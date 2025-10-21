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

            error_log('Goods Service: ' . $goods_services);
            // Process goods/services
            if (!empty($_POST['goods_services']) && is_array($_POST['goods_services'])) {
                $processed_goods_services = array_map(function ($service) {
                    return isset($service['count']) ? intval($service['count']) : 0;
                }, $_POST['goods_services']);
            } else {
                $processed_goods_services = array();
            }


            // Serialize guest details and goods/services for storage
            // $guest_details_serialized = maybe_serialize($processed_guest_details);
            // $goods_services_serialized = maybe_serialize($processed_goods_services);
            $guest_details_serialized = maybe_serialize($guest_details);
            $goods_services_serialized = maybe_serialize($goods_services);

            // Prepare registration data
            $registration_data = array(
                'event_id' => $event_id,
                'name' => $name,
                'email' => $email,
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
                array('%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s')
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

        // Use custom email template if available
        $confirmation_template = !empty($event_data['custom_email_template']) ? $event_data['custom_email_template'] : $sct_settings['confirmation_template'];
        $notification_template = $sct_settings['notification_template'];

        // Prepare data for placeholder replacement
        $placeholder_data = array_merge($registration_data, $event_data);

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

        $notification_subject = $this->replace_email_placeholders(
            $sct_settings['notification_subject'],
            $placeholder_data
        );
        $notification_message = $this->replace_email_placeholders(
            $notification_template,
            $placeholder_data
        );

        // Add the reservation link to the confirmation email
        $settings = get_option('event_admin_settings', []);
        $management_page_id = isset($settings['event_management_page']) ? $settings['event_management_page'] : null;
        $reservation_url = add_query_arg(array(
            'action' => 'manage_reservation',
            'uid' => $registration_data['unique_identifier']
        ), get_permalink($management_page_id));

        // Create HTML version with proper link
        $confirmation_message_html = str_replace(
            '{reservation_link}',
            '<a href="' . esc_url($reservation_url) . '">Manage your registration</a>',
            $confirmation_message
        );

        $confirmation_css_path = EVENT_ADMIN_PATH . 'admin/templates/confirmation_css.html';

        // Check if the file exists before reading it
        if (file_exists($confirmation_css_path)) {
            $confirmation_css_content = file_get_contents($confirmation_css_path);
        } else {
            $confirmation_css_content = '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Registration Confirmed: {event_name}!</title></head><body><div class="email-container">'; // Fallback to an empty string if the file doesn't exist
        }

        $confirmation_message_html = wpautop($confirmation_message_html);
        $confirmation_message_html = $confirmation_css_content . ($confirmation_message_html) . '</div></body></html>';

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



    private function log_email($event_id, $email_type, $recipient_email, $subject, $message, $status = 'sent') {
        return SCT_Event_Email_Utilities::log_email($event_id, $email_type, $recipient_email, $subject, $message, $status);
    }

    private function generate_dkim_signature($subject, $from_email, $to_email, $body) {
        return SCT_Event_Email_Utilities::generate_dkim_signature($subject, $from_email, $to_email, $body);
    }
    
    private function replace_email_placeholders($template, $data) {
        return SCT_Event_Email_Utilities::replace_email_placeholders($template, $data);
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

}
