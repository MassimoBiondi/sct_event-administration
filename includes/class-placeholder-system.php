<?php
/**
 * Event-specific Email Placeholder System
 */

if (!defined('ABSPATH')) {
    exit;
}

// Include the base class (make sure it's loaded first)
if (!class_exists('SCT_Email_Placeholder_Base')) {
    require_once dirname(__FILE__) . '/utilities/class-placeholder-base.php';
}

class SCT_Event_Email_Placeholders extends SCT_Email_Placeholder_Base {
    
    /**
     * Get mappings from old format to new format for backward compatibility
     */
    protected static function get_old_format_mappings() {
        return array_merge(parent::get_old_format_mappings(), array(
            // Event-specific mappings
            '{registration_id}' => 'registration.id',
            '{event_name}' => 'event.title',
            '{event_title}' => 'event.title',
            '{name}' => 'attendee.name',
            '{attendee_name}' => 'attendee.name',
            '{email}' => 'attendee.email',
            '{attendee_email}' => 'attendee.email',
            '{guest_count}' => 'attendee.guest_count',
            '{people_count}' => 'attendee.guest_count',
            '{event_date}' => 'event.date',
            '{event_time}' => 'event.time',
            '{event_end_date}' => 'event.end_date',
            '{event_end_time}' => 'event.end_time',
            '{event_date_range}' => 'event.date_range',
            '{location_name}' => 'location.name',
            '{location_url}' => 'location.url',
            '{location_link}' => 'location.link',
            '{total_price}' => 'payment.total',
            '{total_cost}' => 'payment.total',
            '{payment_status}' => 'payment.status',
            '{description}' => 'event.description',
            '{event_description}' => 'event.description',
            '{guest_capacity}' => 'capacity.total',
            '{remaining_capacity}' => 'capacity.remaining',
            '{manage_registration_link}' => 'registration.manage_link',
            '{manage_link}' => 'registration.manage_link',
        ));
    }
    
    /**
     * Register event-specific placeholders
     */
    protected static function register_plugin_placeholders() {
        // Event placeholders
        static::register_placeholder('event.id', array(
            'description' => 'Event ID',
            'callback' => function($data) { return $data['event_id'] ?? $data['id'] ?? ''; },
            'category' => 'event',
            'example' => '123',
            'required_data' => array('event_id')
        ));
        
        static::register_placeholder('event.title', array(
            'description' => 'Event title/name',
            'callback' => function($data) { return $data['event_title'] ?? $data['event_name'] ?? $data['title'] ?? ''; },
            'category' => 'event',
            'example' => 'Summer Conference 2025',
            'required_data' => array('event_title')
        ));
        
        static::register_placeholder('event.date', array(
            'description' => 'Event date',
            'callback' => function($data) { return $data['event_date'] ?? ''; },
            'category' => 'event',
            'example' => 'August 15, 2025',
            'required_data' => array('event_date')
        ));
        
        static::register_placeholder('event.time', array(
            'description' => 'Event time',
            'callback' => function($data) { return $data['event_time'] ?? ''; },
            'category' => 'event',
            'example' => '2:00 PM',
            'required_data' => array('event_time')
        ));
        
        static::register_placeholder('event.end_date', array(
            'description' => 'Event end date (for multi-day events)',
            'callback' => function($data) { return $data['event_end_date'] ?? ''; },
            'category' => 'event',
            'example' => 'August 17, 2025',
            'required_data' => array('event_end_date')
        ));
        
        static::register_placeholder('event.end_time', array(
            'description' => 'Event end time',
            'callback' => function($data) { return $data['event_end_time'] ?? ''; },
            'category' => 'event',
            'example' => '5:00 PM',
            'required_data' => array('event_end_time')
        ));
        
        static::register_placeholder('event.date_range', array(
            'description' => 'Event date/time range (formatted for display)',
            'callback' => function($data) { 
                if (!isset($data['event_date'])) {
                    return '';
                }
                // Create a mock event object from the data
                $event = (object)$data;
                return EventPublic::format_event_date_range($event);
            },
            'category' => 'event',
            'example' => 'August 15 – August 17, 2025 at 2:00 PM',
            'required_data' => array('event_date')
        ));
        
        static::register_placeholder('event.description', array(
            'description' => 'Event description',
            'callback' => function($data) { return $data['description'] ?? $data['event_description'] ?? ''; },
            'category' => 'event',
            'example' => 'Join us for an amazing conference...',
            'required_data' => array('description')
        ));
        
        // Location placeholders
        static::register_placeholder('location.name', array(
            'description' => 'Event location name',
            'callback' => function($data) { return esc_html(stripslashes($data['location_name'] ?? '')); },
            'category' => 'location',
            'example' => 'Grand Hotel Conference Center',
            'required_data' => array('location_name')
        ));
        
        static::register_placeholder('location.url', array(
            'description' => 'Event location URL',
            'callback' => function($data) { return $data['location_link'] ?? $data['location_url'] ?? ''; },
            'category' => 'location',
            'example' => 'https://maps.google.com/...',
            'required_data' => array('location_link')
        ));
        
        static::register_placeholder('location.link', array(
            'description' => 'Event location as clickable link',
            'callback' => function($data) { 
                $url = $data['location_link'] ?? $data['location_url'] ?? '';
                $name = $data['location_name'] ?? 'View Location';
                return !empty($url) ? '<a href="' . esc_url($url) . '" target="_blank" rel="noopener">' . esc_html($name) . '</a>' : '';
            },
            'category' => 'location',
            'example' => '<a href="...">View on Map</a>',
            'required_data' => array('location_link')
        ));
        
        // Attendee placeholders
        static::register_placeholder('attendee.name', array(
            'description' => 'Attendee full name',
            'callback' => function($data) { return $data['attendee_name'] ?? $data['name'] ?? ''; },
            'category' => 'attendee',
            'example' => 'John Smith',
            'required_data' => array('name')
        ));
        
        static::register_placeholder('attendee.email', array(
            'description' => 'Attendee email address',
            'callback' => function($data) { return $data['attendee_email'] ?? $data['email'] ?? ''; },
            'category' => 'attendee',
            'example' => 'john.smith@example.com',
            'required_data' => array('email')
        ));
        
        static::register_placeholder('attendee.guest_count', array(
            'description' => 'Number of guests/people',
            'callback' => function($data) { return $data['guest_count'] ?? $data['people_count'] ?? '1'; },
            'category' => 'attendee',
            'example' => '3',
            'required_data' => array('guest_count')
        ));
        
        // Registration placeholders
        static::register_placeholder('registration.id', array(
            'description' => 'Registration ID',
            'callback' => function($data) { return $data['registration_id'] ?? ''; },
            'category' => 'registration',
            'example' => 'REG-12345',
            'required_data' => array('registration_id')
        ));
        
        static::register_placeholder('registration.date', array(
            'description' => 'Registration date',
            'callback' => function($data) { return $data['registration_date'] ?? current_time('F j, Y'); },
            'category' => 'registration',
            'example' => 'August 10, 2025',
            'required_data' => array('registration_date')
        ));
        
        static::register_placeholder('registration.manage_link', array(
            'description' => 'Registration management link',
            'callback' => function($data) { 
                if (!empty($data['manage_link'])) {
                    return $data['manage_link'];
                }
                
                if (!empty($data['registration_id'])) {
                    $management_page = get_option('event_management_page');
                    if ($management_page) {
                        return get_permalink($management_page) . '?registration_id=' . $data['registration_id'];
                    }
                }
                
                return '';
            },
            'category' => 'registration',
            'example' => 'https://example.com/manage-registration?id=123',
            'required_data' => array('registration_id')
        ));
        
        // Payment/Pricing placeholders
        static::register_placeholder('payment.total', array(
            'description' => 'Total payment amount (formatted)',
            'callback' => function($data) { 
                $amount = $data['total_price'] ?? $data['total_cost'] ?? 0;
                return static::format_currency($amount);
            },
            'category' => 'payment',
            'example' => '$150.00',
            'required_data' => array('total_price')
        ));
        
        static::register_placeholder('payment.breakdown', array(
            'description' => 'Pricing breakdown details',
            'callback' => function($data) { return $data['pricing_breakdown'] ?? $data['pricing_overview'] ?? ''; },
            'category' => 'payment',
            'example' => 'Adult: $100 x 2, Child: $50 x 1',
            'required_data' => array('pricing_breakdown')
        ));
        
        static::register_placeholder('payment.status', array(
            'description' => 'Payment status',
            'callback' => function($data) { return $data['payment_status'] ?? 'Pending'; },
            'category' => 'payment',
            'example' => 'Paid',
            'required_data' => array('payment_status')
        ));
        
        // Capacity placeholders
        static::register_placeholder('capacity.total', array(
            'description' => 'Total event capacity',
            'callback' => function($data) { return $data['guest_capacity'] ?? 'Unlimited'; },
            'category' => 'capacity',
            'example' => '100',
            'required_data' => array('guest_capacity')
        ));
        
        static::register_placeholder('capacity.remaining', array(
            'description' => 'Remaining event capacity',
            'callback' => function($data) { return $data['remaining_capacity'] ?? 'N/A'; },
            'category' => 'capacity',
            'example' => '25',
            'required_data' => array('remaining_capacity')
        ));
        
        // Set default values
        static::set_default_value('event.title', '[Event Title]');
        static::set_default_value('attendee.name', '[Attendee Name]');
        static::set_default_value('payment.total', '$0.00');
    }
    
    /**
     * Format currency value for events
     */
    private static function format_currency($amount) {
        $currency_symbol = get_option('currency_symbol', '$');
        $currency_format = get_option('currency_format', 2);
        
        return $currency_symbol . number_format(floatval($amount), $currency_format);
    }
}
