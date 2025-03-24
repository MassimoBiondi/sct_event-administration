<?php
class EventPublic {
    public function __construct() {
        add_shortcode('event_list', array($this, 'render_events_list'));
        add_shortcode('event_registration', array($this, 'render_event_registration'));
        add_shortcode('event_management', array($this, 'render_event_management'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));

        // Add AJAX handlers for both logged in and non-logged in users
        add_action('wp_ajax_register_event', array($this, 'process_registration'));
        add_action('wp_ajax_nopriv_register_event', array($this, 'process_registration'));

        // add_action('wp_enqueue_scripts', array($this, 'enqueue_public_scripts'));
        add_action('wp_ajax_delete_reservation', array($this, 'delete_reservation'));
        add_action('wp_ajax_nopriv_delete_reservation', array($this, 'delete_reservation'));
    }

    public function enqueue_scripts() {
        global $post;
        if (is_a($post, 'WP_Post') && 
            (has_shortcode($post->post_content, 'event_list') || 
             has_shortcode($post->post_content, 'event_registration') ||
             has_shortcode($post->post_content, 'event_management')
            )) {
            
            wp_enqueue_style(
                'event-public-style',
                EVENT_ADMIN_URL . 'public/css/public.css',
                array(),
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
                    'currencySymbol' => $sct_settings['currency_symbol'],
                    'currencyFormat' => $sct_settings['currency_format']
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

    public function render_events_list($atts) {
        global $wpdb;

        // Parse attributes with defaults
        $atts = shortcode_atts(array(
            'limit' => null, // Default to null for all events
        ), $atts);

        // Build the query
        $query = "SELECT * FROM {$wpdb->prefix}sct_events WHERE event_date >= CURDATE() ORDER BY event_date ASC";
        
        // Add LIMIT clause if limit parameter is set and is a positive number
        if (!is_null($atts['limit']) && is_numeric($atts['limit']) && $atts['limit'] > 0) {
            $query .= $wpdb->prepare(" LIMIT %d", intval($atts['limit']));
        }
        
        // Get events
        $events = $wpdb->get_results($query);
        
        // $events = $wpdb->get_results(
        //     "SELECT * FROM {$wpdb->prefix}sct_events 
        //      WHERE event_date >= CURDATE() 
        //      ORDER BY event_date ASC"
        // );
        
        // Get the registration page ID from settings
        $registration_page_id = get_option('event_admin_settings', [])["event_registration_page"];

        // Get the management page ID from settings
        $management_page_id = get_option('event_admin_settings', [])["event_management_page"];
        
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
    
        // Get current registration count
        $registered_guests = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(guest_count) FROM {$wpdb->prefix}sct_event_registrations WHERE event_id = %d",
            $event->id
        ));
        
        $registered_guests = $registered_guests ?: 0;
        $remaining_capacity = $event->guest_capacity - $registered_guests;

        // Check if the user is a member
        $is_member = is_user_logged_in() && current_user_can('member'); // Adjust this condition based on your membership logic
        $price = $is_member ? $event->member_price : $event->non_member_price;
    
        ob_start();
        include EVENT_ADMIN_PATH . 'public/views/event-registration.php';
        return ob_get_clean();
    }

    public function render_event_management() {
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
            $member_guests = isset($_POST['member_guests']) ? intval($_POST['member_guests']) : 0;
            $non_member_guests = isset($_POST['non_member_guests']) ? intval($_POST['non_member_guests']) : 0;
            $children_guests = isset($_POST['children_guests']) ? intval($_POST['children_guests']) : 0;
            $guest_count = $member_guests + $non_member_guests + $children_guests;
    
            // Validate required fields
            if (!$event_id || !$name || !$email || $guest_count < 1) {
                error_log('Missing required fields');
                wp_send_json_error(array('message' => 'All fields are required.'));
                return;
            }
    
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
    
            // Debug event data
            error_log('Event found: ' . print_r($event, true));
    
            // Validate guest count against max per registration
            if ($event->max_guests_per_registration > 0) {  // Only check if max guests limit is not 0
                if ($guest_count > $event->max_guests_per_registration) {
                    error_log('Max guests per registration exceeded');
                    wp_send_json_error(array(
                        'message' => sprintf(
                            'Maximum number of guests per registration is %d',
                            $event->max_guests_per_registration
                        )
                    ));
                    return;
                }
            }
    
            // Check if this email has already registered for this event
            $existing_registration = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}sct_event_registrations 
                WHERE event_id = %d AND email = %s",
                $event_id,
                $email
            ));

            if ($existing_registration > 0) {
                error_log('Duplicate registration attempt: ' . $email . ' for event: ' . $event_id);
                wp_send_json_error(array(
                    'message' => 'You have already registered for this event.'
                ));
                return;
            }

            // Check remaining capacity
            if ($event->guest_capacity > 0) {  // Only check if capacity is not 0
                $current_registrations = $wpdb->get_var($wpdb->prepare(
                    "SELECT SUM(guest_count) FROM {$wpdb->prefix}sct_event_registrations WHERE event_id = %d",
                    $event_id
                ));
                
                $remaining_capacity = $event->guest_capacity - $current_registrations;
                if ($guest_count > $remaining_capacity) {
                    error_log('Not enough remaining capacity');
                    wp_send_json_error(array(
                        'message' => sprintf(
                            'Only %d spots remaining for this event.',
                            $remaining_capacity
                        )
                    ));
                    return;
                }
            }

            // Generate a unique identifier for the registration
            $unique_identifier = wp_generate_uuid4();

            // Prepare registration data
            $registration_data = array(
                'event_id' => $event_id,
                'name' => $name,
                'email' => $email,
                'guest_count' => $guest_count,
                'member_guests' => $member_guests,
                'non_member_guests' => $non_member_guests,
                'children_guests' => $children_guests,
                'registration_date' => current_time('mysql'),
                'unique_identifier' => $unique_identifier
            );
    
            // Debug registration data
            error_log('Attempting to insert registration: ' . print_r($registration_data, true));
    
            // Insert registration
            $result = $wpdb->insert(
                $wpdb->prefix . 'sct_event_registrations',
                $registration_data,
                array('%d', '%s', '%s', '%d', '%d', '%d', '%d', '%s', '%s')
            );
    
            if ($result === false) {
                error_log('Database error: ' . $wpdb->last_error);
                wp_send_json_error(array('message' => 'Registration failed. Please try again.'));
                return;
            }
    
            $registration_id = $wpdb->insert_id;

            // Prepare event data for emails
            $event_data = array(
                'registration_id' => $registration_id,
                'event_id' => $event->id,
                'event_name' => $event->event_name,
                'event_date' => $event->event_date,
                'event_time' => $event->event_time,
                'location_name' => $event->location_name,
                'admin_email' => $event->admin_email,
                'unique_identifier' => $unique_identifier
            );

            // Send confirmation and notification emails
            $this->send_registration_emails($registration_data, $event_data);

            wp_send_json_success(array(
                'message' => 'Thank you for registering! You will receive a confirmation email shortly.'
            ));
    
        } catch (Exception $e) {
            error_log('Registration error: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'An unexpected error occurred.'));
        }
    }
    
    private function send_registration_emails($registration_data, $event_data) {
        $sct_settings = get_option('event_admin_settings', array(
            'admin_email' => get_option('admin_email'),
            'notification_subject' => 'New Event Registration: {event_name}',
            'notification_template' => $this->get_default_notification_template(),
            'confirmation_subject' => 'Registration Confirmation: {event_name}',
            'confirmation_template' => $this->get_default_confirmation_template()
        ));

        global $wpdb;
        $event_data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sct_events WHERE id = %d",
            $registration_data['event_id']
        ), ARRAY_A);

        // Use custom email template if available
        $confirmation_template = !empty($event_data['custom_email_template']) ? $event_data['custom_email_template'] : $sct_settings['confirmation_template'];

        // Prepare data for placeholder replacement
        $placeholder_data = array_merge($registration_data, $event_data);

        error_log('Merge Data: placeholder data: ' . print_r($placeholder_data, true) . ' registration data: ' . print_r($registration_data, true) . ' event data: ' . print_r($event_data, true));

        // Use event's admin email, fallback to WordPress admin email if not set
        $admin_email = !empty($event_data['admin_email']) ? 
            $event_data['admin_email'] : 
            $sct_settings['admin_email'];
        
        // Send admin notification
        $admin_subject = $this->replace_email_placeholders(
            $sct_settings['notification_subject'], 
            $placeholder_data
        );
        $admin_message = $this->replace_email_placeholders(
            $sct_settings['notification_template'], 
            $placeholder_data
        );

        // Get the full URL from WordPress
        $blog_url = get_bloginfo('url');
        $parsed_url = parse_url($blog_url);
        $domain = $parsed_url['host'];
        $domain = preg_replace('/^.*?([^.]+\.[^.]+)$/', '$1', $domain);

        if (strpos($domain, '.') === false) {
            $domain .= '.qq';
        }

        // Create HTML version for admin notification
        $admin_message_html = nl2br($admin_message);
        $admin_message_html = '<html><head><meta charset="UTF-8"></head><body>' . $admin_message_html . '</body></html>';
        
        $admin_headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <events@' . $domain . '>',
            'Reply-To: ' . $admin_email
        );

        $admin_sent = wp_mail(
            $admin_email,
            $admin_subject, 
            $admin_message_html,
            $admin_headers
        );

        // Log admin email status
        error_log('Admin notification ' . ($admin_sent ? 'sent' : 'failed') . ' for registration: ' . $registration_data['email']);
        
        // Send confirmation email
        $confirmation_subject = $this->replace_email_placeholders(
            $sct_settings['confirmation_subject'], 
            $placeholder_data
        );
        $confirmation_message = $this->replace_email_placeholders(
            $confirmation_template, 
            $placeholder_data
        );

        // Add the manage reservation link to the confirmation email
        $manage_reservation_url = add_query_arg(array(
            'action' => 'manage_reservation',
            'uid' => $registration_data['unique_identifier']
        ), get_permalink(get_option('event_admin_settings')['event_management_page']));
        
        // Create HTML version with proper link
        $confirmation_message_html = str_replace(
            '{manage_link}',
            '<a href="' . esc_url($manage_reservation_url) . '">Manage your registration</a>',
            $confirmation_message
        );
        $confirmation_message_html = nl2br($confirmation_message_html);
        $confirmation_message_html = '<html><head><meta charset="UTF-8"></head><body>' . $confirmation_message_html . '</body></html>';
        
        $confirmation_headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <events@' . $domain . '>',
            'Reply-To: ' . $admin_email
        );

        $confirmation_sent = wp_mail(
            $registration_data['email'], 
            $confirmation_subject, 
            $confirmation_message_html,
            $confirmation_headers
        );

        if ($confirmation_sent) {
            $registration_id = $registration_data['registration_id'];
            $event_id = $registration_data['event_id'];

            // Save the email data
            $wpdb->insert(
                $wpdb->prefix . 'sct_event_emails',
                array(
                    'registration_id' => $registration_id,
                    'event_id' => $event_id,
                    'email_type' => 'confirmation',
                    'subject' => $confirmation_subject,
                    'message' => $confirmation_message_html,
                    'sent_date' => current_time('mysql'),
                    'status' => 'sent'
                ),
                array('%d', '%d', '%s', '%s', '%s', '%s', '%s')
            );
        }

        // Log confirmation email status
        error_log('Confirmation email ' . ($confirmation_sent ? 'sent' : 'failed') . ' for: ' . $registration_data['email']);
    }
    
    private function replace_email_placeholders($template, $data) {
        $placeholders = array(
            '{event_name}' => $data['event_name'] ?? '',
            '{name}' => $data['name'] ?? '',
            '{email}' => $data['email'] ?? '',
            '{guest_count}' => $data['guest_count'] ?? '',
            '{registration_date}' => $data['registration_date'] ?? '',
            '{event_date}' => $data['event_date'] ?? '',
            '{event_time}' => $data['event_time'] ?? '',
            '{location_name}' => $data['location_name'] ?? ''
        );
    
        return str_replace(array_keys($placeholders), array_values($placeholders), $template);
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
}
