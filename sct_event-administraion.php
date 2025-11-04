<?php
/*
Plugin Name: SCT Events
Plugin URI: https://github.com/MassimoBiondi/sct_event-administration
Description: Advanced WordPress event management plugin with multi-day events, event registrations, integrated email notifications, and a Gutenberg Events List block. Features include event capacity management, lottery-based registration, waiting lists, and registration email templates with dynamic placeholders. Contains Icons; lottery wheel by bsd studio from <a href="https://thenounproject.com/browse/icons/term/lottery-wheel/" target="_blank" title="lottery wheel Icons">Noun Project</a> (CC BY 3.0) / User by Lucas del Río from <a href="https://thenounproject.com/browse/icons/term/user/" target="_blank" title="User Icons">Noun Project</a> (CC BY 3.0)
Version: 2.10.9
Author: Massimo Biondi
Author URI: https://massimo.tokyo/
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: sct-event-administration
GitHub Plugin URI: MassimoBiondi/sct_event-administration
*/

if (!defined('ABSPATH')) exit;

define('EVENT_ADMIN_PATH', plugin_dir_path(__FILE__));
define('EVENT_ADMIN_URL', plugin_dir_url(__FILE__));
define('EVENT_ADMIN_VERSION', '2.10.9');

// Load email utilities and placeholder system
require_once EVENT_ADMIN_PATH . 'includes/class-email-utilities.php';
require_once EVENT_ADMIN_PATH . 'includes/class-placeholder-system.php';
require_once EVENT_ADMIN_PATH . 'includes/class-github-updater.php';
require_once EVENT_ADMIN_PATH . 'includes/class-event-list-block.php';

// Create database tables on activation
function event_admin_activate() {
    ob_start();

    global $wpdb;

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    $charset_collate = $wpdb->get_charset_collate();

    // Drop the sct_event_emails table if it exists
    // $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}sct_event_emails");
    // $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}sct_event_registrations");
    // $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}sct_events");

    
    $sql = "CREATE TABLE {$wpdb->prefix}sct_events (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        event_name varchar(255) NOT NULL,
        event_date date NOT NULL,
        event_time time,
        event_end_date date,
        event_end_time time,
        location_name varchar(255) NOT NULL,
        description longtext,
        location_link varchar(255),
        guest_capacity int NOT NULL,
        max_guests_per_registration int NOT NULL,
        admin_email varchar(255) NOT NULL,
        pricing_options longtext DEFAULT NULL,
        pricing_description longtext DEFAULT NULL,
        goods_services longtext NULL,
        goods_services_description longtext DEFAULT NULL,
        member_only tinyint(1) DEFAULT 0,
        by_lottery tinyint(1) DEFAULT 0,
        has_waiting_list tinyint(1) DEFAULT 0,
        custom_email_template longtext DEFAULT NULL,
        thumbnail_url varchar(255) DEFAULT NULL,
        publish_date datetime DEFAULT NULL,
        unpublish_date datetime DEFAULT NULL,
        payment_methods text DEFAULT NULL,
        payment_methods_description longtext DEFAULT NULL,
        collect_phone tinyint(1) DEFAULT 0,
        collect_company tinyint(1) DEFAULT 0,
        collect_address tinyint(1) DEFAULT 0,
        comment_fields longtext DEFAULT NULL,
        external_registration tinyint(1) DEFAULT 0,
        external_registration_url varchar(500) DEFAULT NULL,
        external_registration_text varchar(255) DEFAULT NULL,
        PRIMARY KEY  (id)
    ) ENGINE=INNODB $charset_collate;";
    dbDelta($sql);

    // Add missing columns if they don't exist (for existing installations)
    $table_name = $wpdb->prefix . 'sct_events';
    
    // Add comment_fields column if it doesn't exist
    $column_exists = $wpdb->query($wpdb->prepare(
        "SELECT * FROM information_schema.COLUMNS WHERE TABLE_NAME = %s AND COLUMN_NAME = 'comment_fields' AND TABLE_SCHEMA = %s",
        $table_name,
        DB_NAME
    ));
    if (!$column_exists) {
        $wpdb->query("ALTER TABLE $table_name ADD COLUMN comment_fields longtext DEFAULT NULL AFTER collect_address");
    }

    // Add comments column to registrations table if it doesn't exist
    $reg_table_name = $wpdb->prefix . 'sct_event_registrations';
    $reg_column_exists = $wpdb->query($wpdb->prepare(
        "SELECT * FROM information_schema.COLUMNS WHERE TABLE_NAME = %s AND COLUMN_NAME = 'comments' AND TABLE_SCHEMA = %s",
        $reg_table_name,
        DB_NAME
    ));
    if (!$reg_column_exists) {
        $wpdb->query("ALTER TABLE $reg_table_name ADD COLUMN comments longtext DEFAULT NULL AFTER country");
    }
    
    $sql = "CREATE TABLE {$wpdb->prefix}sct_event_registrations (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        event_id mediumint(9) NOT NULL,
        name varchar(255) NOT NULL,
        email varchar(255) NOT NULL,
        guest_count int NOT NULL,
        guest_details longtext DEFAULT NULL,
        goods_services longtext DEFAULT NULL,
        company_name varchar(255) DEFAULT NULL,
        phone varchar(20) DEFAULT NULL,
        address varchar(255) DEFAULT NULL,
        city varchar(100) DEFAULT NULL,
        postal_code varchar(20) DEFAULT NULL,
        country varchar(100) DEFAULT NULL,
        comments longtext DEFAULT NULL,
        registration_date datetime DEFAULT CURRENT_TIMESTAMP,
        is_winner tinyint(1) DEFAULT 0,
        unique_identifier varchar(255) NOT NULL,
        payment_method varchar(255),
        payment_status enum('pending', 'paid', 'failed') DEFAULT 'pending',
        PRIMARY KEY  (id),
        UNIQUE KEY unique_event_email (event_id, email)
    ) ENGINE=INNODB $charset_collate;";
    dbDelta($sql);

    $sql = "CREATE TABLE {$wpdb->prefix}sct_event_emails (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        event_id mediumint(9) NOT NULL,
        email_type varchar(50) NOT NULL,
        recipients text NOT NULL,
        subject text NOT NULL,
        message text NOT NULL,
        sent_date datetime NOT NULL,
        status varchar(20) NOT NULL,
        PRIMARY KEY (id),
        FOREIGN KEY (event_id) REFERENCES {$wpdb->prefix}sct_events(id) ON DELETE CASCADE
    ) ENGINE=INNODB $charset_collate;";
    dbDelta($sql);

    // Create Events page if it doesn't exist
    $events_query = new WP_Query(array(
        'post_type' => 'page',
        'name' => 'sct-events',
        'post_status' => 'any',
        'posts_per_page' => 1
    ));
    $events_page = $events_query->have_posts() ? $events_query->posts[0] : null;
    if (!$events_page) {
        $events_page_args = array(
            'post_title'    => 'Events',
            'post_name'     => 'sct-events',
            'post_content'  => '[event_list]',
            'post_status'   => 'draft',
            'post_type'     => 'page',
            'post_author'   => get_current_user_id()
        );
        wp_insert_post($events_page_args);
    }

    // Create Registration page if it doesn't exist
    $registration_query = new WP_Query(array(
        'post_type' => 'page',
        'name' => 'sct-registration',
        'post_status' => 'any',
        'posts_per_page' => 1
    ));
    $registration_page = $registration_query->have_posts() ? $registration_query->posts[0] : null;
    if (!$registration_page) {
        $registration_page_args = array(
            'post_title'    => 'Registration',
            'post_name'     => 'sct-registration',
            'post_content'  => '[event_registration]',
            'post_status'   => 'draft',
            'post_type'     => 'page',
            'post_author'   => get_current_user_id()
        );
        wp_insert_post($registration_page_args);
    }

    // Create Management page if it doesn't exist
    $reservations_query = new WP_Query(array(
        'post_type' => 'page',
        'name' => 'sct-reservations',
        'post_status' => 'any',
        'posts_per_page' => 1
    ));
    $reservations_page = $reservations_query->have_posts() ? $reservations_query->posts[0] : null;
    if (!$reservations_page) {
        $reservations_page_args = array(
            'post_title'    => 'Reservation Management',
            'post_name'     => 'sct-reservations',
            'post_content'  => '[reservations_management]',
            'post_status'   => 'draft',
            'post_type'     => 'page',
            'post_author'   => get_current_user_id()
        );
        wp_insert_post($reservations_page_args);
    }
    

    // Set default options
    event_admin_set_default_options();

    $output = ob_get_clean();
    if (!empty($output)) {
        // error_log('Plugin activation output: ' . $output);
    }
}
register_activation_hook(__FILE__, 'event_admin_activate');

// Load required files
require_once EVENT_ADMIN_PATH . 'includes/class-event-admin.php';
require_once EVENT_ADMIN_PATH . 'includes/class-event-public.php';
// require_once EVENT_ADMIN_PATH . 'includes/test-scenarios.php';
// require_once EVENT_ADMIN_PATH . 'includes/class-email-handler.php';

// Initialize the plugin
function event_admin_init() {
    if (current_user_can('edit_posts')) {
        new EventAdmin();
    }
    new EventPublic();

    // Check for plugin updates
    event_admin_check_for_updates();

    // Initialize GitHub updater
    if (class_exists('SCT_GitHub_Updater')) {
        new SCT_GitHub_Updater(__FILE__, 'MassimoBiondi', 'sct_event-administration');
    }
}

// Load text domain for translations at init action as required by WordPress 6.7.0
add_action('init', function() {
    load_plugin_textdomain('sct-event-administration', false, dirname(plugin_basename(__FILE__)) . '/languages');
}, 5); // Priority 5 - load translations early

// Initialize classes after text domain is loaded
add_action('init', 'event_admin_init', 10);

function event_admin_check_for_updates() {
    $current_version = get_option('event_admin_version', '1.0');

    if (version_compare($current_version, EVENT_ADMIN_VERSION, '<')) {
        event_admin_update_database();
        update_option('event_admin_version', EVENT_ADMIN_VERSION);
    }
}

function event_admin_update_database() {
    global $wpdb;

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    $charset_collate = $wpdb->get_charset_collate();

    // Get existing columns
    $columns = $wpdb->get_col("DESCRIBE {$wpdb->prefix}sct_events");
    
    // Add has_waiting_list column if it doesn't exist
    if (!in_array('has_waiting_list', $columns)) {
        $wpdb->query("ALTER TABLE {$wpdb->prefix}sct_events ADD COLUMN has_waiting_list tinyint(1) DEFAULT 0 AFTER by_lottery");
        $columns = $wpdb->get_col("DESCRIBE {$wpdb->prefix}sct_events");
    }

    // Add event_end_date column if it doesn't exist
    if (!in_array('event_end_date', $columns)) {
        $wpdb->query("ALTER TABLE {$wpdb->prefix}sct_events ADD COLUMN event_end_date date AFTER event_time");
        $columns = $wpdb->get_col("DESCRIBE {$wpdb->prefix}sct_events");
    }

    // Add event_end_time column if it doesn't exist
    if (!in_array('event_end_time', $columns)) {
        $wpdb->query("ALTER TABLE {$wpdb->prefix}sct_events ADD COLUMN event_end_time time AFTER event_end_date");
        $columns = $wpdb->get_col("DESCRIBE {$wpdb->prefix}sct_events");
    }

    // Make event_time nullable if it's not already
    $time_column = $wpdb->get_results("SHOW COLUMNS FROM {$wpdb->prefix}sct_events WHERE Field = 'event_time'");
    if (!empty($time_column) && $time_column[0]->Null === 'NO') {
        $wpdb->query("ALTER TABLE {$wpdb->prefix}sct_events MODIFY event_time time");
    }

    // Drop the sct_event_emails table if it exists
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}sct_event_emails");

    // Check if external registration columns exist, if not add them
    if (!in_array('external_registration', $columns)) {
        $wpdb->query("ALTER TABLE {$wpdb->prefix}sct_events ADD COLUMN external_registration tinyint(1) DEFAULT 0 AFTER payment_methods");
        // Refresh columns list after adding new column
        $columns = $wpdb->get_col("DESCRIBE {$wpdb->prefix}sct_events");
    }
    if (!in_array('external_registration_url', $columns)) {
        $wpdb->query("ALTER TABLE {$wpdb->prefix}sct_events ADD COLUMN external_registration_url varchar(500) DEFAULT NULL AFTER external_registration");
        // Refresh columns list after adding new column
        $columns = $wpdb->get_col("DESCRIBE {$wpdb->prefix}sct_events");
    }
    if (!in_array('external_registration_text', $columns)) {
        $wpdb->query("ALTER TABLE {$wpdb->prefix}sct_events ADD COLUMN external_registration_text varchar(255) DEFAULT NULL AFTER external_registration_url");
        // Refresh columns list after adding new column
        $columns = $wpdb->get_col("DESCRIBE {$wpdb->prefix}sct_events");
    }

    // Add pricing_description column if it doesn't exist
    if (!in_array('pricing_description', $columns)) {
        $wpdb->query("ALTER TABLE {$wpdb->prefix}sct_events ADD COLUMN pricing_description LONGTEXT DEFAULT NULL AFTER pricing_options");
        // Refresh columns list after adding new column
        $columns = $wpdb->get_col("DESCRIBE {$wpdb->prefix}sct_events");
    }

    // Add goods_services_description column if it doesn't exist
    if (!in_array('goods_services_description', $columns)) {
        $wpdb->query("ALTER TABLE {$wpdb->prefix}sct_events ADD COLUMN goods_services_description LONGTEXT DEFAULT NULL AFTER goods_services");
        // Refresh columns list after adding new column
        $columns = $wpdb->get_col("DESCRIBE {$wpdb->prefix}sct_events");
    }

    // Add payment_methods_description column if it doesn't exist
    if (!in_array('payment_methods_description', $columns)) {
        $wpdb->query("ALTER TABLE {$wpdb->prefix}sct_events ADD COLUMN payment_methods_description LONGTEXT DEFAULT NULL AFTER payment_methods");
        // Refresh columns list after adding new column
        $columns = $wpdb->get_col("DESCRIBE {$wpdb->prefix}sct_events");
    }

    // Update the sct_events table
    $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}sct_events (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        event_name varchar(255) NOT NULL,
        event_date date NOT NULL,
        event_time time NOT NULL,
        location_name varchar(255) NOT NULL,
        description longtext,
        location_link varchar(255),
        guest_capacity int NOT NULL,
        max_guests_per_registration int NOT NULL,
        admin_email varchar(255) NOT NULL,
        pricing_options longtext DEFAULT NULL,
        goods_services longtext NULL,
        member_only tinyint(1) DEFAULT 0,
        by_lottery tinyint(1) DEFAULT 0,
        has_waiting_list tinyint(1) DEFAULT 0,
        custom_email_template longtext DEFAULT NULL,
        thumbnail_url varchar(255) DEFAULT NULL,
        publish_date datetime DEFAULT NULL,
        unpublish_date datetime DEFAULT NULL,
        payment_methods text DEFAULT NULL,
        external_registration tinyint(1) DEFAULT 0,
        external_registration_url varchar(500) DEFAULT NULL,
        external_registration_text varchar(255) DEFAULT NULL,
        PRIMARY KEY  (id)
    ) ENGINE=INNODB $charset_collate;";
    $result = dbDelta($sql);

    // Update the sct_event_registrations table
    $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}sct_event_registrations (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        event_id mediumint(9) NOT NULL,
        name varchar(255) NOT NULL,
        email varchar(255) NOT NULL,
        guest_count int NOT NULL,
        guest_details longtext DEFAULT NULL,
        goods_services longtext DEFAULT NULL,
        registration_date datetime DEFAULT CURRENT_TIMESTAMP,
        is_winner tinyint(1) DEFAULT 0,
        unique_identifier varchar(255) NOT NULL,
        payment_method varchar(255),
        payment_status enum('pending', 'completed', 'failed') DEFAULT 'pending',
        PRIMARY KEY  (id),
        UNIQUE KEY unique_event_email (event_id, email)
    ) ENGINE=INNODB $charset_collate;";
    $result = dbDelta($sql);

    // Update the sct_event_emails table
    $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}sct_event_emails (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        event_id mediumint(9) NOT NULL,
        email_type varchar(50) NOT NULL,
        recipients text NOT NULL,
        subject text NOT NULL,
        message text NOT NULL,
        sent_date datetime NOT NULL,
        status varchar(20) NOT NULL,
        PRIMARY KEY (id),
        FOREIGN KEY (event_id) REFERENCES {$wpdb->prefix}sct_events(id) ON DELETE CASCADE
    ) ENGINE=INNODB $charset_collate;";
    $result = dbDelta($sql);
    error_log(print_r($result, true));
}

function event_admin_set_default_options() {
    $eventAdminInit = new EventAdmin();
    $sct_settings = get_option('event_admin_settings', array(
        'event_registration_page' => get_registration_page_id(),
        'event_reservations_page' => get_reservations_page_id(),
        'admin_email' => get_option('admin_email'),
        'currency' => 'JPY',
        'notification_subject' => 'New Event Registration: {event_name}',
        'notification_template' => $eventAdminInit->getDefaultNotificationTemplate(),
        'confirmation_subject' => 'Registration Confirmation: {event_name}',
        'confirmation_template' => $eventAdminInit->getDefaultConfirmationTemplate(),
        'start_of_week' => 1,
    ));
    update_option('event_admin_settings', $sct_settings);
    unset($eventAdminInit);
}

function get_registration_page_id() {
    global $wpdb;
    
    $page_id = $wpdb->get_var($wpdb->prepare(
        "SELECT ID FROM $wpdb->posts 
        WHERE post_title = %s 
        AND post_type = 'page' 
        AND post_status IN ('publish', 'draft') 
        LIMIT 1",
        'Registration'
    ));
    
    return $page_id ? (int)$page_id : null;
}

function get_reservations_page_id() {
    global $wpdb;

    $page_id = $wpdb->get_var($wpdb->prepare(
        "SELECT ID FROM $wpdb->posts
        WHERE post_name = %s
        AND post_type = 'page'
        AND post_status IN ('publish', 'draft')
        LIMIT 1",
        'sct-reservations'
    ));

    return $page_id ? (int)$page_id : null;
}

// Removed duplicate text domain loading - already handled above with priority 5

// Register the dashboard widget
function sct_event_dashboard_widget() {
    wp_add_dashboard_widget(
        'sct_event_dashboard_widget', // Widget slug.
        'Event Overview', // Title.
        'sct_event_dashboard_widget_display' // Display function.
    );
}
add_action('wp_dashboard_setup', 'sct_event_dashboard_widget');

// Display function for the dashboard widget
function sct_event_dashboard_widget_display() {
    global $wpdb;

    // Fetch current and future events
    $current_date = date('Y-m-d');
    $events = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}sct_events 
        WHERE COALESCE(event_end_date, event_date) >= %s
        ORDER BY event_date ASC, event_time ASC",
        $current_date
    ));

    if (empty($events)) {
        echo '<p>No upcoming events found.</p>';
        return;
    }

    echo '<table class="widefat fixed striped">';
    echo '<thead><tr><th>Event Name</th><th>Date</th><th>Registrations</th><th>Empty Spaces</th></thead>';
    echo '<tbody>';

    foreach ($events as $event) {
        // Get total registrations for the event
        $total_registered = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(guest_count) FROM {$wpdb->prefix}sct_event_registrations WHERE event_id = %d",
            $event->id
        ));
        $total_registered = $total_registered ?: 0;

        // Calculate remaining capacity
        $remaining_capacity = ($event->guest_capacity > 0) ? $event->guest_capacity - $total_registered : 'Unlimited';

        echo '<tr>';
        echo '<td>' . esc_html($event->event_name) . '</td>';
        echo '<td>' . esc_html(date('Y-m-d', strtotime($event->event_date))) . '</td>';
        echo '<td>' . esc_html($total_registered) . '</td>';
        echo '<td>' . esc_html($remaining_capacity) . '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';
}

class SCT_Events_Calendar_Widget extends WP_Widget {
    public function __construct() {
        parent::__construct(
            'sct_events_calendar_widget',
            'SCT Events Calendar', // Translation will be handled in widget display
            array('description' => 'A calendar of all SCT events') // Translation will be handled in widget display
        );
    }

    public function widget($args, $instance) {
        global $wpdb;

        // Get registration page ID and URL
        $registration_page_id = get_option('event_admin_settings', [])["event_registration_page"];
        $registration_url = get_permalink($registration_page_id);

        // Get current month and year
        $month = isset($_GET['sctcalmonth']) ? intval($_GET['sctcalmonth']) : date('n');
        $year = isset($_GET['sctcalyear']) ? intval($_GET['sctcalyear']) : date('Y');

        // Get first and last day of the month
        $first_day = date('Y-m-01', strtotime("$year-$month-01"));
        $last_day = date('Y-m-t', strtotime($first_day));

        // Fetch all events for this month, respecting publish and unpublish dates
        $events = $wpdb->get_results($wpdb->prepare(
            "SELECT id, event_name, event_date, event_time, publish_date, unpublish_date 
         FROM {$wpdb->prefix}sct_events 
         WHERE event_date BETWEEN %s AND %s
         AND (publish_date IS NULL OR publish_date <= NOW())
         AND (unpublish_date IS NULL OR unpublish_date > NOW())
         ORDER BY event_date ASC",
            $first_day, $last_day
        ));

        // Group events by date
        $events_by_date = [];
        foreach ($events as $event) {
            $events_by_date[$event->event_date][] = $event;
        }

        // Output widget
        echo $args['before_widget'];
        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }

        // Calendar navigation
        $today_year = (int)date('Y');
        $today_month = (int)date('n');
        $prev_month = $month - 1;
        $prev_year = $year;
        if ($prev_month < 1) {
            $prev_month = 12;
            $prev_year--;
        }
        $next_month = $month + 1;
        $next_year = $year;
        if ($next_month > 12) {
            $next_month = 1;
            $next_year++;
        }
        $calendar_url = function($m, $y) {
            return add_query_arg(array('sctcalmonth' => $m, 'sctcalyear' => $y));
        };

        echo '<div class="sct-events-calendar-widget">';
        echo '<div class="sct-calendar-nav">';

        // Only show previous month navigation if not before current month/year
        if ($year > $today_year || ($year == $today_year && $month > $today_month)) {
            echo '<a href="' . esc_url($calendar_url($prev_month, $prev_year)) . '">&laquo;</a> ';
        } else {
            echo '<span class="sct-calendar-nav-disabled" style="color:#ccc;opacity:0.5;cursor:not-allowed;">&laquo;</span> ';
        }

        echo '<span>' . date('F Y', strtotime("$year-$month-01")) . '</span>';
        echo ' <a href="' . esc_url($calendar_url($next_month, $next_year)) . '">&raquo;</a>';
        echo '</div>';

        // Build calendar table
        $first_weekday = date('w', strtotime($first_day));
        $days_in_month = date('t', strtotime($first_day));
        $today = date('Y-m-d');

        // Get start of week setting (default: 0 = Sunday)
        $start_of_week = get_option('event_admin_settings', [])['start_of_week'] ?? 0;

        echo '<table class="sct-calendar-table">';
        echo '<thead><tr>';
                

        // Build days array starting from the selected day, using English abbreviations to avoid early translation calls
        $day_names = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        $day_abbrs = ['S', 'M', 'T', 'W', 'T', 'F', 'S'];
        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $day_index = ($start_of_week + $i) % 7;
            $days[] = $day_abbrs[$day_index];
        }

        // Output table header with English abbreviations
        foreach ($days as $i => $abbr) {
            $day_index = ($start_of_week + $i) % 7;
            $full_day = $day_names[$day_index];
            echo '<th title="' . esc_attr($full_day) . '">' . esc_html($abbr) . '</th>';
        }
        echo '</tr></thead><tbody><tr>';

        // Calculate the weekday of the first day of the month, adjusted for start of week
        $first_weekday = (date('w', strtotime($first_day)) - $start_of_week + 7) % 7;

        // Empty cells before first day
        for ($i = 0; $i < $first_weekday; $i++) {
            echo '<td style="border:none;background:transparent;"></td>';
        }

        // Days of the month
        $cell = $first_weekday; // Track the cell index for correct row breaks
        $unique_id = uniqid('sctcal_');
        for ($day = 1; $day <= $days_in_month; $day++, $cell++) {
            $date = date('Y-m-d', strtotime("$year-$month-$day"));
            echo '<td>';
            $events_today = !empty($events_by_date[$date]) ? $events_by_date[$date] : [];
            $event_count = count($events_today);

            if ($event_count === 1) {
                $event = $events_today[0];
                $event_url = add_query_arg('id', $event->id, $registration_url);
                $event_datetime = $date . ' ' . ($event->event_time ?? '00:00:00');
                if (strtotime($event_datetime) >= time()) {
                    // Use the day number as the link if only one event and it's in the future
                    echo '<a href="' . esc_url($event_url) . '" uk-tooltip="' . esc_attr($event->event_name) . '">' . $day . '</a>';
                } else {
                    // Past event, not clickable
                    echo '<span title="' . esc_attr($event->event_name) . '" uk-tooltip="' . esc_attr($event->event_name) . '" >' . $day . '</span>';
                }
            } elseif ($event_count > 1) {
                // Tooltip with all event names
                $tooltip = '';
                foreach ($events_today as $event) {
                    $tooltip .= esc_html($event->event_name) . "<br>";
                }
                $popup_id = $unique_id . '_' . $day;
                // Day number as popup trigger, with tooltip listing all events
                echo '<a href="#' . esc_attr($popup_id) . '" uk-toggle uk-tooltip="' . esc_attr(trim($tooltip)) . '">' . $day . '</a>';
                // Popup dialog for event selection
                echo '<div id="' . esc_attr($popup_id) . '" class="uk-flex-top" uk-modal>
                        <div class="uk-modal-dialog uk-modal-body uk-margin-auto-vertical" style="min-width:220px;">
                            <button class="uk-modal-close-default" type="button" uk-close></button>
                            <h4>' . esc_html(date('l, F j, Y', strtotime($date))) . '</h4>
                            <ul class="uk-list">';
                foreach ($events_today as $event) {
                    $event_url = add_query_arg('id', $event->id, $registration_url);
                    $event_datetime = $date . ' ' . ($event->event_time ?? '00:00:00');
                    if (strtotime($event_datetime) >= time()) {
                        echo '<li><a href="' . esc_url($event_url) . '" style="text-decoration:none;" uk-tooltip="' . esc_attr($event->event_name) . '">' . esc_html($event->event_name) . '</a></li>';
                    } else {
                        echo '<li><span style="color:#bbb;cursor:not-allowed;" uk-tooltip="' . esc_attr($event->event_name) . '">' . esc_html($event->event_name) . '</span></li>';
                    }
                }
                echo '      </ul>
                        </div>
                    </div>';
            } else {
                // No events: just show the day number
                echo $day;
            }
            echo '</td>';
            // New row after every 7 cells
            if (($cell + 1) % 7 == 0 && $day != $days_in_month) {
                echo '</tr><tr>';
            }
        }

        // Empty cells after last day
        if (($cell) % 7 != 0) {
            for ($i = ($cell) % 7; $i < 7; $i++) {
                echo '<td style="border:none;background:transparent;"></td>';
            }
        }
        echo '</tr></tbody></table>';
        echo '</div>'; // .sct-events-calendar-widget

        echo $args['after_widget'];
    }

    public function form($instance) {
        // Use hardcoded English strings to avoid early translation loading issues in WordPress 6.7.0
        $title = !empty($instance['title']) ? $instance['title'] : 'Events Calendar';
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">Title:</label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text"
                   value="<?php echo esc_attr($title); ?>">
        </p>
        <?php
    }

    public function update($new_instance, $old_instance) {
        $instance = [];
        $instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
        return $instance;
    }
}

// Register the widget
add_action('widgets_init', function() {
    register_widget('SCT_Events_Calendar_Widget');
});
function sct_events_calendar_shortcode($atts) {
    ob_start();
    the_widget('SCT_Events_Calendar_Widget');
    return ob_get_clean();
}
add_shortcode('sct_events_calendar', 'sct_events_calendar_shortcode');

add_filter('the_excerpt', 'do_shortcode');