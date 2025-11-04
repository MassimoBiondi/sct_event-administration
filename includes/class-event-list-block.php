<?php
/**
 * Events List Gutenberg Block
 * 
 * Registers and handles the Events List block for the block editor
 */

if (!defined('ABSPATH')) {
    exit;
}

class SCT_Event_List_Block {
    
    public function __construct() {
        add_action('init', array($this, 'register_block'));
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_editor_assets'));
    }
    
    /**
     * Register the Events List block
     */
    public function register_block() {
        register_block_type(
            'sct-events/list',
            array(
                'render_callback' => array($this, 'render_block'),
                'attributes' => array(
                    'numberOfEvents' => array(
                        'type' => 'number',
                        'default' => 10,
                    ),
                    'sortBy' => array(
                        'type' => 'string',
                        'default' => 'date_asc',
                    ),
                    'showDescription' => array(
                        'type' => 'boolean',
                        'default' => true,
                    ),
                    'showLocation' => array(
                        'type' => 'boolean',
                        'default' => true,
                    ),
                    'showDate' => array(
                        'type' => 'boolean',
                        'default' => true,
                    ),
                ),
            )
        );
    }
    
    /**
     * Enqueue editor assets
     */
    public function enqueue_editor_assets() {
        wp_enqueue_script(
            'sct-event-list-block-editor',
            EVENT_ADMIN_URL . 'admin/js/event-list-block.js',
            array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components'),
            EVENT_ADMIN_VERSION
        );
        
        wp_enqueue_style(
            'sct-event-list-block-editor',
            EVENT_ADMIN_URL . 'admin/css/event-list-block-editor.css',
            array(),
            EVENT_ADMIN_VERSION
        );
    }
    
    /**
     * Render the block on the frontend
     */
    public function render_block($attributes) {
        global $wpdb;
        
        $number = isset($attributes['numberOfEvents']) ? intval($attributes['numberOfEvents']) : 10;
        $sort_by = isset($attributes['sortBy']) ? sanitize_text_field($attributes['sortBy']) : 'date_asc';
        $show_description = isset($attributes['showDescription']) ? (bool) $attributes['showDescription'] : true;
        $show_location = isset($attributes['showLocation']) ? (bool) $attributes['showLocation'] : true;
        $show_date = isset($attributes['showDate']) ? (bool) $attributes['showDate'] : true;
        
        // Build query
        $table = $wpdb->prefix . 'sct_events';
        
        // Only show future/current events (exclude past events)
        $query = "SELECT * FROM $table";
        $query .= " WHERE (publish_date IS NULL OR publish_date <= NOW())";
        $query .= " AND (unpublish_date IS NULL OR unpublish_date >= NOW())";
        $query .= " AND COALESCE(event_end_date, event_date) >= CURDATE()";
        
        // Sort order
        switch ($sort_by) {
            case 'date_desc':
                $query .= " ORDER BY event_date DESC";
                break;
            case 'name_asc':
                $query .= " ORDER BY event_name ASC";
                break;
            case 'name_desc':
                $query .= " ORDER BY event_name DESC";
                break;
            case 'date_asc':
            default:
                $query .= " ORDER BY event_date ASC";
                break;
        }
        
        $query .= " LIMIT " . intval($number);
        
        $events = $wpdb->get_results($query);
        
        if (empty($events)) {
            return '<div class="sct-events-list-block"><p>' . esc_html__('No upcoming events.', 'sct-event-administration') . '</p></div>';
        }
        
        $output = '<div class="sct-events-list-block">';
        $event_count = count($events);
        
        if ($event_count > 3) {
            // Use slider for more than 3 events
            $output .= '<div class="sct-events-slider-container uk-position-relative">';
            $output .= '<div uk-slider="center: false; finite: true; velocity: 1">';
            
            // Slider Navigation
            $output .= '<div class="uk-flex uk-flex-between uk-flex-middle uk-margin-bottom">';
            $output .= '<div class="slider-navigation">';
            $output .= '<a class="uk-slidenav-previous" href uk-slidenav-previous uk-slider-item="previous"></a>';
            $output .= '<a class="uk-slidenav-next" href uk-slidenav-next uk-slider-item="next"></a>';
            $output .= '</div>';
            $output .= '<div class="slider-info">';
            $output .= '<span class="uk-text-meta">' . sprintf(esc_html__('Showing %d events', 'sct-event-administration'), count($events)) . '</span>';
            $output .= '</div>';
            $output .= '</div>';
            
            $output .= '<div class="uk-slider-container">';
            $output .= '<ul class="sct-events-grid uk-slider-items uk-child-width-1-3@m uk-child-width-1-2@s uk-child-width-1-1 uk-grid uk-grid-match" uk-grid>';
            
            foreach ($events as $event) {
                $output .= '<li>';
                $output .= $this->render_event_item($event, $show_description, $show_location, $show_date);
                $output .= '</li>';
            }
            
            $output .= '</ul>';
            $output .= '</div>';
            
            // Dot Navigation
            $output .= '<ul class="uk-slider-nav uk-dotnav uk-flex-center uk-margin-top"></ul>';
            
            $output .= '</div>';
            $output .= '</div>';
        } else {
            // Use grid for 3 or fewer events
            // Determine width class based on event count
            if ($event_count === 1 || $event_count === 2) {
                $width_class = 'uk-child-width-1-2';
            } else {
                // Exactly 3 events
                $width_class = 'uk-child-width-1-3';
            }
            
            $output .= '<div class="sct-events-grid-container">';
            $output .= '<div class="sct-events-grid ' . $width_class . '" uk-grid="masonry: false" uk-height-match="target: > .sct-event-item">';
            
            foreach ($events as $event) {
                $output .= $this->render_event_item($event, $show_description, $show_location, $show_date);
            }
            
            $output .= '</div>';
            $output .= '</div>';
        }
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Get event thumbnail URL
     * Falls back to first image in description if no thumbnail set
     */
    private function get_event_thumbnail($event) {
        // Use explicit thumbnail if set
        if (!empty($event->thumbnail_url)) {
            return $event->thumbnail_url;
        }
        
        // Fall back to first image in description
        if (!empty($event->description)) {
            $description = wp_unslash($event->description);
            
            // Look for img tags
            if (preg_match('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $description, $matches)) {
                return esc_url($matches[1]);
            }
            
            // Look for WordPress image shortcodes
            if (preg_match('/\[wp-image-(\d+)[^\]]*\]/', $description, $matches)) {
                $img = wp_get_attachment_image_url($matches[1], 'medium');
                if ($img) {
                    return esc_url($img);
                }
            }
        }
        
        return null;
    }
    
    /**
     * Render individual event item
     */
    private function render_event_item($event, $show_description, $show_location, $show_date) {
        global $wpdb;
        
        // Calculate available capacity
        $registered_guests = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(guest_count) FROM {$wpdb->prefix}sct_event_registrations WHERE event_id = %d",
            $event->id
        ));
        $registered_guests = $registered_guests ?: 0;
        $available_capacity = $event->guest_capacity > 0 ? $event->guest_capacity - $registered_guests : -1;
        $is_fully_booked = $event->guest_capacity > 0 && $available_capacity <= 0;
        $is_low_capacity = $event->guest_capacity > 0 && $available_capacity > 0 && $available_capacity < 5;
        
        // Get thumbnail (with fallback to description image)
        $thumbnail = $this->get_event_thumbnail($event);
        
        // Get registration page URL
        $settings = get_option('event_admin_settings', []);
        $registration_page_id = isset($settings['event_registration_page']) ? $settings['event_registration_page'] : null;
        if (!$registration_page_id) {
            $registration_page_id = get_the_ID();
        }
        $registration_url = get_permalink($registration_page_id);
        
        $output = '<div class="sct-event-item uk-card uk-card-default">';
        
        // Thumbnail
        if ($thumbnail) {
            $output .= '<div class="uk-card-media-top">';
            $output .= '<img src="' . esc_url($thumbnail) . '" alt="' . esc_attr($event->event_name) . '" />';
            $output .= '</div>';
        }
        
        // Event content wrapper
        $output .= '<div class="uk-card-body">';
        
        // Event title
        $output .= '<h3 class="sct-event-title">';
        $output .= esc_html($event->event_name);
        $output .= '</h3>';
        
        // Date and time
        if ($show_date) {
            $output .= '<div class="sct-event-meta">';
            $output .= '<span class="sct-event-date">';
            // Use the EventPublic formatter for consistent date range display
            $event_public = new EventPublic();
            $output .= esc_html($event_public::format_event_date_range($event));
            $output .= '</span>';
            $output .= '</div>';
        }
        
        // Location
        if ($show_location && !empty($event->location_name)) {
            $output .= '<div class="sct-event-location">';
            $output .= '<strong>' . esc_html__('Location:', 'sct-event-administration') . '</strong> ';
            $output .= esc_html($event->location_name);
            if (!empty($event->location_link)) {
                $output .= ' <a href="' . esc_url($event->location_link) . '" target="_blank" rel="noopener noreferrer">';
                $output .= esc_html__('View on Map', 'sct-event-administration');
                $output .= '</a>';
            }
            $output .= '</div>';
        }
        
        // Description
        if ($show_description && !empty($event->description)) {
            $output .= '<div class="sct-event-description">';
            $output .= wp_kses_post(wp_trim_words($event->description, 20));
            $output .= '</div>';
        }
        
        $output .= '</div><!-- uk-card-body -->';
        
        // Capacity status notices (moved above button for better visibility)
        if ($is_fully_booked) {
            $output .= '<div class="sct-event-notice sct-event-fully-booked">';
            $output .= '<strong>' . esc_html__('❌ Fully Booked', 'sct-event-administration') . '</strong>';
            $output .= '</div>';
        } elseif ($is_low_capacity) {
            $output .= '<div class="sct-event-notice sct-event-low-capacity">';
            $output .= '<strong>' . sprintf(
                esc_html__('⚠ Only %d spot(s) left', 'sct-event-administration'),
                $available_capacity
            ) . '</strong>';
            $output .= '</div>';
        }
        
        // Register button (only show if event is NOT fully booked)
        if (!$is_fully_booked) {
            $output .= '<div class="sct-event-footer">';
            
            if (!empty($event->external_registration) && !empty($event->external_registration_url)) {
                // External registration
                $output .= '<a href="' . esc_url($event->external_registration_url) . '" ';
                $output .= 'target="_blank" rel="noopener noreferrer" ';
                $output .= 'class="sct-register-button sct-register-external">';
                $output .= esc_html(!empty($event->external_registration_text) ? $event->external_registration_text : 'Register');
                $output .= ' <span class="sct-external-icon">↗</span>';
                $output .= '</a>';
            } else {
                // Internal registration
                $register_url = add_query_arg('id', $event->id, $registration_url);
                $output .= '<a href="' . esc_url($register_url) . '" class="sct-register-button sct-register-internal">';
                $output .= esc_html__('Register', 'sct-event-administration');
                $output .= '</a>';
            }
            
            $output .= '</div><!-- sct-event-footer -->';
        }
        
        $output .= '</div><!-- sct-event-item -->';
        
        return $output;
    }
}

// Initialize the block
new SCT_Event_List_Block();
