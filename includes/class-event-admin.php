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
        
        $events = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sct_events 
            WHERE DATE(CONCAT(event_date, ' ', event_time)) >= %s
            ORDER BY event_date ASC, event_time ASC",
            $current_date
        ));
        
        include EVENT_ADMIN_PATH . 'admin/views/events-list.php';
    }

    // Render emails page
    public function sct_render_emails_page() {
        global $wpdb;

        $emails = $wpdb->get_results("
            SELECT 
                e.id,
                e.event_id,
                e.email_type,
                e.subject,
                e.message AS email_content,
                e.sent_date,
                e.status,
                ev.event_name,
                CASE 
                    WHEN e.email_type = 'mass_email' THEN 
                        CONCAT('Sent to ', COUNT(*), ' recipients')
                    ELSE reg.email
                END AS recipient_email,
                CASE 
                    WHEN e.email_type = 'mass_email' THEN 
                        GROUP_CONCAT(
                            CONCAT(reg.email, ':', e.status) 
                            ORDER BY reg.email ASC 
                            SEPARATOR '|'
                        )
                    ELSE NULL
                END AS all_recipients,
                CASE 
                    WHEN e.email_type = 'mass_email' THEN 
                        MIN(e.sent_date)
                    ELSE e.sent_date
                END AS grouped_sent_date,
                SUM(CASE WHEN e.status = 'failed' THEN 1 ELSE 0 END) as failed_count,
                COUNT(*) as total_count
            FROM {$wpdb->prefix}sct_event_emails e
            LEFT JOIN {$wpdb->prefix}sct_events ev ON e.event_id = ev.id
            LEFT JOIN {$wpdb->prefix}sct_event_registrations reg ON e.registration_id = reg.id
            GROUP BY 
                CASE 
                    WHEN e.email_type = 'mass_email' THEN 
                        CONCAT(
                            e.event_id, 
                            '_', 
                            e.subject, 
                            '_', 
                            FLOOR(UNIX_TIMESTAMP(e.sent_date)/300)
                        )
                    ELSE e.id
                END
            ORDER BY grouped_sent_date DESC
        ");

        // error_log(print_r($emails, true));

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
        if (in_array($current_screen->id, array('toplevel_page_event-admin', 'events_page_event-registrations', 'events_page_event-past'))) {
            wp_enqueue_script('datatables-js', 'https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js', array('jquery'), '1.11.5', true);
            wp_enqueue_style('datatables-css', 'https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css', array(), '1.11.5');
        }
        wp_enqueue_style('jquery-ui-css', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');

        // Enqueue admin styles and scripts last
        wp_enqueue_style('event-admin-style', EVENT_ADMIN_URL . 'admin/css/admin.css', array(), '1.0.0');
        wp_enqueue_script('event-admin-script', EVENT_ADMIN_URL . 'admin/js/admin.js', array('jquery', 'jquery-ui-accordion'), '1.0.0', true);

        wp_localize_script('event-admin-script', 'eventAdmin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'update_registration_nonce' => wp_create_nonce('update_registration_nonce'),
            'items_per_page' => $items_per_page,
            'export_nonce' => wp_create_nonce('export_nonce')
        ));
    }
    
    public function display_events_page() {
        global $wpdb;
        
        // Get only upcoming events
        $current_date = current_time('Y-m-d');
        
        $events = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sct_events 
            WHERE DATE(CONCAT(event_date, ' ', event_time)) >= %s
            ORDER BY event_date ASC, event_time ASC",
            $current_date
        ));
        
        include EVENT_ADMIN_PATH . 'admin/views/events-list.php';
    }

    public function display_registrations_page() {
        global $wpdb;
        $event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : null;
        $upcoming_events = $this->get_events('upcoming', $event_id);
        include EVENT_ADMIN_PATH . 'admin/views/registrations.php';
    }

    public function save_event() {
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
            'member_price' => floatval($_POST['member_price']),
            'non_member_price' => floatval($_POST['non_member_price']),
            'member_only' => isset($_POST['member_only']) ? 1 : 0,
            'children_counted_separately' => isset($_POST['children_counted_separately']) ? 1 : 0,
            'by_lottery' => isset($_POST['by_lottery']) ? 1 : 0,
            'custom_email_template' => wp_kses_post($_POST['custom_email_template']),
        );

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
            '%f', // member_price
            '%f', // non_member_price
            '%d', // member_only
            '%d',  // children_counted_separately
            '%d', // by_lottery
            '%s'  // custom_email_template
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
        
        error_log(print_r($_POST, true));

        $registration_id = intval($_POST['registration_id']);
        $member_guests = intval($_POST['member_guests']);
        $non_member_guests = intval($_POST['non_member_guests']);
        $children_guests = intval($_POST['children_guests']);
        $guest_count = $member_guests + $non_member_guests + $children_guests;
        
        if ($guest_count < 1) {
            wp_send_json_error(array('message' => 'Guest count must be at least 1'));
            return;
        }
        
        $registration = $wpdb->get_row($wpdb->prepare(
            "SELECT r.*, e.guest_capacity, e.member_price, e.non_member_price 
             FROM {$wpdb->prefix}sct_event_registrations r 
             JOIN {$wpdb->prefix}sct_events e ON r.event_id = e.id 
             WHERE r.id = %d",
            $registration_id
        ));
        
        if (!$registration) {
            wp_send_json_error(array('message' => 'Registration not found'));
            return;
        }
        
        // Calculate total guests for this event excluding current registration
        $total_guests = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(guest_count) 
             FROM {$wpdb->prefix}sct_event_registrations 
             WHERE event_id = %d AND id != %d",
            $registration->event_id,
            $registration_id
        ));
        
        $total_guests = intval($total_guests);
        
        // Check if new guest count would exceed capacity
        if ($registration->guest_capacity > 0 && ($total_guests + $guest_count) > $registration->guest_capacity) {
            wp_send_json_error(array(
                'message' => sprintf(
                    'Cannot update: guest count would exceed event capacity. Maximum available spots: %d',
                    $registration->guest_capacity - $total_guests
                )
            ));
            return;
        }
        
        // Update the registration
        $result = $wpdb->update(
            $wpdb->prefix . 'sct_event_registrations',
            array(
                'guest_count' => $guest_count,
                'member_guests' => $member_guests,
                'non_member_guests' => $non_member_guests,
                'children_guests' => $children_guests
            ),
            array('id' => $registration_id),
            array('%d', '%d', '%d', '%d'),
            array('%d')
        );
        
        if ($result !== false) {
            wp_send_json_success(array('message' => 'Registration updated successfully'));
        } else {
            wp_send_json_error(array('message' => 'Error updating registration'));
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

        // Delete associated emails first (to maintain referential integrity)
        $wpdb->delete(
            $wpdb->prefix . 'sct_event_emails',
            array('registration_id' => $registration_id),
            array('%d')
        );

        // Delete the registration
        $result = $wpdb->delete(
            $wpdb->prefix . 'sct_event_registrations',
            array('id' => $registration_id),
            array('%d')
        );

        if ($result === false) {
            wp_send_json_error(array('message' => 'Failed to delete registration'));
            return;
        }

        wp_send_json_success(array('message' => 'Registration deleted successfully'));
    }

    private function send_email($registration, $subject, $body_template, $headers, $is_mass_email, $sct_settings) {
        global $wpdb;

        // Calculate total price
        $total_price = ($registration->member_price * $registration->member_guests) + ($registration->non_member_price * $registration->non_member_guests);

        // Replace placeholders
        $placeholders = array(
            '{event_id}' => $registration->event_id,
            '{name}' => $registration->name,
            '{email}' => $registration->email,
            '{guest_count}' => $registration->guest_count,
            '{member_guests}' => $registration->member_guests,
            '{non_member_guests}' => $registration->non_member_guests,
            '{registration_date}' => date('F j, Y g:i A', strtotime($registration->registration_date)),
            '{event_name}' => $registration->event_name,
            '{event_date}' => date('F j, Y', strtotime($registration->event_date)),
            '{event_time}' => date('g:i A', strtotime($registration->event_time)),
            '{location_name}' => $registration->location_name,
            '{description}' => $registration->description,
            '{location_link}' => $registration->location_link,
            '{guest_capacity}' => $registration->guest_capacity,
            '{max_guests_per_registration}' => $registration->max_guests_per_registration,
            '{admin_email}' => $sct_settings['admin_email'],
            '{member_price}' => $registration->member_price,
            '{non_member_price}' => $registration->non_member_price,
            '{member_only}' => $registration->member_only ? 'Yes' : 'No',
            '{total_price}' => number_format($total_price, $sct_settings['currency_format']),
            '{children_counted_separately}' => $registration->children_counted_separately ? 'Yes' : 'No',
            '{by_lottery}' => $registration->by_lottery ? 'Yes' : 'No',
            '{children_guests}' => $registration->children_guests,
            '{currency_symbol}' => $sct_settings['currency_symbol'],
            '{currency_format}' => $sct_settings['currency_format'],
            '{registration_id}' => $registration->registration_id,
            '{registration_link}' => $sct_settings['event_registration_page'] . '?registration_id=' . $registration->registration_id,
            '{manage_link}' => $sct_settings['event_management_page'] . '?uid=' . $registration->unique_id
        );

        $personalized_body = str_replace(array_keys($placeholders), array_values($placeholders), $body_template);

        // Add calculation table if there is a member price or non-member price
        if ($registration->member_price > 0 || $registration->non_member_price > 0) {
            $calculation_table = '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse; width: 100%;">';
            $calculation_table .= '<thead><tr><th>Type</th><th>Quantity</th><th>Price</th><th>Total</th></tr></thead>';
            $calculation_table .= '<tbody>';
            if ($registration->member_price > 0) {
                $calculation_table .= sprintf(
                    '<tr><td>Members</td><td>%d</td><td>%s%.'.$sct_settings['currency_format'].'f</td><td>%s%.'.$sct_settings['currency_format'].'f</td></tr>',
                    $registration->member_guests,
                    $sct_settings['currency_symbol'],
                    $registration->member_price,
                    $sct_settings['currency_symbol'],
                    $registration->member_price * $registration->member_guests
                );
            }
            if ($registration->non_member_price > 0) {
                $calculation_table .= sprintf(
                    '<tr><td>Guests</td><td>%d</td><td>%s%.'.$sct_settings['currency_format'].'f</td><td>%s%.'.$sct_settings['currency_format'].'f</td></tr>',
                    $registration->non_member_guests,
                    $sct_settings['currency_symbol'],
                    $registration->non_member_price,
                    $sct_settings['currency_symbol'],
                    $registration->non_member_price * $registration->non_member_guests
                );
            }
            $calculation_table .= sprintf(
                '<tr><td colspan="3" style="text-align: right;"><strong>Total</strong></td><td><strong>%s%.'.$sct_settings['currency_format'].'f</strong></td></tr>',
                $sct_settings['currency_symbol'],
                $total_price
            );
            $calculation_table .= '</tbody></table>';

            $personalized_body .= '<br><br>' . $calculation_table;
        }

        // Send the email
        $sent = wp_mail(
            $registration->email,
            $subject,
            wpautop($personalized_body),
            $headers
        );

        if ($sent) {
            $wpdb->insert(
                $wpdb->prefix . 'sct_event_emails',
                array(
                    'registration_id' => $registration->registration_id,
                    'event_id' => $registration->event_id,
                    'email_type' => ($is_mass_email) ? 'mass_email' : 'individual_email',
                    'subject' => $subject,
                    'message' => $personalized_body,
                    'sent_date' => current_time('mysql'),
                    'status' => 'sent'
                ),
                array(
                    '%d', // registration_id
                    '%d', // event_id
                    '%s', // email_type
                    '%s', // subject
                    '%s', // message
                    '%s', // sent_date
                    '%s'  // status
                )
            );
            return true;
        } else {
            return false;
        }
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
            'confirmation_template' => $this->get_default_confirmation_template()
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
            'From: ' . get_bloginfo('name') . ' <events@' . $domain . '>',
            'Reply-To: ' . $admin_email
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
            ? "WHERE DATE(CONCAT(event_date, ' ', event_time)) >= %s"
            : "WHERE DATE(CONCAT(event_date, ' ', event_time)) < %s";
            
        $where_clause = ($event_id !== null)
            ? $where_clause . " AND id = ".$event_id
            : $where_clause;

        $sql = $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sct_events 
            {$where_clause}
            ORDER BY event_date $order, event_time $order",
            $current_date
        );
        
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
        return "New registration for {event_name}\n\n" .
               "Registration Details:\n" .
               "Name: {name}\n" .
               "Email: {email}\n" .
               "Number of Guests: {guest_count}\n" .
               "Registration Date: {registration_date}\n\n" .
               "Event Details:\n" .
               "Date: {event_date}\n" .
               "Time: {event_time}\n" .
               "Location: {location_name}";
    }

    public function getDefaultNotificationTemplate() {
        return $this->get_default_notification_template();
    }
    
    private function get_default_confirmation_template() {
        return "Dear {name},\n\n" .
               "Thank you for registering for {event_name}.\n\n" .
               "Registration Details:\n" .
               "Number of Guests: {guest_count}\n" .
               "Event Date: {event_date}\n" .
               "Event Time: {event_time}\n" .
               "Location: {location_name}\n\n" .
               "You can manage your registration here: {manage_link}\n\n" .
               "We look forward to seeing you!\n\n" .
               "Best regards,\n" .
               "The Event Team";
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
            'confirmation_template' => wp_kses_post($_POST['confirmation_template'])
        );
    
        update_option('event_admin_settings', $sct_settings);
    }

    public function handle_export_event_registrations() {
        if (!current_user_can('edit_posts')) {
            wp_die('Unauthorized access');
        }

        $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
        
        if (!$event_id) {
            wp_die('Invalid event ID');
        }

        // Verify nonce
        if (!wp_verify_nonce($_POST['_wpnonce'], 'export_event_registrations_' . $event_id)) {
            wp_die('Invalid nonce');
        }

        global $wpdb;

        // Get event details
        $event = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sct_events WHERE id = %d",
            $event_id
        ));

        if (!$event) {
            wp_die('Event not found');
        }

        // Get registrations
        $registrations = $wpdb->get_results($wpdb->prepare(
            "SELECT r.*, 
                    COUNT(e.id) as email_count,
                    MAX(e.sent_date) as last_email_sent
             FROM {$wpdb->prefix}sct_event_registrations r
             LEFT JOIN {$wpdb->prefix}sct_event_emails e ON r.id = e.registration_id
             WHERE r.event_id = %d 
             GROUP BY r.id
             ORDER BY r.registration_date ASC",
            $event_id
        ), ARRAY_A);

        // Set headers for CSV download
        $filename = sanitize_title($event->event_name) . '-registrations-' . date('Y-m-d') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Create output stream
        $output = fopen('php://output', 'w');
        
        // Add UTF-8 BOM for proper Excel handling of special characters
        fputs($output, "\xEF\xBB\xBF");

        // Add CSV title spanning all columns
        // $title = array($event->event_name . ' ' . date('Y-m-d', strtotime($event->event_date)) . ' ' . date('H:i', strtotime($event->event_time)));
        // fputcsv($output, $title);
        // fputcsv($output, array('')); // Empty row for spacing

        // Add registration headers
        $headers = array('Attendance', 'Name', 'Email', 'Members', 'Guests');
        if ($event->children_counted_separately) {
            $headers[] = 'Children';
        }
        if ($event->by_lottery) {
            $headers[] = 'Lottery';
        }
        $headers[] = 'Total Guests';
        if ($event->member_price > 0) {
            $headers[] = 'Member Guests (Price)';
        }
        if ($event->non_member_price > 0) {
            $headers[] = 'Non-Member Guests (Price)';
        }
        if ($event->member_price > 0 || $event->non_member_price > 0) {
            $headers[] = 'Total Price';
        }
        $headers[] = 'Remarks';
        fputcsv($output, $headers);

        // Add data rows
        foreach ($registrations as $registration) {
            $member_price = $event->member_price * $registration['member_guests'];
            $non_member_price = $event->non_member_price * $registration['non_member_guests'];
            $total_price = $member_price + $non_member_price;

            $row = array(
                '', // Checkbox for attendance
                $registration['name'],
                $registration['email'],
                $registration['member_guests'],
                $registration['non_member_guests']
            );

            if ($event->children_counted_separately) {
                $row[] = $registration['children_guests'];
            }

            if ($event->by_lottery) {
                $row[] = $registration['lottery'] ? 'Yes' : 'No';
            }

            $total_guests = $registration['member_guests'] + $registration['non_member_guests'] + ($event->children_counted_separately ? $registration['children_guests'] : 0);
            $row[] = $total_guests;

            if ($event->member_price > 0) {
                $row[] = "{$registration['member_guests']} ({$event->member_price}) = " . number_format($member_price, $sct_settings['currency_format']);
            }
            if ($event->non_member_price > 0) {
                $row[] = "{$registration['non_member_guests']} ({$event->non_member_price}) = " . number_format($non_member_price, $sct_settings['currency_format']);
            }
            if ($event->member_price > 0 || $event->non_member_price > 0) {
                $row[] = number_format($total_price, $sct_settings['currency_format']);
            }
            $row[] = ''; // Remarks field

            fputcsv($output, $row);
        }

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

            // Send the email
            $headers = array('Content-Type: text/plain; charset=UTF-8');
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
    
    public function update_children_guest_count() {
        check_ajax_referer('update_registration_nonce', 'security');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }

        global $wpdb;

        $registration_id = intval($_POST['registration_id']);
        $children_guests = intval($_POST['children_guests']);

        $result = $wpdb->update(
            $wpdb->prefix . 'sct_event_registrations',
            array('children_guests' => $children_guests),
            array('id' => $registration_id),
            array('%d'),
            array('%d')
        );

        if ($result !== false) {
            wp_send_json_success(array('message' => 'Children guest count updated successfully.'));
        } else {
            wp_send_json_error(array('message' => 'Failed to update children guest count.'));
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
        // Verify nonce
        if (!isset($_POST['add_registration_security']) || !wp_verify_nonce($_POST['add_registration_security'], 'add_registration_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
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
        $member_guests = isset($_POST['member_guests']) ? intval($_POST['member_guests']) : 0;
        $non_member_guests = isset($_POST['non_member_guests']) ?  intval($_POST['non_member_guests']) : 0;
        $children_guests = isset($_POST['children_guests']) ? intval($_POST['children_guests']) : 0;

        $result = $wpdb->insert(
            "{$wpdb->prefix}sct_event_registrations",
            array(
                'event_id' => $event_id,
                'name' => $name,
                'email' => $email,
                'member_guests' => $member_guests,
                'non_member_guests' => $non_member_guests,
                'children_guests' => $children_guests,
                'registration_date' => current_time('mysql')
            ),
            array(
                '%d', '%s', '%s', '%d', '%d', '%d', '%s'
            )
        );

        if ($result) {
            wp_send_json_success();
        } else {
            wp_send_json_error(array('message' => 'Failed to add registration'));
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
            $results = $wpdb->get_results("SELECT * FROM $table", ARRAY_A);
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

} // End Class

add_action('wp_ajax_update_children_guest_count', array('EventAdmin', 'update_children_guest_count'));
// add_filter('set-screen-option', array('EventAdmin', 'save_screen_options'), 10, 3);

// Hook into the WordPress export process
// add_action('admin_init', [new EventAdmin(), 'export_custom_tables']);

