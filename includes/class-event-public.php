<?php
class EventPublic {
    public function __construct() {
        add_shortcode('event_list', array($this, 'render_events_list'));
        add_shortcode('event_registration', array($this, 'render_event_registration'));
        add_shortcode('reservations_management', array($this, 'render_reservations_management'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));

        // Add AJAX handlers for both logged in and non-logged in users
        add_action('wp_ajax_register_event', array($this, 'process_registration'));
        add_action('wp_ajax_nopriv_register_event', array($this, 'process_registration'));
        add_action('wp_ajax_join_waiting_list', array($this, 'process_waiting_list_ajax'));
        add_action('wp_ajax_nopriv_join_waiting_list', array($this, 'process_waiting_list_ajax'));

        // add_action('wp_enqueue_scripts', array($this, 'enqueue_public_scripts'));
        add_action('wp_ajax_delete_reservation', array($this, 'delete_reservation'));
        add_action('wp_ajax_nopriv_delete_reservation', array($this, 'delete_reservation'));
    }

    public function enqueue_scripts() {
        global $post;
        
        // Enqueue block CSS if the block is used
        if (is_a($post, 'WP_Post') && has_blocks($post->post_content)) {
            wp_enqueue_style(
                'sct-event-list-block-styles',
                EVENT_ADMIN_URL . 'public/css/event-list-block.css',
                array(),
                EVENT_ADMIN_VERSION
            );
        }
        
        if (is_a($post, 'WP_Post') && 
            (has_shortcode($post->post_content, 'event_list') || 
             has_shortcode($post->post_content, 'event_registration') ||
             has_shortcode($post->post_content, 'reservations_management')
            )) {
            
            // Enqueue event registration form styles with theme CSS as dependency
            // This ensures our styles load AFTER the theme's UIKit CSS so overrides work
            wp_enqueue_style(
                'event-public-style',
                EVENT_ADMIN_URL . 'public/css/public.css',
                array('uikit-css-css', 'jss-classic-style-css'), // Load after theme CSS
                '1.0.0'
            );

            // wp_enqueue_script(
            //     'event-public-script',
            //     EVENT_ADMIN_URL . 'public/js/public.js',
            //     array('jquery'),
            //     '1.0.0',
            //     true
            // );

            wp_enqueue_script(
                'event-public-script',
                EVENT_ADMIN_URL . 'public/js/public.js',
                array('jquery'),
                EVENT_ADMIN_VERSION,
                true);

            // Enqueue UIKit form enhancer script
            wp_enqueue_script(
                'uikit-form-enhancer',
                EVENT_ADMIN_URL . 'public/js/uikit-form-enhancer.js',
                array(),
                EVENT_ADMIN_VERSION,
                true);

            $sct_settings = get_option('event_admin_settings', array(
                'currency_symbol' => '$',
                'currency_format' => 2,
            ));

            wp_localize_script(
                'event-public-script',
                'eventPublic',
                array(
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('event_registration_nonce'),
                    'currencySymbol' => isset($sct_settings['currency_symbol']) ? $sct_settings['currency_symbol'] : '$',
                    'currencyFormat' => isset($sct_settings['currency_format']) ? $sct_settings['currency_format'] : 2
                )
            );
        }
    }    

    // public function enqueue_public_scripts() {
    //     wp_enqueue_script('sct-event-public-js',
    //         EVENT_ADMIN_URL . 'public/js/public.js',
    //         array('jquery'),
    //         EVENT_ADMIN_VERSION,
    //         true);
    //     wp_localize_script('sct-event-public-js', 'sct_event_admin', array(
    //         'ajaxurl' => admin_url('admin-ajax.php'),
    //         'nonce' => wp_create_nonce('sct_event_admin_nonce')
    //     ));
    // }

    /**
     * Format event date and time range for display
     * 
     * @param object $event Event object with event_date, event_time, event_end_date, event_end_time
     * @return string Formatted date/time string
     */
    public static function format_event_date_range($event) {
        $has_end_date = !empty($event->event_end_date);
        $has_start_time = !empty($event->event_time) && $event->event_time !== '00:00:00';
        $has_end_time = !empty($event->event_end_time) && $event->event_end_time !== '00:00:00';

        $start_date = date('F j, Y', strtotime($event->event_date));
        
        // Multi-day event
        if ($has_end_date) {
            $end_date = date('F j, Y', strtotime($event->event_end_date));
            $date_range = $start_date . ' – ' . $end_date;
            
            // Add start time if provided
            if ($has_start_time) {
                $date_range .= ' at ' . date('g:i A', strtotime($event->event_time));
            } else {
                $date_range .= ' (all-day)';
            }
            
            return $date_range;
        }
        
        // Single-day event
        $date_str = $start_date;
        
        if ($has_start_time) {
            $date_str .= ' at ' . date('g:i A', strtotime($event->event_time));
            
            // Add end time if provided
            if ($has_end_time) {
                $date_str .= ' – ' . date('g:i A', strtotime($event->event_end_time));
            }
        }
        
        return $date_str;
    }

    public function render_events_list($atts) {
        global $wpdb;

        // Parse attributes with defaults
        $atts = shortcode_atts(array(
            'limit' => null, // Default to null for all events
        ), $atts);

        // Build the query - only show future events (events that haven't started yet)
        // Use COALESCE to handle multi-day events with NULL end_date
        $query = "SELECT * FROM {$wpdb->prefix}sct_events WHERE COALESCE(event_end_date, event_date) >= CURDATE()";
        
        $query .= " AND (publish_date IS NULL OR publish_date <= NOW())";
        // $query .= " AND (unpublish_date IS NULL OR unpublish_date > NOW())";
        $query .= " ORDER BY event_date ASC, event_time ASC";

        // Add LIMIT clause if limit parameter is set and is a positive number
        if (!is_null($atts['limit']) && is_numeric($atts['limit']) && $atts['limit'] > 0) {
            $query .= " LIMIT " . intval($atts['limit']);
        }

        // $query .= " AND (publish_date IS NULL OR publish_date <= NOW())";
        // $query .= " AND (unpublish_date IS !NULL OR unpublish_date > NOW())";

        error_log(print_r($query, true));
        
        // Get events
        $events = $wpdb->get_results($query);
        
        // $events = $wpdb->get_results(
        //     "SELECT * FROM {$wpdb->prefix}sct_events 
        //      WHERE event_date >= CURDATE() 
        //      ORDER BY event_date ASC"
        // );
        
        // Get the registration page ID from settings
        $settings = get_option('event_admin_settings', []);
        $registration_page_id = isset($settings['event_registration_page']) ? $settings['event_registration_page'] : null;

        // Get the management page ID from settings
        $management_page_id = isset($settings['event_management_page']) ? $settings['event_management_page'] : null;
        
        // If no registration page is set, use the current page
        if (!$registration_page_id) {
            $registration_page_id = get_the_ID();
        }
        
        // Get the base registration URL
        $registration_url = get_permalink($registration_page_id);
        $event_registration_url = get_permalink($registration_page_id);
        
        ob_start();
        include EVENT_ADMIN_PATH . 'public/views/events-list.php';
        return ob_get_clean();
    }
    
    public function process_waiting_list_ajax() {
        $event_id = intval($_POST['event_id']);
        $name = isset($_POST['waiting_list_name']) ? sanitize_text_field($_POST['waiting_list_name']) : '';
        $email = sanitize_email($_POST['waiting_list_email']);
        $people = isset($_POST['waiting_list_people']) ? intval($_POST['waiting_list_people']) : 1;
        $comment = isset($_POST['waiting_list_comment']) ? sanitize_textarea_field($_POST['waiting_list_comment']) : '';
        
        if (!$event_id || !$name || !$email || $people < 1) {
            wp_send_json_error(array('message' => 'Missing required fields.'));
            return;
        }
        
        global $wpdb;
        
        // Get event details
        $event = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sct_events WHERE id = %d",
            $event_id
        ));
        
        if (!$event || !$event->has_waiting_list) {
            wp_send_json_error(array('message' => 'Invalid event or waiting list not available.'));
            return;
        }
        
        // Send waiting list emails
        $this->send_waiting_list_emails($event, $name, $email, $people, $comment);
        
        wp_send_json_success(array('message' => 'Thank you! You have been added to the waiting list.'));
    }
    
    private function send_waiting_list_emails($event, $user_name, $user_email, $people_count, $comment = '') {
        $sct_settings = get_option('event_admin_settings', array(
            'admin_email' => get_option('admin_email')
        ));
        
        $admin_email = !empty($event->admin_email) ? $event->admin_email : $sct_settings['admin_email'];
        
        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <events@swissclubtokyo.com>'
        );
        
        // Send admin notification
        $admin_subject = 'Waiting List Request: ' . $event->event_name;
        $admin_message = "Someone has requested to join the waiting list for a fully booked event.\n\n";
        $admin_message .= "Event: {$event->event_name}\n";
        $admin_message .= "Date/Time: " . self::format_event_date_range($event) . "\n";
        $admin_message .= "Name: {$user_name}\n";
        $admin_message .= "Email: {$user_email}\n";
        $admin_message .= "Number of People: {$people_count}\n";
        if (!empty($comment)) {
            $admin_message .= "Comment: {$comment}\n";
        }
        $admin_message .= "\nPlease contact them if a spot becomes available.";
        
        wp_mail($admin_email, $admin_subject, $admin_message, $headers);
        $this->log_email($event->id, 'waiting_list', $admin_email, $admin_subject, $admin_message, 'sent');
        
        // Send confirmation to applicant
        $user_subject = 'Waiting List Confirmation: ' . $event->event_name;
        $user_message = "Dear {$user_name},\n\n";
        $user_message .= "Thank you for joining the waiting list for {$event->event_name}.\n\n";
        $user_message .= "Event Details:\n";
        $user_message .= "Date/Time: " . self::format_event_date_range($event) . "\n";
        $user_message .= "Location: {$event->location_name}\n";
        $user_message .= "Number of People: {$people_count}\n\n";
        $user_message .= "We will contact you if a spot becomes available.\n\n";
        $user_message .= "Best regards,\n";
        $user_message .= "The Event Team";
        
        wp_mail($user_email, $user_subject, $user_message, $headers);
        $this->log_email($event->id, 'waiting_list_confirmation', $user_email, $user_subject, $user_message, 'sent');
    }
    

    public function render_event_registration($atts) {
        // Get event ID from URL parameter first, then fallback to shortcode attribute
        $event_id = isset($_GET['id']) ? intval($_GET['id']) : (isset($atts['id']) ? intval($atts['id']) : 0);
        
        if (!$event_id) {
            return 'Event ID is required.';
        }
    
        global $wpdb;
        
        // Get event details
        $event = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sct_events WHERE id = %d",
            $event_id
        ));
    
        if (!$event) {
            return 'Event not found.';
        }
        
        // Check if event has already started
        $event_datetime = $event->event_date . ' ' . $event->event_time;
        if (strtotime($event_datetime) < time()) {
            return '<div class="event-past"><h2>' . esc_html($event->event_name) . '</h2><p>This event has already started and registration is no longer available.</p></div>';
        }
    
        // Get current registration count
        $registered_guests = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(guest_count) FROM {$wpdb->prefix}sct_event_registrations WHERE event_id = %d",
            $event->id
        ));
        
        $registered_guests = $registered_guests ?: 0;
        $remaining_capacity = $event->guest_capacity - $registered_guests;

        // Check if the user is a member
        $is_member = is_user_logged_in() && current_user_can('member'); // Adjust this condition based on your membership logic
        // $price = $is_member ? $event->member_price : $event->non_member_price;
    
        ob_start();
        include EVENT_ADMIN_PATH . 'public/views/event-registration.php';
        return ob_get_clean();
    }

    public function render_reservations_management() {
        // Get the unique identifier from the URL
        if (isset($_GET['uid'])) {
            $unique_id = sanitize_text_field($_GET['uid']);
            global $wpdb;
    
            // Fetch the reservation using the unique identifier
            $reservation = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}sct_event_registrations WHERE unique_identifier = %s",
                $unique_id
            ));
    
            if ($reservation) {
                $event = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}sct_events WHERE id = %d",
                    $reservation->event_id
                ));
                ob_start();
                include EVENT_ADMIN_PATH . 'public/views/manage-reservation.php';
                return ob_get_clean();
            } else {
                // echo $_GET['uid'];
                return 'No reservation found.';
                // return 'Invalid reservation identifier.';
            }
        } else {
            return 'No reservation identifier provided.';
        }
    }

    public function process_registration() {
        try {
            check_ajax_referer('event_registration_nonce', 'nonce');

            global $wpdb;

            // Debug incoming data
            error_log('Registration data received: ' . print_r($_POST, true));

            $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
            $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
            $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
            $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
            $company_name = isset($_POST['company_name']) ? sanitize_text_field($_POST['company_name']) : '';
            $address = isset($_POST['address']) ? sanitize_text_field($_POST['address']) : '';
            $city = isset($_POST['city']) ? sanitize_text_field($_POST['city']) : '';
            $postal_code = isset($_POST['postal_code']) ? sanitize_text_field($_POST['postal_code']) : '';
            $country = isset($_POST['country']) ? sanitize_text_field($_POST['country']) : '';
            $comments = isset($_POST['comments']) && is_array($_POST['comments']) ? array_map('sanitize_text_field', $_POST['comments']) : array();
            $guest_details = isset($_POST['guest_details']) ? $_POST['guest_details'] : null;
            $goods_services = isset($_POST['goods_services']) ? $_POST['goods_services'] : null; // Handle goods/services
            $guest_count = isset($_POST['guest_count']) ? intval($_POST['guest_count']) : 0;
            $payment_method = isset($_POST['payment_method']) ? sanitize_text_field($_POST['payment_method']) : '';
            $payment_status = 'pending'; // Default to pending

            // Get event details
            $event = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}sct_events WHERE id = %d",
                $event_id
            ));

            if (!$event) {
                error_log('Event not found: ' . $event_id);
                wp_send_json_error(array('message' => 'Event not found.'));
                return;
            }

            // Validate required fields
            // if (!$event_id || !$name || !$email || (empty($guest_details) && $guest_count < 1)) {
            //     error_log('Missing required fields');
            //     wp_send_json_error(array('message' => 'All fields are required.'));
            //     return;
            // }

            // Calculate total guests
            $total_guests = 0;
            if (!empty($guest_details)) {
                $processed_guest_details = array_map(function ($detail) {
                    return isset($detail['count']) ? intval($detail['count']) : 0;
                }, $guest_details);

                foreach ($guest_details as $detail) {
                    $total_guests += intval($detail['count']);
                }
            } else {
                $total_guests = $guest_count;
            }

            if ($total_guests < 1) {
                error_log('Guest count must be at least 1');
                wp_send_json_error(array('message' => 'Guest count must be at least 1'));
                return;
            }

            error_log('Full POST data: ' . print_r($_POST, true));
            error_log('Goods Service from POST: ' . print_r($_POST['goods_services'] ?? 'NOT SET', true));
            error_log('Goods Service variable: ' . $goods_services);
            // Process goods/services - build proper array with all details
            if (!empty($_POST['goods_services']) && is_array($_POST['goods_services'])) {
                error_log('Processing goods_services array...');
                $processed_goods_services = array_map(function ($service) {
                    error_log('Service data: ' . print_r($service, true));
                    // Handle checkbox values - "on" or "1" should become 1, empty strings/0 should be 0
                    $count = isset($service['count']) ? $service['count'] : 0;
                    if ($count === 'on' || $count === '1') {
                        $count = 1;
                    } else {
                        $count = intval($count);
                    }
                    return [
                        'name' => isset($service['name']) ? sanitize_text_field($service['name']) : '',
                        'price' => isset($service['price']) ? floatval($service['price']) : 0,
                        'count' => $count,
                    ];
                }, $_POST['goods_services']);
                error_log('Processed goods services: ' . print_r($processed_goods_services, true));
            } else {
                error_log('No goods_services or not an array');
                $processed_goods_services = array();
            }


            // Serialize guest details and goods/services for storage
            // $guest_details_serialized = maybe_serialize($processed_guest_details);
            $guest_details_serialized = maybe_serialize($guest_details);
            $goods_services_serialized = maybe_serialize($processed_goods_services);

            // Prepare registration data
            $registration_data = array(
                'event_id' => $event_id,
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'company_name' => $company_name,
                'address' => $address,
                'city' => $city,
                'postal_code' => $postal_code,
                'country' => $country,
                'comments' => !empty($comments) ? maybe_serialize($comments) : null,
                'guest_details' => $guest_details_serialized,
                'goods_services' => $goods_services_serialized,
                'guest_count' => $total_guests,
                'payment_method' => $payment_method,
                'payment_status' => $payment_status,
                'registration_date' => current_time('mysql'),
                'unique_identifier' => wp_generate_uuid4()
            );

            // Debug registration data
            error_log('Attempting to insert registration: ' . print_r($registration_data, true));

            // Insert registration
            $result = $wpdb->insert(
                $wpdb->prefix . 'sct_event_registrations',
                $registration_data,
                array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s')
            );

            if ($result === false) {
                // error_log('Database error: ' . $wpdb->last_error);
                if (strpos($wpdb->last_error, 'Duplicate entry') !== false) {
                    // error_log('Database error: ' . $wpdb->last_error);
                    wp_send_json_error(array('message' => 'duplicate entry'));
                } else {
                    wp_send_json_error(array('message' => 'Registration failed. Please try again.'));
                }
                return;
            }

            $registration_id = $wpdb->insert_id;

            $registration_data['registration_id'] = $registration_id;

            // Prepare event data for emails
            $event_data = array(
                'registration_id' => $registration_id,
                'event_id' => $event->id,
                'event_name' => $event->event_name,
                'event_date' => $event->event_date,
                'event_time' => $event->event_time,
                'location_name' => esc_html(stripslashes($event->location_name)),
                'admin_email' => $event->admin_email,
                'unique_identifier' => $registration_data['unique_identifier']
            );

            // Send confirmation and notification emails
            $this->send_registration_emails($registration_data, $event_data);

            wp_send_json_success(array(
                'message' => 'You will receive a confirmation email shortly. Please check your inbox.<br><br>
                 <span class="uk-text-muted uk-text-small uk-text-italic">
                 If you do not receive it, please check your spam folder or contact us at<br>
                 <a href="mailto:' . esc_html($event->admin_email) . '">' . esc_html($event->admin_email) . '</a></span>'
                
            ));

        } catch (Exception $e) {
            error_log('Registration error: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'An unexpected error occurred.'));
        }
    }

    // Add this function to both class-event-admin.php and class-event-public.php, or in a shared file

    private function send_registration_emails($registration_data, $event_data) {
        $sct_settings = get_option('event_admin_settings', array(
            'admin_email' => get_option('admin_email'),
            'notification_subject' => 'New Event Registration: {event_name}',
            'notification_template' => $this->get_default_notification_template(),
            'confirmation_subject' => 'Registration Confirmation: {event_name}',
            'confirmation_template' => $this->get_default_confirmation_template()
        ));

        global $wpdb;

        // Fetch event details
        $event_data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sct_events WHERE id = %d",
            $registration_data['event_id']
        ), ARRAY_A);

        // Use custom email template if available, and repair any corruption
        $confirmation_template = !empty($event_data['custom_email_template']) 
            ? self::repair_corrupted_template($event_data['custom_email_template']) 
            : $sct_settings['confirmation_template'];
        $notification_template = $sct_settings['notification_template'];

        // Prepare data for placeholder replacement
        $placeholder_data = array_merge($registration_data, $event_data);

        // Map database field names to placeholder-compatible names
        // This allows templates to use both database names and placeholder system names
        $placeholder_data['event_title'] = $placeholder_data['event_name'] ?? '';
        $placeholder_data['event.title'] = $placeholder_data['event_name'] ?? '';
        $placeholder_data['attendee_name'] = $placeholder_data['name'] ?? '';
        $placeholder_data['attendee_email'] = $placeholder_data['email'] ?? '';
        $placeholder_data['attendee_guest_count'] = $placeholder_data['guest_count'] ?? '';
        $placeholder_data['registration_id'] = $placeholder_data['registration_id'] ?? $placeholder_data['id'] ?? '';
        $placeholder_data['id'] = $placeholder_data['registration_id'] ?? $placeholder_data['id'] ?? '';
        $placeholder_data['registration_name'] = $placeholder_data['name'] ?? '';
        $placeholder_data['registration_email'] = $placeholder_data['email'] ?? '';
        $placeholder_data['registration_date'] = $placeholder_data['registration_date'] ?? current_time('F j, Y');
        $placeholder_data['location.name'] = $placeholder_data['location_name'] ?? '';
        $placeholder_data['website_name'] = get_bloginfo('name');
        $placeholder_data['website_url'] = get_bloginfo('url');
        $placeholder_data['current_date'] = current_time('F j, Y');
        $placeholder_data['current_year'] = date('Y');
        $placeholder_data['date.year'] = date('Y');
        $placeholder_data['admin_email'] = $event_data['admin_email'] ?? get_option('admin_email');
        
        // Add additional fields dynamically if they exist
        $placeholder_data = $this->enrich_additional_fields($placeholder_data, $registration_data, $event_data);

        // Initialize pricing breakdown and total price
        $pricing_breakdown = '';
        $total_price = 0;
        $has_rows = false;

        $currency_symbol = isset($sct_settings['currency_symbol']) ? $sct_settings['currency_symbol'] : '$';
        $currency_format = isset($sct_settings['currency_format']) ? intval($sct_settings['currency_format']) : 2;

        $pricing_rows = '';

        $settings = get_option('event_admin_settings', []);
        $management_page_id = isset($settings['event_management_page']) ? $settings['event_management_page'] : null;
        $placeholder_data['reservation_link'] = add_query_arg(array(
            'uid' => $registration_data['unique_identifier']
        ), get_permalink($management_page_id));

        // Process pricing options
        if (!empty($registration_data['guest_details'])) {
            $guest_details = maybe_unserialize($registration_data['guest_details']);
            foreach ($guest_details as $detail) {
                $count = intval($detail['count']);
                $name = esc_html($detail['name']);
                $price = floatval($detail['price']);
                $total = $count * $price;
                if ($count > 0) {
                    $has_rows = true;
                    $pricing_rows .= sprintf(
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

        // Process goods/services
        if (!empty($registration_data['goods_services'])) {
            $goods_services = maybe_unserialize($registration_data['goods_services']);
            foreach ($goods_services as $service) {
                $count = intval($service['count']);
                $name = esc_html($service['name']);
                $price = floatval($service['price']);
                $total = $count * $price;
                if ($count > 0) {
                    $has_rows = true;
                    $pricing_rows .= sprintf(
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

        // Only show the table if there are any rows
        if ($has_rows) {
            $pricing_breakdown = '<table style="width: 100%; border-collapse: collapse; margin-top: 10px;">';
            $pricing_breakdown .= '<thead><tr><th style="border: 1px solid #ddd; padding: 8px;">&nbsp;</th><th style="border: 1px solid #ddd; padding: 8px;">Count</th><th style="border: 1px solid #ddd; padding: 8px;">Price</th><th style="border: 1px solid #ddd; padding: 8px;">Total</th></tr></thead>';
            $pricing_breakdown .= '<tbody>' . $pricing_rows;
            $pricing_breakdown .= sprintf(
                '<tr><td colspan="3" style="border-top: 1px solid #ddd; padding: 8px; text-align: right;"><strong>Total</strong></td><td style="border-top: 1px solid #ddd; border-bottom: 2px solid #ddd; padding: 8px;"><strong>%s %s</strong></td></tr>',
                esc_html($currency_symbol),
                number_format($total_price, $currency_format)
            );
            $pricing_breakdown .= '</tbody></table>';
        } elseif (empty($registration_data['guest_details']) && $registration_data['guest_count'] > 0) {
            $pricing_breakdown = '<strong>Number of Guests:</strong> ' . $registration_data['guest_count'];
        }

        $placeholder_data['pricing_overview'] = $pricing_breakdown;
        $placeholder_data['total_price'] = sprintf('%s %s', esc_html($currency_symbol), number_format($total_price, $currency_format));

        // Always show payment method if a method is selected and total price > 0 (from pricing_options or goods_services)
        $payment_methods = maybe_unserialize($event_data['payment_methods']);
        if (empty($payment_methods)) {
            $payment_methods = array();
        }
        $selected_payment_method = array_filter($payment_methods, function ($method) use ($registration_data) {
            return $method['type'] === $registration_data['payment_method'];
        });
        if (!empty($selected_payment_method) && $total_price > 0) {
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

            $placeholder_data['payment_method_details'] = $this->replace_email_placeholders(
                $placeholder_data['payment_method_details'],
                $placeholder_data
            );
        }


        // Replace placeholders in the email templates
        $confirmation_subject = $this->replace_email_placeholders(
            $sct_settings['confirmation_subject'],
            $placeholder_data
        );
        $confirmation_message = $this->replace_email_placeholders(
            $confirmation_template,
            $placeholder_data
        );
        
        // === CLEAN UP UNREPLACED PLACEHOLDERS ===
        // Remove any placeholders that weren't replaced (empty values)
        $confirmation_message = $this->clean_empty_placeholders($confirmation_message);

        $notification_subject = $this->replace_email_placeholders(
            $sct_settings['notification_subject'],
            $placeholder_data
        );
        $notification_message = $this->replace_email_placeholders(
            $notification_template,
            $placeholder_data
        );
        
        // === CLEAN UP UNREPLACED PLACEHOLDERS ===
        // Remove any placeholders that weren't replaced (empty values)
        $notification_message = $this->clean_empty_placeholders($notification_message);

        // Add the reservation link to the confirmation email
        $settings = get_option('event_admin_settings', []);
        $management_page_id = isset($settings['event_management_page']) ? $settings['event_management_page'] : null;
        $reservation_url = add_query_arg(array(
            'action' => 'manage_reservation',
            'uid' => $registration_data['unique_identifier']
        ), get_permalink($management_page_id));

        // Create HTML version with proper link
        $confirmation_message_html = str_replace(
            array('{{reservation_link}}', '{reservation_link}'),
            '<a href="' . esc_url($reservation_url) . '">Manage your registration</a>',
            $confirmation_message
        );

        // Detect if this is an HTML template or plain text
        // HTML templates contain HTML tags and should not be wrapped or processed with wpautop()
        $is_html_template = preg_match('/<[a-z]+.*?>/i', $confirmation_message_html);
        
        if ($is_html_template) {
            // This is an HTML template - use it as-is without wrapping
            // No safe_autop() conversion, no CSS wrapper added
            // The template already contains complete HTML structure
        } else {
            // This is plain text - add HTML wrapper and styling
            $confirmation_css_path = EVENT_ADMIN_PATH . 'admin/templates/confirmation_css.html';

            // Check if the file exists before reading it
            if (file_exists($confirmation_css_path)) {
                $confirmation_css_content = file_get_contents($confirmation_css_path);
            } else {
                $confirmation_css_content = '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Registration Confirmed!</title></head><body><div class="email-container">'; // Fallback
            }

            $confirmation_message_html = $this->safe_autop($confirmation_message_html);
            $confirmation_message_html = $confirmation_css_content . ($confirmation_message_html) . '</div></body></html>';
        }

        $notification_message_html = nl2br($notification_message);
        $notification_message_html = '<html><head><meta charset="UTF-8"></head><body>' . $notification_message_html . '</body></html>';

        // Determine reply-to address for confirmation email
        $reply_to_email = !empty($event_data['admin_email']) ? $event_data['admin_email'] : $sct_settings['admin_email'];

        // Get the domain of the WP site
        $site_url = get_bloginfo('url');
        $parsed_url = parse_url($site_url);
        $wp_domain = isset($parsed_url['host']) ? $parsed_url['host'] : '';
        $wp_domain = preg_replace('/^www\./', '', $wp_domain);

        // Check if reply-to is on the same domain
        $reply_to_domain = substr(strrchr($reply_to_email, "@"), 1);
        $from_email = $sct_settings['admin_email'];
        if (strcasecmp($reply_to_domain, $wp_domain) !== 0) {
            $from_email = 'event@' . $wp_domain;
        }

        // For notification email, always use WP admin email as from
        $wp_admin_email = get_option('admin_email');
        $notification_to = !empty($event_data['admin_email']) ? $event_data['admin_email'] : $sct_settings['admin_email'];


        $from_email = 'events@swissclubtokyo.com';

        // Generate DKIM signature
        $dkim_header = $this->generate_dkim_signature($confirmation_subject, $from_email, $registration_data['email'], $confirmation_message_html);

        $confirmation_headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . $from_email . '>',
            'Reply-To: ' . $from_email,
            'Return-Path: events@swissclubtokyo.com'
        );
        if ($dkim_header) {
            $confirmation_headers[] = $dkim_header;
        }

        $notification_dkim_header = $this->generate_dkim_signature($notification_subject, $from_email, $notification_to, $notification_message_html);

        $notification_headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: Event @ ' . get_bloginfo('name') . ' <' . $from_email . '>',
            'Return-Path: events@swissclubtokyo.com'
        );
        if ($notification_dkim_header) {
            $notification_headers[] = $notification_dkim_header;
        }

        // Send the confirmation email
        $confirmation_sent = wp_mail(
            $registration_data['email'],
            $confirmation_subject,
            $confirmation_message_html,
            $confirmation_headers
        );
        error_log('Confirmation email data: ' . $confirmation_to . ' | ' . $confirmation_subject . ' | ' . $confirmation_message_html);
        error_log('Confirmation email headers: ' . print_r($confirmation_headers, true));

        $this->log_email($event_data['id'], 'confirmation', $registration_data['email'], $confirmation_subject, $confirmation_message_html, $confirmation_sent ? 'sent' : 'failed');

        // Send the admin notification email
        $notification_sent = wp_mail(
            $notification_to,
            $notification_subject,
            $notification_message_html,
            $notification_headers
        );
        $this->log_email($event_data['id'], 'notification', $sct_settings['admin_email'], $notification_subject, $notification_message_html, $notification_sent ? 'sent' : 'failed');

        // Log email statuses
        error_log('Confirmation email ' . ($confirmation_sent ? 'sent' : 'failed') . ' for: ' . $registration_data['email']);
        error_log('Notification email ' . ($notification_sent ? 'sent' : 'failed') . ' to admin: ' . $sct_settings['admin_email']);

        // Log email in the database
    }

    /**
     * Enrich placeholder data with additional fields
     * 
     * Maps event-specific additional fields to simple placeholder names
     * like {{additional_field_1}}, {{whole_table_booking}}, etc.
     * 
     * @param array $placeholder_data
     * @param array $registration_data
     * @param array $event_data
     * @return array
     */
    private function enrich_additional_fields($placeholder_data, $registration_data, $event_data) {
        // Unserialize comments field which contains additional field values
        $comments = $registration_data['comments'] ?? null;
        if (empty($comments)) {
            return $placeholder_data;
        }
        
        // Unserialize the comments field
        $additional_values = maybe_unserialize($comments);
        if (!is_array($additional_values)) {
            return $placeholder_data;
        }
        
        // Get field definitions from event data
        $comment_fields = $event_data['comment_fields'] ?? null;
        if (empty($comment_fields)) {
            return $placeholder_data;
        }
        
        // Decode comment_fields if it's a JSON string
        if (is_string($comment_fields)) {
            $comment_fields = json_decode($comment_fields, true);
        }
        
        if (!is_array($comment_fields)) {
            return $placeholder_data;
        }
        
        // Create a map of field ID to field label
        $field_labels = array();
        foreach ($comment_fields as $field) {
            if (!empty($field['id']) && !empty($field['label'])) {
                $field_labels[$field['id']] = $field['label'];
            }
        }
        
        // Add all additional fields to placeholder_data with both ID and label-based keys
        $field_counter = 1;
        foreach ($additional_values as $field_id => $field_value) {
            if (!empty($field_value)) {
                // Get the label for this field
                $label = $field_labels[$field_id] ?? null;
                
                // Add numbered placeholder: {{additional_field_1}}, {{additional_field_2}}, etc.
                $placeholder_data['additional_field_' . $field_counter] = $field_value;
                
                // If we have a label, also add label-based placeholder
                if (!empty($label)) {
                    $label_key = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $label));
                    $label_key = trim($label_key, '_');
                    $placeholder_data[$label_key] = $field_value;
                    $placeholder_data['additional_' . $label_key] = $field_value;
                }
                
                // Also store with field ID as key for direct access
                $placeholder_data[$field_id] = $field_value;
                
                $field_counter++;
            }
        }
        
        // Store the additional values for use in generating the {{additional_fields}} placeholder
        $placeholder_data['_additional_values'] = $additional_values;
        $placeholder_data['_field_labels'] = $field_labels;
        
        return $placeholder_data;
    }

    private function log_email($event_id, $email_type, $recipient_email, $subject, $message, $status = 'sent') {
        return SCT_Event_Email_Utilities::log_email($event_id, $email_type, $recipient_email, $subject, $message, $status);
    }

    private function generate_dkim_signature($subject, $from_email, $to_email, $body) {
        return SCT_Event_Email_Utilities::generate_dkim_signature($subject, $from_email, $to_email, $body);
    }

    /**
     * Apply autop but preserve placeholder syntax
     * Regular wpautop() can break {{placeholders}} by removing braces
     *
     * @param string $text Text to apply autop to
     * @return string Text with paragraphs added and placeholders preserved
     */
    private function safe_autop($text) {
        // First, temporarily replace all placeholders with markers
        $placeholders = array();
        $marker_counter = 0;
        
        // Match both {{}} and {} placeholder formats
        $text = preg_replace_callback('/\{\{[^}]*\}\}|\{[^}]*\}/', function($matches) use (&$placeholders, &$marker_counter) {
            $placeholder_key = '___PLACEHOLDER_' . $marker_counter . '___';
            $placeholders[$placeholder_key] = $matches[0];
            $marker_counter++;
            return $placeholder_key;
        }, $text);
        
        // Now apply wpautop safely
        $text = wpautop($text);
        
        // Restore all placeholders
        foreach ($placeholders as $key => $placeholder) {
            $text = str_replace($key, $placeholder, $text);
        }
        
        return $text;
    }
    
    /**
     * IMPROVED: Simplified email placeholder replacement
     * Handles both old {format} and new {{format}} directly without complex callbacks
     * This is more reliable for email generation
     *
     * @param string $template Template with placeholders
     * @param array $data Data to replace placeholders with
     * @return string Template with placeholders replaced
     */
    private function replace_email_placeholders($template, $data) {
        // Build a comprehensive mapping of all possible placeholder keys
        $replacements = array();
        
        // Helper function to safely get value as string
        $get_value = function($key) use ($data) {
            if (!isset($data[$key])) {
                return '';
            }
            $value = $data[$key];
            if (is_array($value) || is_object($value)) {
                return '';
            }
            return (string)$value;
        };
        
        // === SIMPLE FLAT KEY REPLACEMENTS ===
        // These handle direct database field names
        // ONLY add entries that actually have values
        $simple_fields = array(
            'event_name', 'event_title', 'event_date', 'event_time', 'event_description',
            'location_name', 'location_link', 'location_url',
            'name', 'email', 'phone', 'company_name', 'address', 'city', 'postal_code', 'country',
            'guest_count', 'payment_method', 'payment_status',
            'registration_id', 'registration_date', 'unique_identifier',
            'website_name', 'website_url', 'admin_email', 'current_date', 'current_year',
            'total_price', 'pricing_overview', 'payment_method_details', 'reservation_link',
            'attendee_name', 'attendee_email', 'attendee_guest_count', 'registration_name', 'registration_email'
        );
        
        foreach ($simple_fields as $field) {
            $value = $get_value($field);
            if (!empty($value)) {
                // Old format: {field_name}
                $replacements['{' . $field . '}'] = $value;
                // New format: {{field_name}}
                $replacements['{{' . $field . '}}'] = $value;
            }
        }
        
        // === COMPLEX HTML FIELDS - Explicit Handling ===
        // These fields contain HTML that needs special handling
        // Ensure they are ALWAYS replaced, even if empty
        $html_fields = array('pricing_overview', 'payment_method_details', 'pricing_breakdown', 'goods_services_breakdown');
        
        foreach ($html_fields as $field) {
            if (isset($data[$field])) {
                $value = $data[$field];
                // For HTML fields, cast to string but don't skip empties
                // This ensures the placeholder is replaced even with empty content
                $value_str = (string)$value;
                if (!empty($value_str)) {
                    $replacements['{' . $field . '}'] = $value_str;
                    $replacements['{{' . $field . '}}'] = $value_str;
                } else {
                    // Replace empty HTML fields with empty string (effectively removing the placeholder)
                    $replacements['{' . $field . '}'] = '';
                    $replacements['{{' . $field . '}}'] = '';
                }
            }
        }
        
        // === ADDITIONAL FIELDS (Dynamic) ===
        // These come from custom field responses and need special handling
        foreach ($data as $key => $value) {
            if (strpos($key, 'additional_field_') === 0) {
                if (!is_array($value) && !is_object($value)) {
                    $value_str = (string)$value;
                    if (!empty($value_str)) {
                        $replacements['{{' . $key . '}}'] = $value_str;
                        $replacements['{' . $key . '}'] = $value_str;
                    }
                }
            }
        }
        
        // === ADDITIONAL_FIELDS SUMMARY ===
        // Build a comprehensive list of all additional fields for the {{additional_fields}} placeholder
        $additional_fields_html = '';
        $additional_fields_text = '';
        
        // Get additional field values and labels from enriched data
        $additional_values = $data['_additional_values'] ?? array();
        $field_labels = $data['_field_labels'] ?? array();
        
        // Build the additional fields output from the values and labels
        foreach ($additional_values as $field_id => $value) {
            if (!empty($value)) {
                // Get label for this field from the field_labels map
                $label = $field_labels[$field_id] ?? ucwords(str_replace('_', ' ', $field_id));
                $value_str = (string)$value;
                
                // HTML format
                $additional_fields_html .= '<div class="detail-row">' . "\n";
                $additional_fields_html .= '                    <span class="detail-label">' . esc_html($label) . '</span>' . "\n";
                $additional_fields_html .= '                    <span class="detail-value">' . esc_html($value_str) . '</span>' . "\n";
                $additional_fields_html .= '                </div>' . "\n";
                
                // Text format
                $additional_fields_text .= $label . ': ' . $value_str . "\n";
            }
        }
        
        if (!empty($additional_fields_html)) {
            // Wrap in the section tags for display
            $additional_fields_html = '            <!-- Your Preferences Section -->' . "\n" .
                                     '            <div class="section">' . "\n" .
                                     '                <div class="section-title">Your Preferences</div>' . "\n" .
                                     $additional_fields_html .
                                     '            </div>' . "\n";
            
            $replacements['{{additional_fields}}'] = $additional_fields_html;
            $replacements['{{additional.fields}}'] = $additional_fields_html;
            $replacements['{additional_fields}'] = $additional_fields_html;
        } else {
            // No additional fields - replace placeholder with empty string
            $replacements['{{additional_fields}}'] = '';
            $replacements['{{additional.fields}}'] = '';
            $replacements['{additional_fields}'] = '';
        }
        
        if (!empty($additional_fields_text)) {
            $replacements['{{additional_fields_text}}'] = $additional_fields_text;
            $replacements['{additional_fields_text}'] = $additional_fields_text;
        }
        
        // === NESTED FIELD REPLACEMENTS ===
        // These map nested placeholders to data values
        
        // Event nested
        $event_title = $get_value('event_title') ?: $get_value('event_name');
        if (!empty($event_title)) {
            $replacements['{{event.title}}'] = $event_title;
            $replacements['{{event.name}}'] = $event_title;
        }
        
        $event_date = $get_value('event_date');
        if (!empty($event_date)) {
            $replacements['{{event.date}}'] = $event_date;
        }
        
        $event_time = $get_value('event_time');
        if (!empty($event_time)) {
            $replacements['{{event.time}}'] = $event_time;
        }
        
        $event_desc = $get_value('event_description') ?: $get_value('description');
        if (!empty($event_desc)) {
            $replacements['{{event.description}}'] = $event_desc;
        }
        
        // Attendee nested
        $attendee_name = $get_value('attendee_name') ?: $get_value('name');
        if (!empty($attendee_name)) {
            $replacements['{{attendee.name}}'] = $attendee_name;
            $replacements['{{registration.name}}'] = $attendee_name;
        }
        
        $attendee_email = $get_value('attendee_email') ?: $get_value('email');
        if (!empty($attendee_email)) {
            $replacements['{{attendee.email}}'] = $attendee_email;
            $replacements['{{registration.email}}'] = $attendee_email;
        }
        
        $guest_count = $get_value('attendee_guest_count') ?: $get_value('guest_count');
        if (!empty($guest_count)) {
            $replacements['{{attendee.guest_count}}'] = $guest_count;
            $replacements['{{people_count}}'] = $guest_count;
        }
        
        $registration_date = $get_value('registration_date');
        if (!empty($registration_date)) {
            $replacements['{{registration.date}}'] = $registration_date;
        }
        
        $registration_id = $get_value('registration_id') ?: $get_value('id');
        if (!empty($registration_id)) {
            $replacements['{{registration.id}}'] = $registration_id;
        }
        
        // Location nested
        $location_name = $get_value('location_name');
        if (!empty($location_name)) {
            $replacements['{{location.name}}'] = $location_name;
        }
        
        $location_link = $get_value('location_link') ?: $get_value('location_url');
        if (!empty($location_link)) {
            $replacements['{{location.link}}'] = $location_link;
            $replacements['{{location.url}}'] = $location_link;
        }
        
        // Payment nested
        $total_price = $get_value('total_price');
        if (!empty($total_price)) {
            $replacements['{{payment.total}}'] = $total_price;
        }
        
        $payment_method = $get_value('payment_method');
        if (!empty($payment_method)) {
            $replacements['{{payment.method}}'] = $payment_method;
        }
        
        $payment_status = $get_value('payment_status');
        if (!empty($payment_status)) {
            $replacements['{{payment.status}}'] = $payment_status;
        }
        
        // Website nested
        $website_name = $get_value('website_name');
        if (!empty($website_name)) {
            $replacements['{{website.name}}'] = $website_name;
        }
        
        $website_url = $get_value('website_url');
        if (!empty($website_url)) {
            $replacements['{{website.url}}'] = $website_url;
        }
        
        $admin_email = $get_value('admin_email');
        if (!empty($admin_email)) {
            $replacements['{{admin.email}}'] = $admin_email;
        }
        
        // Reservation link
        $reservation_link = $get_value('reservation_link');
        if (!empty($reservation_link)) {
            $replacements['{{reservation.link}}'] = $reservation_link;
            $replacements['{{reservation_link}}'] = $reservation_link;
        }
        
        // Current year/date
        $current_year = $get_value('current_year');
        if (!empty($current_year)) {
            $replacements['{{current_year}}'] = $current_year;
            $replacements['{{date.year}}'] = $current_year;
        }
        
        // === DYNAMIC REGISTRATION FIELDS ===
        // Support for {{registration.FIELDNAME}} notation for all registration fields
        $registration_fields = array(
            'name', 'email', 'phone', 'company_name', 'address', 'city', 
            'postal_code', 'country', 'guest_count', 'payment_method', 'payment_status',
            'registration_id', 'registration_date', 'unique_identifier'
        );
        
        foreach ($registration_fields as $field) {
            $value = $get_value($field);
            if (!empty($value)) {
                // Add {{registration.fieldname}} variation
                $replacements['{{registration.' . $field . '}}'] = $value;
                // Also support underscore version
                $replacements['{{registration.' . str_replace('_', '-', $field) . '}}'] = $value;
            }
        }
        
        // === DYNAMIC EVENT FIELDS ===
        // Support for {{event.FIELDNAME}} notation for all event fields
        $event_fields = array(
            'event_name', 'event_date', 'event_time', 'event_description',
            'location_name', 'location_link', 'location_url'
        );
        
        foreach ($event_fields as $field) {
            $value = $get_value($field);
            if (!empty($value)) {
                // Strip 'event_' prefix for the placeholder name
                $clean_name = str_replace('event_', '', $field);
                // Add {{event.fieldname}} variation
                $replacements['{{event.' . $clean_name . '}}'] = $value;
                // Also support underscore version
                $replacements['{{event.' . str_replace('_', '-', $clean_name) . '}}'] = $value;
            }
        }
        
        // Apply all replacements - IMPORTANT: Sort by length DESC to avoid double-replacement
        // For example, replace {{field}} BEFORE {field} to avoid partial matches
        // Sort replacements by key length in descending order
        uksort($replacements, function($a, $b) {
            return strlen($b) - strlen($a);
        });
        
        foreach ($replacements as $placeholder => $value) {
            $template = str_replace($placeholder, $value, $template);
        }
        
        return $template;
    }
    
    private function get_default_notification_template() {
        return "New registration for {{event.name}}\n\n" .
               "Registration Details:\n" .
               "Name: {{registration.name}}\n" .
               "Email: {{registration.email}}\n" .
               "Number of Guests: {{registration.guest_count}}\n\n" .
               "Registration Date: {{registration.date}}\n\n" .
               "{{registration.pricing_breakdown}}\n\n" .
               "Event Details:\n" .
               "Date: {{event.date}}\n" .
               "Time: {{event.time}}\n" .
               "Location: {{event.location_name}}" .
               "Location Url: {{event.location_url}}";
    }
    
    /**
     * Clean up unreplaced placeholders from email content
     * Removes any {{placeholder}} or {placeholder} that wasn't replaced with actual values
     * For HTML content: removes elements containing only placeholders
     * For text content: removes lines containing only placeholders
     * IMPORTANT: Only matches placeholders, NOT CSS curly braces
     * 
     * @param string $content Email content with potential unreplaced placeholders
     * @return string Content with empty placeholders cleaned up
     */
    private function clean_empty_placeholders($content) {
        // Detect if this is HTML content
        $is_html = preg_match('/<[a-z]+.*?>/i', $content);
        
        if ($is_html) {
            // === HTML CONTENT CLEANUP ===
            // IMPORTANT: Only clean up actual placeholders {{...}} or {placeholders}, 
            // NOT CSS braces or other content
            
            // Remove detail-row divs where the value span contains ONLY a placeholder (with whitespace)
            // Using negative lookahead to prevent matching across multiple divs
            // ONLY match {{placeholder}} format (double braces) - NOT single braces that might be CSS
            $content = preg_replace(
                '/<div\s+class="detail-row"[^>]*>(?:(?!<\/div>).)*<span[^>]*class="detail-value"[^>]*>\s*\{\{[^}]+\}\}\s*<\/span>\s*<\/div>/is',
                '',
                $content
            );
            
            // Remove other divs/sections that only contain unreplaced placeholders (double braces only)
            $content = preg_replace('/<div[^>]*>\s*\{\{[^}]+\}\}\s*<\/div>/i', '', $content);
            $content = preg_replace('/<section[^>]*>\s*\{\{[^}]+\}\}\s*<\/section>/i', '', $content);
            
            // Remove any remaining unreplaced {{placeholders}} (double braces only)
            $content = preg_replace('/\{\{[^}]+\}\}/', '', $content);
            
            // Clean up empty divs and empty spans (but be careful not to match CSS)
            $content = preg_replace('/<div[^>]*>\s*<\/div>/i', '', $content);
            $content = preg_replace('/<span[^>]*>\s*<\/span>/i', '', $content);
            
            // Clean up excessive whitespace and blank lines
            $content = preg_replace('/\n\s*\n+/', "\n", $content);
            
        } else {
            // === TEXT CONTENT CLEANUP ===
            
            // Remove lines that only contain unreplaced placeholders (double braces only)
            // This handles cases like: "Phone: {{registration.phone}}" when phone is empty
            $content = preg_replace('/^[^:\n]*:\s*\{\{[^}]+\}\}\s*$/m', '', $content);
            
            // Also remove completely empty lines created by the above replacement
            $content = preg_replace('/\n\n+/', "\n", $content);
            
            // Remove any remaining unreplaced {{placeholders}} (double braces only)
            $content = preg_replace('/\{\{[^}]+\}\}/', '', $content);
            
            // Clean up multiple consecutive blank lines
            $content = preg_replace('/\n\n+/', "\n", $content);
        }
        
        return $content;
    }
    
    private function get_default_confirmation_template() {
        return "Dear {{registration.name}},\n\n" .
               "Thank you for registering for {{event.name}}.\n\n" .
               "Registration Details:\n" .
               "Number of Guests: {{registration.guest_count}}\n" .
               "Event Date: {{event.date}}\n" .
               "Event Time: {{event.time}}\n" .
               "Location: {{event.location_link}}\n\n" .
               "{{registration.pricing_breakdown}}\n\n" .
               "Manage your registration: {{registration.reservation_link}}\n\n" .
               "We look forward to seeing you!\n\n" .
               "{{admin.email}}\n\n" .
               "Best regards,\n" .
               "The Event Team";
    }

    public function delete_reservation() {
        // Verify nonce

        error_log('Delete reservation request received');
        error_log('Post Data: ' . print_r($_POST, true));

        if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'delete_reservation_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }

        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'You must be logged in to delete a reservation'));
            return;
        }

        // Get and validate reservation ID
        $reservation_id = isset($_POST['reservation_id']) ? intval($_POST['reservation_id']) : 0;
        error_log('Reservation ID: ' . $reservation_id);
        if (!$reservation_id) {
            wp_send_json_error(array('message' => 'Invalid reservation ID'));
            return;
        }

        // Get and validate unique ID
        $unique_id = isset($_POST['unique_id']) ? sanitize_text_field($_POST['unique_id']) : '';
        error_log('Unique ID: ' . $unique_id);
        if (!($unique_id)) {
            wp_send_json_error(array('message' => 'Invalid unique ID qq'));
            return;
        }

        global $wpdb;
        $current_user = wp_get_current_user();

        error_log('Status: Before checking reservation');

        // Check if the reservation belongs to the current user
        $reservation = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sct_event_registrations WHERE id = %d AND unique_identifier = %s",
            $reservation_id, $unique_id
        ));

        error_log('Reservation: ' . print_r($reservation, true));

        if (!$reservation) {
            wp_send_json_error(array('message' => 'Reservation not found or you do not have permission to delete this reservation'));
            return;
        }

        // Delete the reservation
        $result = $wpdb->delete(
            $wpdb->prefix . 'sct_event_registrations',
            array('id' => $reservation_id),
            array('%d')
        );

        error_log('Result: ' . print_r($result, true));

        if ($result === false) {
            wp_send_json_error(array('message' => 'Failed to delete reservation'));
            return;
        }

        wp_send_json_success(array('message' => 'Reservation deleted successfully'));
    }

    /**
     * REPAIR CORRUPTED EMAIL TEMPLATES
     * 
     * Detects and repairs email templates that were corrupted by WordPress wpautop() filter.
     * Symptoms: CSS on multiple lines with <br /> tags, HTML structure mangled
     * Root cause: wp_editor() or other form processing applying wpautop() on save
     * 
     * This function:
     * 1. Detects if template has corruption markers (CSS with <br /> tags)
     * 2. Attempts to reconstruct single-line CSS if possible
     * 3. Returns original template if no corruption detected
     * 4. Can be used on database load or before form display
     * 
     * @param string $template Email template potentially corrupted
     * @return string Repaired template or original if not corrupted
     */
    public static function repair_corrupted_template($template) {
        if (empty($template)) {
            return $template;
        }
        
        // Check for corruption markers: <style> tag content contains <br /> or <br>
        // This indicates wpautop() was applied to the CSS
        if (!preg_match('/<style[^>]*>.*?<\/style>/is', $template)) {
            // No style tag, not corrupted (or not an HTML template)
            return $template;
        }
        
        // Extract the style block
        if (!preg_match('/<style[^>]*>(.*?)<\/style>/is', $template, $matches)) {
            return $template;
        }
        
        $style_content = $matches[1];
        
        // Check if style content has corruption markers
        if (!preg_match('/<br\s*\/?>/i', $style_content)) {
            // No br tags in style, not corrupted
            return $template;
        }
        
        // CORRUPTED: Attempt to repair
        // Remove all <br> and <br /> tags from style content
        $repaired_style = preg_replace('/<br\s*\/?>/i', '', $style_content);
        
        // Remove extra whitespace and newlines in CSS (collapse multiple spaces)
        $repaired_style = preg_replace('/\s+/', ' ', $repaired_style);
        
        // Trim the result
        $repaired_style = trim($repaired_style);
        
        // Replace the corrupted style block with the repaired one
        $repaired_template = preg_replace(
            '/<style[^>]*>(.*?)<\/style>/is',
            '<style>' . $repaired_style . '</style>',
            $template
        );
        
        error_log('EMAIL TEMPLATE CORRUPTION DETECTED AND REPAIRED');
        error_log('Original style length: ' . strlen($style_content));
        error_log('Repaired style length: ' . strlen($repaired_style));
        
        return $repaired_template;
    }

}
