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
    }

    public function add_admin_menu() {
        // Add main menu item
        add_menu_page(
            'Event Administration',
            'Events',
            'manage_options',
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
            'manage_options',
            'event-admin',
            array($this, 'display_events_list_page')
        );

        add_submenu_page(
            'event-admin',
            'Add New Event',
            'Add New',
            'manage_options',
            'event-admin-new',
            array($this, 'display_add_event_page')
        );

        add_submenu_page(
            'event-admin',
            'Registrations',
            'Registrations',
            'manage_options',
            'event-registrations',
            array($this, 'display_registrations_page')
        );

        add_submenu_page(
            'event-admin',
            'Past Events',
            'Past Events',
            'manage_options',
            'event-past',
            array($this, 'display_past_events_page')
        );

        add_submenu_page(
            'event-admin',
            'Past Registrations',
            'Past Registrations',
            'manage_options',
            'past-registrations',
            array($this, 'display_past_registrations_page')
        );

        add_submenu_page(
            'event-admin', // Parent slug
            'Email History', // Page title
            'Email History', // Menu title
            'manage_options', // Capability
            'sct-email-history', // Menu slug
            array($this, 'sct_render_emails_page') // Callback function
        );
        
        add_submenu_page(
            'event-admin',
            'Settings',
            'Settings',
            'manage_options',
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
                        CONCAT('Mass email sent to ', COUNT(*), ' recipients')
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
        // if (
        //     strpos($hook, 'event-admin') === false && 
        //     strpos($hook, 'event-registrations') === false && 
        //     strpos($hook, 'sct-email-history') === false
        // ) {
        //     return;
        // }
    
        wp_enqueue_style('event-admin-style', EVENT_ADMIN_URL . 'admin/css/admin.css', array(), '1.0.0');
        wp_enqueue_script('event-admin-script', EVENT_ADMIN_URL . 'admin/js/admin.js', array('jquery'), '1.0.0', true);

        wp_localize_script('event-admin-script', 'eventAdmin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'security' => wp_create_nonce('event_admin_nonce'),
            'updateNonce' => wp_create_nonce('update_registration_nonce'),
            'emailNonce' => wp_create_nonce('retry_email_nonce')

            // 'security' => wp_create_nonce('event_admin_nonce')
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
        $upcoming_events = $this->get_events('upcoming');
        // $events = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}sct_events ORDER BY event_date DESC");
        include EVENT_ADMIN_PATH . 'admin/views/registrations.php';
    }

    public function save_event() {
        check_ajax_referer('event_admin_nonce');
    
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied.'));
            return;
        }
    
        global $wpdb;

        // error_log(print_r(stripslashes(sanitize_text_field($_POST['event_name']), true)));
        error_log(stripslashes($_POST['event_name']));
        

        $event_data = array(
            'event_name' => stripslashes(sanitize_text_field($_POST['event_name'])),
            'event_date' => sanitize_text_field($_POST['event_date']),
            'event_time' => sanitize_text_field($_POST['event_time']),
            'location_name' => sanitize_text_field($_POST['location_name']),
            'location_link' => esc_url_raw($_POST['location_link']),
            'description' => stripslashes(wp_kses_post($_POST['event_description'])),
            'guest_capacity' => intval($_POST['guest_capacity']),
            'max_guests_per_registration' => intval($_POST['max_guests_per_registration']),
            'admin_email' => sanitize_email($_POST['admin_email'])
        );
    
        $data_format = array(
            '%s', // event_name
            '%s', // event_date
            '%s', // event_time
            '%s', // location_name
            '%s', // location_link
            '%s', // for description
            '%d', // guest_capacity
            '%d', // max_guests_per_registration
            '%s'  // admin_email
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
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized access'));
            return;
        }
    
        // Get and validate event ID
        $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'delete_event')) {
            wp_send_json_error(array('message' => 'Invalid security token'));
            return;
        }
    
        global $wpdb;
    
        // Start transaction
        // $wpdb->query('START TRANSACTION');
    
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
            $result = $wpdb->query($wpdb->prepare(
                "DELETE FROM {$wpdb->prefix}sct_events WHERE id = %d",
                $event_id
            ));

            
            $result = $wpdb->delete(
                $wpdb->prefix . 'sct_events',
                array('id' => $event_id),
                array('%d')
            );
    
            if ($result === false) {
                throw new Exception('Failed to delete event');
            }
    
            wp_send_json_success(array(
                // 'message' => 'Event deleted successfully',
                'message' => 'Event deleted successfully',
                'deleted' => array(
                    'event' => true,
                    'registrations' => $registration_count > 0,
                    'emails' => $email_count > 0
                )
            ));
    
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
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }
    
        global $wpdb;
        
        $registration_id = intval($_POST['registration_id']);
        $guest_count = intval($_POST['guest_count']);
        
        if ($guest_count < 1) {
            wp_send_json_error(array('message' => 'Guest count must be at least 1'));
            return;
        }
        
        // Get the registration and event details
        $registration = $wpdb->get_row($wpdb->prepare(
            "SELECT r.*, e.guest_capacity 
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
        if (($total_guests + $guest_count) > $registration->guest_capacity) {
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
            array('guest_count' => $guest_count),
            array('id' => $registration_id),
            array('%d'),
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
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized access'));
            return;
        }
    
        // Verify nonce
        $registration_id = isset($_POST['registration_id']) ? intval($_POST['registration_id']) : 0;
        if (!wp_verify_nonce($_POST['nonce'], 'delete_registration')) {
            wp_send_json_error(array('message' => 'Invalid security token'));
            return;
        }
    
        global $wpdb;
    
        // Delete associated emails first (to maintain referential integrity)
        $wpdb->delete(
            $wpdb->prefix . 'sct_event_emails',
            array('registration_id' => $registration_id),
            array('%d')
        );
    

        $result = $wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}sct_event_registrations WHERE id = %d",
            $registration_id
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

    public function send_registration_email() {
        // Verify nonce
        if (!isset($_POST['email_security']) || !wp_verify_nonce($_POST['email_security'], 'send_email_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }
    
        global $wpdb;
        $event_id = intval($_POST['event_id']);
        $sct_settings = get_option('event_admin_settings', array(
            'event_registration_page' => get_option('event_registration_page'),
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

        $admin_email = !empty($event_data['admin_email']) ? 
            $event_data['admin_email'] : 
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
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <events@' . $domain . '>',
            'Reply-To: ' . $admin_email
        );
    
        $success_count = 0;
        $fail_count = 0;
        $total_to_send = 0;
    
        if ($is_mass_email) {
            // Get all registrants for the event
            error_log("Event ID: " . $event_id);
            $registrations = $wpdb->get_results($wpdb->prepare(
                "SELECT r.*, e.*, r.id as registration_id
                 FROM {$wpdb->prefix}sct_event_registrations r 
                 JOIN {$wpdb->prefix}sct_events e ON r.event_id = e.id 
                 WHERE r.event_id = %d",
                $event_id
            ));

            // error_log("Registrations: " . print_r($registrations, true));
            
            if (empty($registrations)) {
                wp_send_json_error(array('message' => 'No registrations found'));
                return;
            }
    
            $total_to_send = count($registrations);
    
            // Send individual emails to each registrant
            foreach ($registrations as $registration) {
                // Replace placeholders for this specific registrant
                $placeholders = array(
                    '{name}' => $registration->name,
                    '{event_name}' => $registration->event_name,
                    '{event_date}' => date('F j, Y', strtotime($registration->event_date)),
                    '{event_time}' => date('g:i A', strtotime($registration->event_time)),
                    '{guest_count}' => $registration->guest_count,
                    '{location}' => $registration->location_name
                );
                
                $personalized_body = str_replace(
                    array_keys($placeholders), 
                    array_values($placeholders), 
                    $body_template
                );
    
                // Send the email
                $sent = wp_mail(
                    $registration->email,
                    $subject,
                    wpautop($personalized_body),
                    $headers
                );
    
                if ($sent) {
                    $success_count++;

                    // error_log("Email sent to: " . $registration->registration_id . " with subject: " . $subject);
                    
                    $wpdb->insert(
                        $wpdb->prefix . 'sct_event_emails',
                        array(
                            'registration_id' => $registration->registration_id,
                            'event_id' => $event_id,
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
    
            // Replace placeholders
            $placeholders = array(
                '{name}' => $registration->name,
                '{event_name}' => $registration->event_name,
                '{event_date}' => date('F j, Y', strtotime($registration->event_date)),
                '{event_time}' => date('g:i A', strtotime($registration->event_time)),
                '{guest_count}' => $registration->guest_count,
                '{location}' => $registration->location_name
            );
            
            $personalized_body = str_replace(
                array_keys($placeholders), 
                array_values($placeholders), 
                $body_template
            );
    
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
                        'registration_id' => $registration_id,
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
                wp_send_json_success(array('message' => 'Email sent succesfully'));
            } else {
                wp_send_json_error(array('message' => 'Failed to send email'));
            }
        }
    }

    public function get_events($type = 'upcoming') {
        global $wpdb;

        $order = ($type === 'upcoming') ? 'ASC' : 'DESC';
        
        $current_date = current_time('Y-m-d');
        
        $where_clause = ($type === 'upcoming') 
            ? "WHERE DATE(CONCAT(event_date, ' ', event_time)) >= %s"
            : "WHERE DATE(CONCAT(event_date, ' ', event_time)) < %s";
            
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
            'admin_email' => get_option('admin_email'),
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
               "We look forward to seeing you!\n\n" .
               "Best regards,\n" .
               "The Event Team";
    }

    public function getDefaultConfirmationTemplate() {
        return $this->get_default_confirmation_template();
    }
    
    private function save_sct_settings() {
        $settings = array(
            'event_registration_page' => ($_POST['event_registration_page']),
            'admin_email' => sanitize_email($_POST['admin_email']),
            'notification_subject' => sanitize_text_field($_POST['notification_subject']),
            'notification_template' => wp_kses_post($_POST['notification_template']),
            'confirmation_subject' => sanitize_text_field($_POST['confirmation_subject']),
            'confirmation_template' => wp_kses_post($_POST['confirmation_template'])
        );
    
        update_option('event_admin_settings', $settings);
        add_settings_error(
            'event_admin_settings',
            'settings_updated',
            'Settings saved successfully.',
            'updated'
        );
    }

    public function handle_export_event_registrations() {
        if (!current_user_can('manage_options')) {
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
            "SELECT event_name, event_date FROM {$wpdb->prefix}sct_events WHERE id = %d",
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

        // Add CSV headers
        fputcsv($output, array(
            'Registration ID',
            'Name',
            'Email',
            'Number of Guests',
            'Registration Date',
            'Emails Sent',
            'Last Email Date'
        ));

        // Add data rows
        foreach ($registrations as $registration) {
            fputcsv($output, array(
                $registration['id'],
                $registration['name'],
                $registration['email'],
                $registration['guest_count'],
                wp_date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($registration['registration_date'])),
                $registration['email_count'] ?: '0',
                $registration['last_email_sent'] ? wp_date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($registration['last_email_sent'])) : 'Never'
            ));
        }

        // Add summary row
        fputcsv($output, array('')); // Empty row for spacing
        fputcsv($output, array(
            'Total Registrations:',
            count($registrations),
            'Total Guests:',
            array_sum(array_column($registrations, 'guest_count'))
        ));

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
    
    

} // End Class
