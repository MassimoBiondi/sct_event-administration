<?php
class EventAdmin {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_save_event', array($this, 'save_event'));
        add_action('wp_ajax_delete_event', array($this, 'delete_event'));
        add_action('wp_ajax_update_registration', array($this, 'update_registration'));
        add_action('wp_ajax_delete_registration', array($this, 'delete_registration'));
        add_action('wp_ajax_send_registration_email', array($this, 'send_registration_email'));
        add_action('admin_post_export_event_registrations', array($this, 'handle_export_event_registrations'));
        add_action('wp_ajax_sct_retry_single_email', array($this, 'handle_retry_single_email'));
        add_action('admin_head', array($this, 'add_screen_options'));
        add_filter('set-screen-option', array($this, 'save_screen_options'), 10, 3);
        add_action('wp_ajax_export_custom_tables', array($this, 'export_custom_tables'));
        add_action('wp_ajax_add_registration', array($this, 'add_registration'));
        add_action('wp_ajax_select_random_winners', array($this, 'select_random_winners'));
        add_action('wp_ajax_update_registration_guest_counts', array($this, 'update_registration_guest_counts'));
        add_action('wp_ajax_update_registration_guest_count', array($this, 'update_registration_guest_count'));
        add_action('wp_ajax_update_payment_status', array($this, 'update_payment_status'));
        add_action('wp_ajax_view_registration_details', array($this, 'view_registration_details'));
        add_action('wp_ajax_check_github_updates', array($this, 'check_github_updates'));
    }

    public function add_admin_menu() {
        // Add main menu item
        add_menu_page(
            'Event Administration',
            'Events',
            'edit_posts',
            'event-admin',
            array($this, 'display_events_list_page'),
            'dashicons-calendar-alt',
            26
        );

        // Add submenu items
        add_submenu_page(
            'event-admin',
            'Events List',
            'All Events',
            'edit_posts',
            'event-admin',
            array($this, 'display_events_list_page')
        );

        add_submenu_page(
            'event-admin',
            'Add New Event',
            'Add New',
            'edit_posts',
            'event-admin-new',
            array($this, 'display_add_event_page')
        );

        add_submenu_page(
            'event-admin',
            'Registrations',
            'Registrations',
            'edit_posts',
            'event-registrations',
            array($this, 'display_registrations_page')
        );

        add_submenu_page(
            'event-admin',
            'Past Events',
            'Past Events',
            'edit_posts',
            'event-past',
            array($this, 'display_past_events_page')
        );

        add_submenu_page(
            'event-admin',
            'Past Registrations',
            'Past Registrations',
            'edit_posts',
            'past-registrations',
            array($this, 'display_past_registrations_page')
        );

        add_submenu_page(
            'event-admin', // Parent slug
            'Email History', // Page title
            'Email History', // Menu title
            'edit_posts', // Capability
            'sct-email-history', // Menu slug
            array($this, 'sct_render_emails_page') // Callback function
        );
        
        add_submenu_page(
            'event-admin',
            'Settings',
            'Settings',
            'edit_posts',
            'event-email-settings',
            array($this, 'display_settings_page')
        );

    }

    public function display_events_list_page() {
        global $wpdb;
        
        // Get only upcoming events
        $current_date = current_time('Y-m-d');
        
        $events = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}sct_events 
            WHERE CONCAT(event_date, ' ', event_time) >= NOW()
            ORDER BY event_date ASC, event_time ASC"
        );
        
        include EVENT_ADMIN_PATH . 'admin/views/events-list.php';
    }

    // Render emails page
    public function sct_render_emails_page() {
        global $wpdb;

        // Get all events (upcoming and optionally past)
        $current_date = current_time('Y-m-d');
        $show_past = isset($_GET['show_past']) && $_GET['show_past'] === '1';

        // Get all events for dropdown
        $all_events = $wpdb->get_results("SELECT id, event_name, event_date FROM {$wpdb->prefix}sct_events ORDER BY event_date DESC");

        // Get upcoming events (or all if show_past is set)
        if ($show_past) {
            $events = $all_events;
        } else {
            $events = $wpdb->get_results($wpdb->prepare(
                "SELECT id, event_name, event_date FROM {$wpdb->prefix}sct_events WHERE event_date >= %s ORDER BY event_date ASC",
                $current_date
            ));
        }

        // Prepare emails array
        $emails = [];
        foreach ($events as $event) {
            // Fetch emails for this event
            $event_emails = $wpdb->get_results($wpdb->prepare(
                "SELECT 
                    id,
                    event_id,
                    email_type,
                    recipients,
                    subject,
                    message AS email_content,
                    sent_date,
                    status
                FROM {$wpdb->prefix}sct_event_emails
                WHERE event_id = %d
                ORDER BY sent_date DESC",
                $event->id
            ));

            // Group mass emails by subject and 5-min window
            $mass_groups = [];
            foreach ($event_emails as $email) {
                if ($email->email_type === 'mass_email') {
                    $group_key = md5($email->subject . floor(strtotime($email->sent_date)/300));
                    if (!isset($mass_groups[$group_key])) {
                        $mass_groups[$group_key] = [
                            'id' => $email->id,
                            'event_id' => $email->event_id,
                            'event_name' => $event->event_name,
                            'email_type' => 'mass_email',
                            'subject' => $email->subject,
                            'email_content' => $email->email_content,
                            'grouped_sent_date' => $email->sent_date,
                            'recipient_email' => [],
                            'all_recipients' => [],
                            'status' => [],
                            'failed_count' => 0,
                        ];
                    }
                    $mass_groups[$group_key]['recipient_email'][] = $email->recipients;
                    $mass_groups[$group_key]['all_recipients'][] = $email->recipients . ':' . $email->status;
                    $mass_groups[$group_key]['status'][] = $email->status;
                    if ($email->status === 'failed') {
                        $mass_groups[$group_key]['failed_count']++;
                    }
                } else {
                    $email->event_name = $event->event_name;
                    $email->recipient_email = $email->recipients;
                    $email->grouped_sent_date = $email->sent_date;
                    $emails[] = $email;
                }
            }
            // Add grouped mass emails as single rows
            foreach ($mass_groups as $group) {
                $group['recipient_email'] = implode(', ', $group['recipient_email']);
                $group['all_recipients'] = implode('|', $group['all_recipients']);
                // Use 'sent' if all sent, 'failed' if any failed
                $group['status'] = in_array('failed', $group['status']) ? 'failed' : 'sent';
                $emails[] = (object)$group;
            }
        }

        // Sort emails by sent date descending
        usort($emails, function($a, $b) {
            return strtotime($b->grouped_sent_date) - strtotime($a->grouped_sent_date);
        });

        include EVENT_ADMIN_PATH . 'admin/views/emails.php';
    }

    public function display_past_events_page() {
        $past_events = $this->get_events('past');
        include EVENT_ADMIN_PATH . 'admin/views/past-events.php';
    }

    public function display_add_event_page() {
        include EVENT_ADMIN_PATH . 'admin/views/add-event.php';
    }

    public function enqueue_admin_scripts($hook) {
        wp_enqueue_media(); // Enqueue the WordPress Media Uploader
        $screen = get_current_screen();
        $items_per_page = 10; // Default value

        if ($screen->id === 'toplevel_page_event-admin') {
            $items_per_page = get_user_option('events_per_page', get_current_user_id());
        } elseif ($screen->id === 'events_page_event-registrations') {
            $items_per_page = get_user_option('registrations_per_page', get_current_user_id());
        } elseif ($screen->id === 'events_page_event-past') {
            $items_per_page = get_user_option('past_events_per_page', get_current_user_id());
        }

        wp_enqueue_script('jquery-ui-autocomplete');

        // Conditionally enqueue DataTables scripts and styles
        $current_screen = get_current_screen();
        // if (in_array($current_screen->id, array('toplevel_page_event-admin', 'events_page_event-registrations', 'events_page_event-past'))) {
        //     wp_enqueue_script('datatables-js', 'https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js', array('jquery'), '1.11.5', true);
        //     wp_enqueue_style('datatables-css', 'https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css', array(), '1.11.5');
        // }
        wp_enqueue_style('jquery-ui-css', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');

        wp_enqueue_style('uikit-css', 'https://cdn.jsdelivr.net/npm/uikit@3.16.3/dist/css/uikit.min.css', array(), '3.16.3');

        // Enqueue UIkit JavaScript
        wp_enqueue_script('uikit-js', 'https://cdn.jsdelivr.net/npm/uikit@3.16.3/dist/js/uikit.min.js', array('jquery'), '3.16.3', true);
        wp_enqueue_script('uikit-icons-js', 'https://cdn.jsdelivr.net/npm/uikit@3.16.3/dist/js/uikit-icons.min.js', array('uikit-js'), '3.16.3', true);
    

        // Enqueue admin styles and scripts last
        wp_enqueue_style('event-admin-style', EVENT_ADMIN_URL . 'admin/css/admin.css', array(), '1.0.0');
        wp_enqueue_script('event-admin-script', EVENT_ADMIN_URL . 'admin/js/admin.js', array('jquery', 'jquery-ui-accordion', 'media-upload'), '1.0.0', true);

        wp_localize_script('event-admin-script', 'eventAdmin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'update_registration_nonce' => wp_create_nonce('update_registration_nonce'),
            'items_per_page' => $items_per_page,
            'export_nonce' => wp_create_nonce('export_nonce'),
            'copy_event_nonce' => wp_create_nonce('copy_event_nonce'),
        ));
    }

    public function display_registrations_page() {
        global $wpdb;
        $event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : null;
        $upcoming_events = $this->get_events('upcoming', $event_id);
        include EVENT_ADMIN_PATH . 'admin/views/registrations.php';
    }

    public function save_event() {
        error_log('Saving event...');
        check_ajax_referer('save_event', 'event_admin_nonce');
        global $wpdb;

        // Verify nonce
        if (!isset($_POST['event_admin_nonce']) || !wp_verify_nonce($_POST['event_admin_nonce'], 'save_event')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }

        $event_data = array(
            'event_name' => stripslashes(sanitize_text_field($_POST['event_name'])),
            'event_date' => sanitize_text_field($_POST['event_date']),
            'event_time' => sanitize_text_field($_POST['event_time']),
            'location_name' => sanitize_text_field($_POST['location_name']),
            'location_link' => esc_url_raw($_POST['location_link']),
            'description' => stripslashes(wp_kses_post($_POST['event_description'])),
            'guest_capacity' => intval($_POST['guest_capacity']),
            'max_guests_per_registration' => intval($_POST['max_guests_per_registration']),
            'admin_email' => sanitize_email($_POST['admin_email']),
            'member_only' => isset($_POST['member_only']) ? 1 : 0,
            'by_lottery' => isset($_POST['by_lottery']) ? 1 : 0,
            'has_waiting_list' => isset($_POST['has_waiting_list']) ? 1 : 0,
            'external_registration' => isset($_POST['external_registration']) ? 1 : 0,
            'external_registration_url' => isset($_POST['external_registration_url']) ? esc_url_raw($_POST['external_registration_url']) : null,
            'external_registration_text' => isset($_POST['external_registration_text']) ? sanitize_text_field($_POST['external_registration_text']) : 'Register Externally',
            'custom_email_template' => isset($_POST['custom_email_template']) ? wp_unslash($_POST['custom_email_template']) : null,
            'thumbnail_url' => isset($_POST['thumbnail_url']) ? sanitize_text_field($_POST['thumbnail_url']) : null,
            'publish_date' => !empty($_POST['publish_date']) ? sanitize_text_field($_POST['publish_date']) : null,
            'unpublish_date' => !empty($_POST['unpublish_date']) ? sanitize_text_field($_POST['unpublish_date']) : null,
            'pricing_options' => isset($_POST['pricing_options']) ? maybe_serialize($_POST['pricing_options']) : null,
            'goods_services' => isset($_POST['goods_services']) ? maybe_serialize($_POST['goods_services']) : null,
            'payment_methods' => isset($_POST['payment_methods']) ? maybe_serialize($_POST['payment_methods']) : null
        );

        if (isset($_POST['payment_methods']) && is_array($_POST['payment_methods'])) {
            $payment_methods = array_map(function ($method) {
                return [
                    'type' => sanitize_text_field($method['type']),
                    'description' => sanitize_text_field($method['description']),
                    'link' => isset($method['link']) ? esc_url_raw($method['link']) : '',
                    'transfer_details' => isset($method['transfer_details']) ? sanitize_textarea_field($method['transfer_details']) : '',
                ];
            }, $_POST['payment_methods']);

            $event_data['payment_methods'] = maybe_serialize($payment_methods);
        }

        // Handle NULL values for publish_date and unpublish_date
        if (empty($event_data['publish_date'])) {
            $event_data['publish_date'] = null;
        }
        if (empty($event_data['unpublish_date'])) {
            $event_data['unpublish_date'] = null;
        }

        $data_format = array(
            '%s', // event_name
            '%s', // event_date
            '%s', // event_time
            '%s', // location_name
            '%s', // location_link
            '%s', // description
            '%d', // guest_capacity
            '%d', // max_guests_per_registration
            '%s', // admin_email
            '%d', // member_only
            '%d', // by_lottery
            '%d', // has_waiting_list
            '%d', // external_registration
            '%s', // external_registration_url
            '%s', // external_registration_text
            '%s', // custom_email_template
            '%s', // thumbnail_url
            '%s', // publish_date
            '%s', // unpublish_date
            '%s', // pricing_options
            '%s', // goods_services
            '%s'  // payment_methods
        );

        if (isset($_POST['event_id'])) {
            // Update existing event
            $result = $wpdb->update(
                $wpdb->prefix . 'sct_events',
                $event_data,
                array('id' => intval($_POST['event_id'])),
                $data_format,
                array('%d')
            );

            // Process Goods/Service Options
            if (isset($_POST['goods_services']) && is_array($_POST['goods_services'])) {
                $goods_services = array_map(function ($item) {
                    return [
                        'name' => sanitize_text_field($item['name']),
                        'price' => floatval($item['price']),
                        'limit' => intval($item['limit']),
                    ];
                }, $_POST['goods_services']);

                // Serialize and save to the database
                $wpdb->update(
                    "{$wpdb->prefix}sct_events",
                    ['goods_services' => maybe_serialize($goods_services)],
                    ['id' => intval($_POST['event_id'])],
                    ['%s'],
                    ['%d']
                );
            } else {
                // If no goods/services are provided, set the column to NULL
                $wpdb->update(
                    "{$wpdb->prefix}sct_events",
                    ['goods_services' => null],
                    ['id' => intval($_POST['event_id'])],
                    ['%s'],
                    ['%d']
                );
            }
        } else {
            // Insert new event
            $result = $wpdb->insert(
                $wpdb->prefix . 'sct_events',
                $event_data,
                $data_format
            );
        }

        if ($result === false) {
            wp_send_json_error(array('message' => 'Failed to save event.'));
            return;
        }

        wp_send_json_success(array('message' => 'Event saved successfully.'));
    }

    
    public function delete_event() {
        // Check if user has permission
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Unauthorized access'));
            return;
        }

        // Verify nonce
        if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'delete_event')) {
            wp_send_json_error(array('message' => 'Invalid security token'));
            return;
        }

        // Get and validate event ID
        $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
        if (!$event_id) {
            wp_send_json_error(array('message' => 'Invalid event ID'));
            return;
        }

        global $wpdb;

        // Start transaction
        $wpdb->query('START TRANSACTION');

        try {
            // Check for existing emails
            $email_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}sct_event_emails WHERE event_id = %d",
                $event_id
            ));

            // Only delete emails if they exist
            if ($email_count > 0) {
                $wpdb->delete(
                    $wpdb->prefix . 'sct_event_emails',
                    array('event_id' => $event_id),
                    array('%d')
                );
            }

            // Check for existing registrations
            $registration_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}sct_event_registrations WHERE event_id = %d",
                $event_id
            ));

            // Only delete registrations if they exist
            if ($registration_count > 0) {
                $wpdb->delete(
                    $wpdb->prefix . 'sct_event_registrations',
                    array('event_id' => $event_id),
                    array('%d')
                );
            }

            // Delete the event
            $result = $wpdb->delete(
                $wpdb->prefix . 'sct_events',
                array('id' => $event_id),
                array('%d')
            );

            if ($result === false) {
                throw new Exception('Failed to delete event');
            }

            $wpdb->query('COMMIT');

            wp_send_json_success(array('message' => 'Event deleted successfully'));

        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }
    

    public function update_registration() {
        // Verify nonce
        if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'update_registration_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }

        global $wpdb;

        $registration_id = intval($_POST['registration_id']);
        $guest_details = isset($_POST['guest_details']) ? $_POST['guest_details'] : null;

        // Validate guest details
        if (empty($guest_details)) {
            wp_send_json_error(array('message' => 'Guest details are required.'));
            return;
        }

        // Calculate total guests
        $total_guests = 0;
        foreach ($guest_details as $detail) {
            $total_guests += intval($detail['count']);
        }

        if ($total_guests < 1) {
            wp_send_json_error(array('message' => 'Guest count must be at least 1.'));
            return;
        }

        // Serialize guest details for storage
        $guest_details_serialized = maybe_serialize($guest_details);

        // Update the registration
        $result = $wpdb->update(
            $wpdb->prefix . 'sct_event_registrations',
            array(
                'guest_details' => $guest_details_serialized,
                'guest_count' => $total_guests
            ),
            array('id' => $registration_id),
            array('%s', '%d'),
            array('%d')
        );

        if ($result !== false) {
            wp_send_json_success(array('message' => 'Registration updated successfully.'));
        } else {
            wp_send_json_error(array('message' => 'Error updating registration.'));
        }
    }
    
    public function delete_registration() {
        // Check if user has permission
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Unauthorized access'));
            return;
        }

        // Verify nonce
        if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'delete_registration')) {
            wp_send_json_error(array('message' => 'Invalid security token'));
            return;
        }

        // Get and validate registration ID
        $registration_id = isset($_POST['registration_id']) ? intval($_POST['registration_id']) : 0;
        if (!$registration_id) {
            wp_send_json_error(array('message' => 'Invalid registration ID'));
            return;
        }

        global $wpdb;


        // Delete the registration
        $result = $wpdb->delete(
            $wpdb->prefix . 'sct_event_registrations',
            array('id' => $registration_id),
            array('%d')
        );

        if ($result === false) {
            wp_send_json_error(array('message' => 'Failed to delete registration' . $wpdb->last_error));
            return;
        }

        wp_send_json_success(array('message' => 'Registration deleted successfully'));
    }

    private function send_email($registration, $subject, $body_template, $headers, $is_mass_email, $sct_settings) {
        global $wpdb;
        // Fetch event data for this registration
        $event_data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sct_events WHERE id = %d",
            $registration->event_id
        ), ARRAY_A);

        // Prepare placeholder data
        $placeholder_data = array_merge((array)$registration, (array)$event_data);

        // Pricing breakdown and total price
        $pricing_breakdown = '<table style="width: 100%; border-collapse: collapse; margin-top: 10px;">';
        $pricing_breakdown .= '<thead><tr><th style="border: 1px solid #ddd; padding: 8px;">&nbsp;</th><th style="border: 1px solid #ddd; padding: 8px;">Count</th><th style="border: 1px solid #ddd; padding: 8px;">Price</th><th style="border: 1px solid #ddd; padding: 8px;">Total</th></tr></thead>';
        $pricing_breakdown .= '<tbody>';
        $total_price = 0;

        $currency_symbol = $sct_settings['currency_symbol'];
        $currency_format = intval($sct_settings['currency_format']);

        // Pricing options
        if (!empty($registration->guest_details)) {
            $guest_details = maybe_unserialize($registration->guest_details);
            foreach ($guest_details as $detail) {
                $count = intval($detail['count']);
                $name = esc_html($detail['name']);
                $price = floatval($detail['price']);
                $total = $count * $price;
                if ($count > 0) {
                    $pricing_breakdown .= sprintf(
                        '<tr><td style="border-bottom: 1px solid #ddd; padding: 8px;">%s</td><td style="border-bottom: 1px solid #ddd; padding: 8px;">%d</td><td style="border-bottom: 1px solid #ddd; padding: 8px;">%s %s</td><td style="border-bottom: 1px solid #ddd; padding: 8px;">%s %s</td></tr>',
                        $name,
                        $count,
                        esc_html($currency_symbol),
                        number_format($price, $currency_format),
                        esc_html($currency_symbol),
                        number_format($total, $currency_format)
                    );
                    $total_price += $total;
                }
            }
        }

        // Goods/services
        if (!empty($registration->goods_services)) {
            $goods_services = maybe_unserialize($registration->goods_services);
            foreach ($goods_services as $service) {
                $count = intval($service['count']);
                $name = esc_html($service['name']);
                $price = floatval($service['price']);
                $total = $count * $price;
                if ($count > 0) {
                    $pricing_breakdown .= sprintf(
                        '<tr><td style="border-bottom: 1px solid #ddd; padding: 8px;">%s</td><td style="border-bottom: 1px solid #ddd; padding: 8px;">%d</td><td style="border-bottom: 1px solid #ddd; padding: 8px;">%s %s</td><td style="border-bottom: 1px solid #ddd; padding: 8px;">%s %s</td></tr>',
                        $name,
                        $count,
                        esc_html($currency_symbol),
                        number_format($price, $currency_format),
                        esc_html($currency_symbol),
                        number_format($total, $currency_format)
                    );
                    $total_price += $total;
                }
            }
        }

        // Add total row
        $pricing_breakdown .= sprintf(
            '<tr><td colspan="3" style="border-top: 1px solid #ddd; padding: 8px; text-align: right;"><strong>Total</strong></td><td style="border-top: 1px solid #ddd; border-bottom: 2px solid #ddd; padding: 8px;"><strong>%s %s</strong></td></tr>',
            esc_html($currency_symbol),
            number_format($total_price, $currency_format)
        );
        $pricing_breakdown .= '</tbody></table>';

        if (empty($registration->guest_details) && $registration->guest_count > 0) {
            $pricing_breakdown = '<strong>Number of Guests:</strong> ' . $registration->guest_count;
        }

        $placeholder_data['pricing_overview'] = $pricing_breakdown;
        $placeholder_data['total_price'] = sprintf('%s %s', esc_html($currency_symbol), number_format($total_price, $currency_format));

        // Payment method details
        $payment_methods = isset($event_data['payment_methods']) ? maybe_unserialize($event_data['payment_methods']) : array();
        $selected_payment_method = array();
        if (!empty($payment_methods) && !empty($registration->payment_method)) {
            $selected_payment_method = array_filter($payment_methods, function ($method) use ($registration) {
                return $method['type'] === $registration->payment_method;
            });
        }
        if (!empty($selected_payment_method)) {
            $selected_payment_method = reset($selected_payment_method);
            $placeholder_data['payment_type'] = $selected_payment_method['type'];
            $placeholder_data['payment_name'] = $selected_payment_method['description'];
            $placeholder_data['payment_link'] = $selected_payment_method['link'];
            $placeholder_data['payment_description'] = $selected_payment_method['transfer_details'];

            $placeholder_data['payment_method_details'] = '<div class="content-section"><h3>Payment Information</h3>';
            if ($selected_payment_method['type'] === 'online') {
                $placeholder_data['payment_method_details'] .= sprintf(
                    '<div class="payment-details-box">To complete your payment securely online, please click the link below:'.
                    '<a style="font-weight: bold;" href="%s" target="_blank" rel="noopener">Complete Payment Online Here</a></div>',
                    esc_html($selected_payment_method['link'])
                );
            } elseif ($selected_payment_method['type'] === 'transfer') {
                $placeholder_data['payment_method_details'] .= sprintf(
                    '<p>Please transfer the total amount to the following bank account details:</p>'.
                    '<div class="payment-details-box">'.
                    '<strong>%s</strong>'.
                    '</div>'.
                    '<p>Kindly include your name and "{event_name}" as the reference for the bank transfer.</p>',
                    nl2br(esc_html($selected_payment_method['transfer_details']))
                );
            } elseif ($selected_payment_method['type'] === 'cash') {
                $placeholder_data['payment_method_details'] .= sprintf(
                    '<div class="payment-details-box">%s</div>',
                    nl2br(esc_html($selected_payment_method['transfer_details']))
                );
            } else {
                $placeholder_data['payment_method_details'] .= '<p>Payment method not specified.</p>';
            }
            $placeholder_data['payment_method_details'] .= '</div>';

            // Replace placeholders in payment_method_details
            $placeholder_data['payment_method_details'] = $this->replace_email_placeholders(
                $placeholder_data['payment_method_details'],
                $placeholder_data
            );
        }

        // Replace placeholders in the email body
        $personalized_body = $this->replace_email_placeholders($body_template, $placeholder_data);

        // Generate DKIM signature and add to headers
        $dkim_header = $this->generate_dkim_signature($subject, 'events@swissclubtokyo.com', $registration->email, wpautop($personalized_body));
        if ($dkim_header) {
            $headers[] = $dkim_header;
        }
        
        // Send the email
        $sent = wp_mail(
            $registration->email,
            $subject,
            wpautop($personalized_body),
            $headers
        );

        if ($is_mass_email) {
            $email_type = 'mass_email';
        } else {
            $email_type = 'individual_email';
        }
        
        $this->log_email($event_data['id'], $email_type, $registration->email, $subject, wpautop($personalized_body), $sent ? 'sent' : 'failed');

        // Optionally log or store the email as needed...

        return $sent;
    }

    private function log_email($event_id, $email_type, $recipient_email, $subject, $message, $status = 'sent') {
        return SCT_Event_Email_Utilities::log_email($event_id, $email_type, $recipient_email, $subject, $message, $status);
    }

    // Helper for placeholder replacement (if not already present)
    private function replace_email_placeholders($template, $data) {
        return SCT_Event_Email_Utilities::replace_email_placeholders($template, $data);
    }

    public function send_registration_email() {
        // Verify nonce
        if (!isset($_POST['email_security']) || !wp_verify_nonce($_POST['email_security'], 'send_email_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }
    
        global $wpdb;
        $event_id = intval($_POST['event_id']);
        $sct_settings = get_option('event_admin_settings', array(
            'event_registration_page' => get_option('event_registration_page'),
            'event_management_page' => get_option('event_management_page'),
            'admin_email' => get_option('admin_email'),
            'notification_subject' => 'New Event Registration: {event_name}',
            'notification_template' => $this->get_default_notification_template(),
            'confirmation_subject' => 'Registration Confirmation: {event_name}',
            'confirmation_template' => $this->get_default_confirmation_template(),
            'start_of_week' => get_option('start_of_week', 'monday'),
        ));

        $event_data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sct_events WHERE id = %d",
            $event_id
        ));

        $subject = sanitize_text_field($_POST['email_subject']);
        $body_template = wp_kses_post($_POST['email_body']);
        $is_mass_email = isset($_POST['is_mass_email']) && $_POST['is_mass_email'] === '1';

        $admin_email = !empty($event_data->admin_email) ? 
            $event_data->admin_email : 
            $sct_settings['admin_email'] ;

        // Get the full URL from WordPress
        $blog_url = get_bloginfo('url');

        // Parse the URL to get its components
        $parsed_url = parse_url($blog_url);

        // Get just the host (domain name)
        $domain = $parsed_url['host'];

        // Remove any server name prefix (including www)
        $domain = preg_replace('/^.*?([^.]+\.[^.]+)$/', '$1', $domain);

        if (strpos($domain, '.') === false) {
            $domain .= '.qq';
        }

        // Set up base email headers
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <events@swissclubtokyo.com>',
            'Reply-To: events@swissclubtokyo.com'
        );
    
        $success_count = 0;
        $fail_count = 0;
        $total_to_send = 0;
    
        if ($is_mass_email) {
            // Get all registrants for the event
            $registrations = $wpdb->get_results($wpdb->prepare(
                "SELECT r.*, e.*, r.id as registration_id
                 FROM {$wpdb->prefix}sct_event_registrations r 
                 JOIN {$wpdb->prefix}sct_events e ON r.event_id = e.id 
                 WHERE r.event_id = %d",
                $event_id
            ));

            if (empty($registrations)) {
                wp_send_json_error(array('message' => 'No registrations found'));
                return;
            }
    
            $total_to_send = count($registrations);
    
            // Send individual emails to each registrant
            foreach ($registrations as $registration) {
                if ($this->send_email($registration, $subject, $body_template, $headers, $is_mass_email, $sct_settings)) {
                    $success_count++;
                } else {
                    $fail_count++;
                }
    
                // Add a small delay to prevent overwhelming the server
                usleep(100000); // 100ms delay
            }
    
            // Prepare response message
            if ($success_count === $total_to_send) {
                wp_send_json_success(array(
                    'message' => sprintf('Successfully sent %d emails', $success_count)
                ));
            } else {
                wp_send_json_error(array(
                    'message' => sprintf(
                        'Sent %d emails successfully, %d failed. Total attempted: %d',
                        $success_count,
                        $fail_count,
                        $total_to_send
                    )
                ));
            }
    
        } else {
            // Single email logic
            $registration_id = intval($_POST['registration_id']);
            $registration = $wpdb->get_row($wpdb->prepare(
                "SELECT r.*, e.* 
                 FROM {$wpdb->prefix}sct_event_registrations r 
                 JOIN {$wpdb->prefix}sct_events e ON r.event_id = e.id 
                 WHERE r.id = %d",
                $registration_id
            ));
            
            if (!$registration) {
                wp_send_json_error(array('message' => 'Registration not found'));
                return;
            }
            
            $registration->id = $registration_id; // Ensure registration ID is set for email logging
            if ($this->send_email($registration, $subject, $body_template, $headers, $is_mass_email, $sct_settings)) {
                wp_send_json_success(array('message' => 'Email sent successfully'));
            } else {
                wp_send_json_error(array('message' => 'Failed to send email'));
            }
        }
    }

    public function get_events($type = 'upcoming', $event_id = null) {
        global $wpdb;

        $order = ($type === 'upcoming') ? 'ASC' : 'DESC';
        
        $current_date = current_time('Y-m-d');
        
        $where_clause = ($type === 'upcoming') 
            ? "WHERE CONCAT(event_date, ' ', event_time) >= NOW()"
            : "WHERE CONCAT(event_date, ' ', event_time) < NOW()";
            
        $where_clause = ($event_id !== null)
            ? $where_clause . " AND id = ".$event_id
            : $where_clause;

        // $where_clause .= " AND (publish_date IS NULL OR publish_date <= NOW())";
        // $where_clause .= " AND (unpublish_date IS NULL OR unpublish_date > NOW())";

        $sql = "SELECT * FROM {$wpdb->prefix}sct_events 
            {$where_clause}
            ORDER BY event_date $order, event_time $order";
        
        return $wpdb->get_results($sql);
    }
    
    
    private function get_registration_count($event_id) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(guest_count) FROM {$wpdb->prefix}sct_event_registrations WHERE event_id = %d",
            $event_id
        )) ?: 0;
    }

    public function display_past_registrations_page() {
        global $wpdb;
        
        // Get event ID from URL if specified
        $event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;
        
        // Get past events
        $past_events = $this->get_events('past');
        
        // If specific event is selected, get its registrations
        if ($event_id) {
            $registrations = $wpdb->get_results($wpdb->prepare(
                "SELECT r.*, e.event_name, e.event_date, e.event_time 
                FROM {$wpdb->prefix}sct_event_registrations r
                JOIN {$wpdb->prefix}sct_events e ON r.event_id = e.id
                WHERE r.event_id = %d
                ORDER BY r.registration_date ASC",
                $event_id
            ));
        }
        
        include EVENT_ADMIN_PATH . 'admin/views/past-registrations.php';
    }    
    
    public function display_settings_page() {
        // Handle form submission
        if (isset($_POST['submit_sct_settings']) && check_admin_referer('save_sct_settings')) {
            $this->save_sct_settings();
        }

        // Get current settings
        $sct_settings = get_option('event_admin_settings', array(
            'event_registration_page' => get_option('event_registration_page'),
            'event_management_page' => get_option('event_management_page'),
            'admin_email' => get_option('admin_email'),
            'currency' => get_option('currency'),
            'currency_symbol' => get_option('currency_symbol'),
            'currency_format' => get_option('currency_format'),
            'notification_subject' => 'New Event Registration: {event_name}',
            'notification_template' => $this->get_default_notification_template(),
            'confirmation_subject' => 'Registration Confirmation: {event_name}',
            'confirmation_template' => $this->get_default_confirmation_template()
        ));

        include EVENT_ADMIN_PATH . 'admin/views/settings.php';
    }

    private function get_default_notification_template() {
        return "New registration for {{event.name}}\n\n" .
               "Registration Details:\n" .
               "Name: {{registration.name}}\n" .
               "Email: {{registration.email}}\n" .
               "Number of Guests: {{registration.guest_count}}\n" .
               "{{registration.pricing_breakdown}}" .
               "Registration Date: {{registration.date}}\n\n" .
               "Event Details:\n" .
               "Event Name: {{event.name}}\n" .
               "Event Date: {{event.date}}\n" .
               "Event Time: {{event.time}}\n" .
               "Location: {{event.location_name}}";
    }

    public function getDefaultNotificationTemplate() {
        return $this->get_default_notification_template();
    }
    
    private function get_default_confirmation_template() {
        return "<div class='header'>
            <h1>Registration Confirmed!</h1>
            <p>Your spot for <strong>{{event.name}}</strong> is secured.</p>
        </div>
        <div class='content-section'><strong>Hello {{registration.name}},</strong>
            Thank you for registering for <strong>{{event.name}}</strong>!
            We're excited to see you there and have prepared this confirmation for your records
        </div>
        <div class='content-section'>
            <h3>Event Details</h3>
            <p><strong class='strong-text'>Event:</strong>{{event.name}}</p>
            <p><strong class='strong-text'>Date:</strong>{{event.date}}</p>
            <p><strong class='strong-text'>Time:</strong>{{event.time}}</p>
            <p><strong class='strong-text'>Location:</strong>{{event.location_name}} ({{event.location_link}} )</p>
            <p>{{event.description}}</p>
        </div>
        <div class='content-section'>
        <h3>Your Registration</h3>
            <p><strong class='strong-text'>Registrant Name:</strong>{{registration.name}}
            <strong class='strong-text'>Registrant Email:</strong>{{registration.email}}</p>
            {{registration.pricing_overview}}
        </div>
        {{registration.payment_method_details}}
        <div class='footer'>
            We look forward to welcoming you to AGM!
            Best regards, The Swiss Club Tokyo Event Team
            {{registration.reservation_link}}
        </div>";
    }

    public function getDefaultConfirmationTemplate() {
        return $this->get_default_confirmation_template();
    }
    
    public function save_sct_settings() {
        if (!current_user_can('manage_options')) {
            return;
        }
    
        check_admin_referer('save_sct_settings');
    
        $sct_settings = array(
            'event_registration_page' => intval($_POST['event_registration_page']),
            'event_management_page' => intval($_POST['event_management_page']),
            'admin_email' => sanitize_email($_POST['admin_email']),
            'currency' => sanitize_text_field($_POST['currency']),
            'currency_symbol' => sanitize_text_field($_POST['currency_symbol']),
            'currency_format' => sanitize_text_field($_POST['currency_format']),
            'notification_subject' => sanitize_text_field($_POST['notification_subject']),
            'notification_template' => wp_kses_post($_POST['notification_template']),
            'confirmation_subject' => sanitize_text_field($_POST['confirmation_subject']),
            'confirmation_template' => wp_kses_post(stripslashes($_POST['confirmation_template'])),
            'start_of_week' => intval($_POST['start_of_week']),
            'github_access_token' => isset($_POST['github_access_token']) ? sanitize_text_field($_POST['github_access_token']) : ''
        );
    
        update_option('event_admin_settings', $sct_settings);
    }

    public function handle_export_event_registrations() {
        if (!current_user_can('edit_posts')) {
            wp_die('Unauthorized access');
        }

        global $wpdb;

        $event_id = intval($_POST['event_id']);
        $event = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sct_events WHERE id = %d",
            $event_id
        ));

        $registrations = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sct_event_registrations WHERE event_id = %d ORDER BY registration_date ASC",
            $event_id
        ));

        $pricing_options = !empty($event->pricing_options) ? maybe_unserialize($event->pricing_options) : [];
        $goods_services_options = !empty($event->goods_services) ? maybe_unserialize($event->goods_services) : [];

        $has_pricing_options = !empty($pricing_options);
        $has_goods_services = !empty($goods_services_options);

        // Get currency settings from sct_settings
        $sct_settings = get_option('event_admin_settings', array(
            'currency_symbol' => '',
            'currency_format' => 2,
        ));
        $currency_symbol = isset($sct_settings['currency_symbol']) ? $sct_settings['currency_symbol'] : '';
        $currency_format = isset($sct_settings['currency_format']) ? intval($sct_settings['currency_format']) : 2;

        $filename = sanitize_title($event->event_name) . '-registrations-' . date('Y-m-d') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        fputs($output, "\xEF\xBB\xBF"); // UTF-8 BOM

        // Build CSV headers
        $headers = ['Attendance', 'Name', 'Email', 'Registration Date', 'Total Guests'];
        if ($has_pricing_options) {
            foreach ($pricing_options as $option) {
                $headers[] = $option['name'];
            }
        }
        if ($has_goods_services) {
            foreach ($goods_services_options as $service) {
                $headers[] = $service['name'];
            }
        }
        if ($has_pricing_options || $has_goods_services) {
            $headers[] = 'Total Price';
        }
        if ($event->by_lottery) {
            $headers[] = 'Winner';
        }
        $headers[] = 'Remarks';
        fputcsv($output, $headers);

        // Add registration data
        foreach ($registrations as $registration) {
            $row = [];
            $row[] = ''; // Attendance (not tracked here)
            $row[] = $registration->name;
            $row[] = $registration->email;
            $row[] = $registration->registration_date;
            $row[] = $registration->guest_count;

            $guest_details = !empty($registration->guest_details) ? maybe_unserialize($registration->guest_details) : [];
            $goods_services = !empty($registration->goods_services) ? maybe_unserialize($registration->goods_services) : [];

            $total_price = 0;

            // Pricing options columns
            if ($has_pricing_options) {
                foreach ($pricing_options as $index => $option) {
                    $count = isset($guest_details[$index]['count']) ? intval($guest_details[$index]['count']) : 0;
                    $row[] = $count;
                    $price = isset($option['price']) ? floatval($option['price']) : 0;
                    $total_price += $count * $price;
                }
            }

            // Goods/services columns
            if ($has_goods_services) {
                foreach ($goods_services_options as $index => $service) {
                    $count = isset($goods_services[$index]['count']) ? intval($goods_services[$index]['count']) : 0;
                    $row[] = $count;
                    $price = isset($service['price']) ? floatval($service['price']) : 0;
                    $total_price += $count * $price;
                }
            }

            // Total Price
            if ($has_pricing_options || $has_goods_services) {
                $row[] = $currency_symbol . ' ' . number_format($total_price, $currency_format);
            }

            // Winner
            if ($event->by_lottery) {
                $row[] = !empty($registration->is_winner) ? 'Yes' : 'No';
            }

            $row[] = ''; // Remarks

            fputcsv($output, $row);
        }

        // Add a blank row before the footer
        fputcsv($output, []);

        // Add a footer row for pricing references
        $footer = ['Pricing Reference', '', '', '', ''];
        if ($has_pricing_options) {
            foreach ($pricing_options as $option) {
                $footer[] = isset($option['price']) ? $currency_symbol . ' ' . number_format($option['price'], $currency_format) : '';
            }
        }
        if ($has_goods_services) {
            foreach ($goods_services_options as $service) {
                $footer[] = isset($service['price']) ? $currency_symbol . ' ' . number_format($service['price'], $currency_format) : '';
            }
        }
        if ($has_pricing_options || $has_goods_services) {
            $footer[] = '';
        }
        if ($event->by_lottery) {
            $footer[] = '';
        }
        $footer[] = '';
        $footer = array_pad($footer, count($headers), '');
        fputcsv($output, $footer);

        fclose($output);
        exit;
    }

    /**
     * Handle export actions
     */
    public function handle_export_requests() {
        if (isset($_POST['export_all_events'])) {
            check_admin_referer('export_events_nonce', 'export_events_nonce');
            $this->export_events_to_csv();
        }

        if (isset($_POST['export_event_registrations']) && !empty($_POST['event_id'])) {
            check_admin_referer('export_registrations_nonce', 'export_registrations_nonce');
            $event_id = intval($_POST['event_id']);
            $this->export_event_registrations_to_csv($event_id);
        }
    }



    public function handle_retry_single_email() {
        // Verify nonce
        if (!check_ajax_referer('sct_email_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }

        // Get and validate parameters
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;

        if (empty($email) || empty($event_id)) {
            wp_send_json_error(array('message' => 'Missing required parameters'));
            return;
        }

        try {
            // Get event details
            $event = get_post($event_id);
            if (!$event || $event->post_type !== 'sct_event') {
                throw new Exception('Invalid event');
            }

            // Get email template and subject
            $email_template = get_post_meta($event_id, '_event_email_template', true);
            $email_subject = get_post_meta($event_id, '_event_email_subject', true);

            if (empty($email_template) || empty($email_subject)) {
                throw new Exception('Email template or subject not found');
            }

            // Generate DKIM signature and add to headers
            $headers = array('Content-Type: text/plain; charset=UTF-8');
            $dkim_header = $this->generate_dkim_signature($email_subject, 'events@swissclubtokyo.com', $email, $email_template);
            if ($dkim_header) {
                $headers[] = $dkim_header;
            }
            
            // Send the email
            $sent = wp_mail($email, $email_subject, $email_template, $headers);

            if (!$sent) {
                throw new Exception('Failed to send email');
            }

            // Update email status in database if needed
            $this->update_email_status($event_id, $email, 'sent');

            wp_send_json_success(array('message' => 'Email sent successfully'));

        } catch (Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }

    private function update_email_status($event_id, $email, $status) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sct_email_log';
        
        return $wpdb->update(
            $table_name,
            array('status' => $status),
            array(
                'event_id' => $event_id,
                'recipient_email' => $email
            ),
            array('%s'),
            array('%d', '%s')
        );
    }
    
    private function generate_dkim_signature($subject, $from_email, $to_email, $body) {
        return SCT_Event_Email_Utilities::generate_dkim_signature($subject, $from_email, $to_email, $body);
    }
    
  

    public function update_registration_guest_counts() {
        // Verify nonce
        if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'update_registration_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed.'));
            return;
        }

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Unauthorized.'));
            return;
        }

        global $wpdb;

        $registration_id = intval($_POST['registration_id']);
        $guest_details = isset($_POST['guest_details']) ? $_POST['guest_details'] : null;
        $goods_services = isset($_POST['goods_services']) ? $_POST['goods_services'] : null;

        if (empty($guest_details) && empty($goods_services)) {
            wp_send_json_error(array('message' => 'Guest details and goods/services are required.'));
            return;
        }

        // Calculate total guests
        $total_guests = 0;
        foreach ($guest_details as $detail) {
            $total_guests += intval($detail['count']);
        }

        if ($total_guests < 1) {
            wp_send_json_error(array('message' => 'Total guest count must be at least 1.'));
            return;
        }

        // Serialize guest details and goods/services for storage
        $guest_details_serialized = maybe_serialize($guest_details);
        $goods_services_serialized = maybe_serialize($goods_services);

        // Update the registration in the database
        $result = $wpdb->update(
            "{$wpdb->prefix}sct_event_registrations",
            array(
                'guest_details' => $guest_details_serialized,
                'goods_services' => $goods_services_serialized,
                'guest_count' => $total_guests
            ),
            array('id' => $registration_id),
            array('%s', '%s', '%d'),
            array('%d')
        );

        if ($result !== false) {
            wp_send_json_success(array('message' => 'Guest counts updated successfully.'));
        } else {
            wp_send_json_error(array('message' => 'Failed to update guest counts.'));
        }
    }

    public function update_registration_guest_count() {
        // Verify nonce
        if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'update_registration_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed.'));
            return;
        }

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Unauthorized.'));
            return;
        }

        global $wpdb;

        $registration_id = intval($_POST['registration_id']);
        $guest_count = intval($_POST['guest_count']);

        if ($guest_count < 1) {
            wp_send_json_error(array('message' => 'Guest count must be at least 1.'));
            return;
        }

        // Update the registration in the database
        $result = $wpdb->update(
            "{$wpdb->prefix}sct_event_registrations",
            array('guest_count' => $guest_count),
            array('id' => $registration_id),
            array('%d'),
            array('%d')
        );

        if ($result !== false) {
            wp_send_json_success(array('message' => 'Guest count updated successfully.'));
        } else {
            wp_send_json_error(array('message' => 'Failed to update guest count.'));
        }
    }

    public function update_payment_status() {
        // Verify nonce
        if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'update_payment_status')) {
            wp_send_json_error(array('message' => 'Security check failed.'));
            return;
        }

        // Check user permissions
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Unauthorized.'));
            return;
        }

        global $wpdb;

        // Get and validate input
        $registration_id = isset($_POST['registration_id']) ? intval($_POST['registration_id']) : 0;
        $payment_status = isset($_POST['payment_status']) ? sanitize_text_field($_POST['payment_status']) : '';

        if (!$registration_id || !in_array($payment_status, array('pending', 'paid', 'failed'), true)) {
            wp_send_json_error(array('message' => 'Invalid input.'));
            return;
        }

        // Update the payment status in the database
        $result = $wpdb->update(
            "{$wpdb->prefix}sct_event_registrations",
            array('payment_status' => $payment_status),
            array('id' => $registration_id),
            array('%s'),
            array('%d')
        );

        if ($result !== false) {
            wp_send_json_success(array('message' => 'Payment status updated successfully.'));
        } else {
            wp_send_json_error(array('message' => 'Failed to update payment status.'));
        }
    }

    public function add_screen_options() {
        $screen = get_current_screen();
        if ($screen->id === 'toplevel_page_event-admin') {
            add_screen_option('per_page', array(
                'label' => 'Events per page',
                'default' => 10,
                'option' => 'events_per_page'
            ));
        } elseif ($screen->id === 'events_page_event-registrations') {
            add_screen_option('per_page', array(
                'label' => 'Registrations per page',
                'default' => 10,
                'option' => 'registrations_per_page'
            ));
        } elseif ($screen->id === 'events_page_event-past') {
            add_screen_option('per_page', array(
                'label' => 'Past events per page',
                'default' => 10,
                'option' => 'past_events_per_page'
            ));
        }
    }

    public function save_screen_options($status, $option, $value) {
        if (in_array($option, array('events_per_page', 'registrations_per_page', 'past_events_per_page'))) {
            return $value;
        }
        return $status;
    }

    public function add_registration() {
        if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'update_registration_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed.'));
            return;
        }

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }

        global $wpdb;

        $event_id = intval($_POST['event_id']);
        $name = sanitize_text_field($_POST['name']);
        $email = sanitize_email($_POST['email']);
        $guest_details = isset($_POST['guest_details']) ? $_POST['guest_details'] : null;
        $goods_services = isset($_POST['goods_services']) ? $_POST['goods_services'] : null;
        $guest_count = isset($_POST['guest_count']) ? intval($_POST['guest_count']) : 0;

        // Process guest details
        $processed_guest_details = array();
        if (!empty($guest_details)) {
            foreach ($guest_details as $index => $detail) {
                $processed_guest_details[$index] = array(
                    'count' => intval($detail['count']),
                    'name' => sanitize_text_field($detail['name']),
                    'price' => floatval($detail['price']),
                );
                $guest_count += $processed_guest_details[$index]['count']; // Update total guest count
            }
        }

        // Process goods/services
        $processed_goods_services = array();
        if (!empty($goods_services)) {
            foreach ($goods_services as $index => $service) {
                $processed_goods_services[$index] = array(
                    'count' => intval($service['count']),
                    'name' => sanitize_text_field($service['name']),
                    'price' => floatval($service['price']),
                );
            }
        }

        // Serialize guest details and goods/services for storage
        $guest_details_serialized = maybe_serialize($processed_guest_details);
        $goods_services_serialized = maybe_serialize($processed_goods_services);

        // Prepare registration data
        $registration_data = array(
            'event_id' => $event_id,
            'name' => $name,
            'email' => $email,
            'guest_details' => $guest_details_serialized,
            'goods_services' => $goods_services_serialized,
            'guest_count' => $guest_count,
            'registration_date' => current_time('mysql'),
        );

        // Insert registration into the database
        $result = $wpdb->insert(
            "{$wpdb->prefix}sct_event_registrations",
            $registration_data,
            array('%d', '%s', '%s', '%s', '%s', '%d', '%s')
        );

        if ($result) {
            wp_send_json_success(array('message' => 'Registration added successfully.'));
        } else {
            wp_send_json_error(array('message' => 'Failed to add registration.'));
        }
    }

    public function select_random_winners() {
        check_ajax_referer('select_random_winners_nonce', 'security');

        $event_id = intval($_POST['event_id']);
        $num_winners = intval($_POST['num_winners']);

        global $wpdb;

        // Get all registrations for the event
        $registrations = $wpdb->get_results($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}sct_event_registrations WHERE event_id = %d",
            $event_id
        ));

        if (count($registrations) < $num_winners) {
            wp_send_json_error('Not enough registrations to select the requested number of winners.');
        }

        // Shuffle and select random winners
        shuffle($registrations);
        $winners = array_slice($registrations, 0, $num_winners);

        // Mark the winners
        foreach ($winners as $winner) {
            $wpdb->update(
                "{$wpdb->prefix}sct_event_registrations",
                array('is_winner' => 1),
                array('id' => $winner->id),
                array('%d'),
                array('%d')
            );
        }

        wp_send_json_success('Winners selected successfully.');
    }

    public function export_custom_tables() {
        check_ajax_referer('export_nonce', 'security');

        global $wpdb;

        // Define the tables to export
        $tables = [
            "{$wpdb->prefix}sct_events",
            "{$wpdb->prefix}sct_event_registrations",
            "{$wpdb->prefix}sct_event_emails"
        ];

        // Create a temporary file
        $tmpfile = tempnam(sys_get_temp_dir(), 'wp_export_');
        $handle = fopen($tmpfile, 'w');

        // Loop through each table and export its data
        foreach ($tables as $table) {
            // Validate table name to prevent SQL injection
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
                continue; // Skip invalid table names
            }
            $results = $wpdb->get_results("SELECT * FROM `{$table}`", ARRAY_A);
            if (!empty($results)) {
                // Write table name
                fwrite($handle, "-- Table: $table\n");

                // Write table data
                foreach ($results as $row) {
                    $values = array_map([$wpdb, 'prepare'], array_values($row));
                    $values = implode(", ", $values);
                    fwrite($handle, "INSERT INTO $table VALUES ($values);\n");
                }
            }
        }

        fclose($handle);

        // Send the file URL to the browser for download
        $upload_dir = wp_upload_dir();
        $file_url = $upload_dir['url'] . '/' . basename($tmpfile);
        rename($tmpfile, $upload_dir['path'] . '/' . basename($tmpfile));

        wp_send_json_success(['url' => $file_url]);
    }

    public function view_registration_details() {
        // Verify nonce
        if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'view_registration_details')) {
            wp_send_json_error(array('message' => 'Security check failed.'));
            return;
        }

        // Check user permissions
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Unauthorized.'));
            return;
        }

        global $wpdb;

        // Get and validate input
        $registration_id = isset($_POST['registration_id']) ? intval($_POST['registration_id']) : 0;

        if (!$registration_id) {
            wp_send_json_error(array('message' => 'Invalid registration ID.'));
            return;
        }

        // Fetch registration details
        $registration = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sct_event_registrations WHERE id = %d",
            $registration_id
        ));

        if (!$registration) {
            wp_send_json_error(array('message' => 'Registration not found.'));
            return;
        }

        // Fetch event details
        $event = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sct_events WHERE id = %d",
            $registration->event_id
        ));

        // Fetch pricing options and goods/services
        $guest_details = maybe_unserialize($registration->guest_details);
        $goods_services = maybe_unserialize($registration->goods_services);
        $pricing_options = $event ? maybe_unserialize($event->pricing_options) : [];
        $goods_services_options = $event ? maybe_unserialize($event->goods_services) : [];

        // Calculate total price
        $total_price = 0;
        $currency_symbol = isset($event->currency_symbol) ? $event->currency_symbol : '';
        $currency_format = isset($event->currency_format) ? intval($event->currency_format) : 2;

        ob_start();
        ?>
        <p><strong>Name:</strong> <?php echo esc_html($registration->name); ?></p>
        <p><strong>Email:</strong> <?php echo esc_html($registration->email); ?></p>
        <p><strong>Guest Count:</strong> <?php echo esc_html($registration->guest_count); ?></p>
        <?php
        $has_pricing = !empty($pricing_options);
        $has_goods = !empty($goods_services_options);

        // Calculate total price for pricing options
        if ($has_pricing) {
            foreach ($pricing_options as $index => $option) {
                $count = isset($guest_details[$index]['count']) ? intval($guest_details[$index]['count']) : 0;
                $price = isset($option['price']) ? floatval($option['price']) : 0;
                $total_price += $count * $price;
            }
        }
        // Calculate total price for goods/services
        if ($has_goods) {
            foreach ($goods_services_options as $index => $service) {
                $count = isset($goods_services[$index]['count']) ? intval($goods_services[$index]['count']) : 0;
                $price = isset($service['price']) ? floatval($service['price']) : 0;
                $total_price += $count * $price;
            }
        }

        // Only show payment status and option if total price > 0
        if ($total_price > 0): ?>
        <?php endif; ?>

        <?php if ($has_pricing): ?>
            <h4>Pricing Options</h4>
            <table class="uk-table uk-table-striped">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Count</th>
                        <th>Price</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($pricing_options as $index => $option) :
                    $count = isset($guest_details[$index]['count']) ? intval($guest_details[$index]['count']) : 0;
                    $price = isset($option['price']) ? floatval($option['price']) : 0;
                    $subtotal = $count * $price;
                    if ($count == 0) continue;
                ?>
                    <tr>
                        <td><?php echo esc_html($option['name']); ?></td>
                        <td><?php echo $count; ?></td>
                        <td><?php echo esc_html($currency_symbol) . ' ' . number_format($price, $currency_format); ?></td>
                        <td><?php echo esc_html($currency_symbol) . ' ' . number_format($subtotal, $currency_format); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <?php foreach ($goods_services_options as $index => $service) :
            $count = isset($goods_services[$index]['count']) ? intval($goods_services[$index]['count']) : 0;
            $price = isset($service['price']) ? floatval($service['price']) : 0;
            $subtotal = $count * $price;
            $total_count += $count;
        ?>
        <?php endforeach; ?>

        <?php if ($total_count > 0): ?>
            <h4>Goods/Services</h4>
            <table  class="uk-table uk-table-striped">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Count</th>
                        <th>Price</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($goods_services_options as $index => $service) :
                    $count = isset($goods_services[$index]['count']) ? intval($goods_services[$index]['count']) : 0;
                    $price = isset($service['price']) ? floatval($service['price']) : 0;
                    $subtotal = $count * $price;
                    if ($count == 0) continue;
                ?>
                    <tr>
                        <td><?php echo esc_html($service['name']); ?></td>
                        <td><?php echo $count; ?></td>
                        <td><?php echo esc_html($currency_symbol) . ' ' . number_format($price, $currency_format); ?></td>
                        <td><?php echo esc_html($currency_symbol) . ' ' . number_format($subtotal, $currency_format); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <?php if (($has_pricing || $has_goods) && $total_price > 0): ?>
            <p><strong>Total Price:</strong> <?php echo esc_html($currency_symbol) . ' ' . number_format($total_price, $currency_format); ?></p>
            <p><strong>Payment Status:</strong> <?php echo esc_html($registration->payment_status); ?></p>
            <?php
            // Payment method
            $selected_payment = '';
            if (!empty($event->payment_methods) && !empty($registration->payment_method)) {
                $payment_methods = maybe_unserialize($event->payment_methods);
                foreach ($payment_methods as $method) {
                    if ($method['type'] === $registration->payment_method) {
                        $selected_payment = esc_html($method['description']);
                        break;
                    }
                }
            }
            ?>
            <p><strong>Payment Option:</strong> <?php echo $selected_payment ? $selected_payment : esc_html($registration->payment_method); ?></p>

        <?php endif; ?>
        <?php
        $html = ob_get_clean();

        // Return the HTML content
        wp_send_json_success(array('html' => $html));
    }

    public function check_github_updates() {
        // Verify nonce
        if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'save_sct_settings')) {
            wp_send_json_error(array('message' => 'Security check failed.'));
            return;
        }

        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized.'));
            return;
        }

        // Get GitHub access token from settings
        $sct_settings = get_option('event_admin_settings', []);
        $access_token = isset($sct_settings['github_access_token']) ? $sct_settings['github_access_token'] : '';

        // Initialize GitHub updater and force check
        if (class_exists('SCT_GitHub_Updater')) {
            $updater = new SCT_GitHub_Updater(__FILE__, 'MassimoBiondi', 'sct_event-administration', $access_token);
            $repository_data = $updater->force_check();

            if ($repository_data) {
                $current_version = EVENT_ADMIN_VERSION;
                $remote_version = ltrim($repository_data['tag_name'], 'v');
                
                if (version_compare($current_version, $remote_version, '<')) {
                    wp_send_json_success(array(
                        'has_update' => true,
                        'current_version' => $current_version,
                        'latest_version' => $remote_version,
                        'download_url' => $repository_data['zipball_url']
                    ));
                } else {
                    wp_send_json_success(array(
                        'has_update' => false,
                        'current_version' => $current_version,
                        'latest_version' => $remote_version
                    ));
                }
            } else {
                wp_send_json_error(array('message' => 'Unable to connect to GitHub repository or repository not found.'));
            }
        } else {
            wp_send_json_error(array('message' => 'GitHub updater not available.'));
        }
    }
} // End Class



function fetch_previous_events() {
    // Verify nonce
    if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'copy_previous_event')) {
        wp_send_json_error(array('message' => 'Security check failed.'));
        return;
    }

    global $wpdb;

    $event_id = intval($_POST['event_id']);
    if (!$event_id) {
        wp_send_json_error(array('message' => 'Invalid event ID.'));
        return;
    }

    // Get the event name
    $event = $wpdb->get_row($wpdb->prepare(
        "SELECT event_name FROM {$wpdb->prefix}sct_events WHERE id = %d",
        $event_id
    ));

    if (!$event) {
        wp_send_json_error(array('message' => 'Event not found.'));
        return;
    }

    // Fetch previous events with the same name
    $previous_events = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}sct_events WHERE event_name = %s AND id != %d",
        $event->event_name,
        $event_id
    ));

    if (empty($previous_events)) {
        wp_send_json_error(array('message' => 'No previous events found.'));
        return;
    }

    // Generate HTML for the modal
    ob_start();
    ?>
    <ul>
        <?php foreach ($previous_events as $prev_event): ?>
            <li>
                <strong><?php echo esc_html($prev_event->event_name); ?></strong> - 
                Date: <?php echo esc_html($prev_event->event_date); ?>
                <button class="button copy-event-data" 
                        data-original-event-id="<?php echo esc_attr($prev_event->id); ?>" 
                        data-nonce="<?php echo wp_create_nonce('copy_event_data'); ?>">
                    Copy
                </button>
            </li>
        <?php endforeach; ?>
    </ul>
    <?php
    $html = ob_get_clean();

    wp_send_json_success(array('html' => $html));
}
add_action('wp_ajax_fetch_previous_events', 'fetch_previous_events');

function copy_event_data() {
    // Verify nonce
    if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'copy_event_data')) {
        wp_send_json_error(array('message' => 'Security check failed.'));
        return;
    }

    global $wpdb;

    $original_event_id = intval($_POST['original_event_id']);
    $new_event_date = sanitize_text_field($_POST['new_event_date']);

    if (!$original_event_id || empty($new_event_date)) {
        wp_send_json_error(array('message' => 'Invalid data provided.'));
        return;
    }

    // Fetch the original event data
    $original_event = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}sct_events WHERE id = %d",
        $original_event_id
    ), ARRAY_A);

    if (!$original_event) {
        wp_send_json_error(array('message' => 'Original event not found.'));
        return;
    }

    // Remove the original event ID and update the date
    unset($original_event['id']);
    $original_event['event_date'] = $new_event_date;

    // Insert the new event
    $result = $wpdb->insert("{$wpdb->prefix}sct_events", $original_event);

    if ($result) {
        wp_send_json_success(array('message' => 'Event copied successfully.'));
    } else {
        wp_send_json_error(array('message' => 'Failed to copy event.'));
    }
}
add_action('wp_ajax_copy_event_data', 'copy_event_data');

function copy_event_by_name() {
    // Verify nonce
    if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'copy_event_nonce')) {
        wp_send_json_error(array('message' => 'Security check failed.'));
        return;
    }

    global $wpdb;

    $event_name = sanitize_text_field($_POST['event_name']);
    $new_event_date = sanitize_text_field($_POST['new_event_date']);

    if (empty($event_name) || empty($new_event_date)) {
        wp_send_json_error(array('message' => 'Invalid data provided.'));
        return;
    }

    // Fetch the most recent event with the given name
    $original_event = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}sct_events WHERE event_name = %s ORDER BY event_date DESC LIMIT 1",
        $event_name
    ), ARRAY_A);

    if (!$original_event) {
        wp_send_json_error(array('message' => 'Original event not found.'));
        return;
    }

    // Remove the original event ID and update the date
    unset($original_event['id']);
    $original_event['event_date'] = $new_event_date;

    // Insert the new event
    $result = $wpdb->insert("{$wpdb->prefix}sct_events", $original_event);

    if ($result) {
        wp_send_json_success(array('message' => 'Event copied successfully.'));
    } else {
        wp_send_json_error(array('message' => 'Failed to copy event.'));
    }
}
add_action('wp_ajax_copy_event_by_name', 'copy_event_by_name');

