<?php
/*
Plugin Name: SCT Event Administration
Description: This WordPress plugin manages events and event registrations with integrated email communication capabilities. It's designed to handle event management workflows including registration tracking and automated email notifications.
Version: 1.9
Author: Massimo Biondi
Author URI: https://massimo.tokyo/
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: sct-event-administration
*/

if (!defined('ABSPATH')) exit;

define('EVENT_ADMIN_PATH', plugin_dir_path(__FILE__));
define('EVENT_ADMIN_URL', plugin_dir_url(__FILE__));
define('EVENT_ADMIN_VERSION', '1.8');

// Create database tables on activation
function event_admin_activate() {
    ob_start();

    global $wpdb;

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE {$wpdb->prefix}sct_events (
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
        member_price decimal(10,2) DEFAULT 0.00,
        non_member_price decimal(10,2) DEFAULT 0.00,
        member_only tinyint(1) DEFAULT 0,
        children_counted_separately tinyint(1) DEFAULT 0,
        by_lottery tinyint(1) DEFAULT 0,
        custom_email_template longtext DEFAULT NULL,
        PRIMARY KEY  (id)
    ) ENGINE=INNODB $charset_collate;";
    dbDelta($sql);
    
    $sql = "CREATE TABLE {$wpdb->prefix}sct_event_registrations (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        event_id mediumint(9) NOT NULL,
        name varchar(255) NOT NULL,
        email varchar(255) NOT NULL,
        guest_count int NOT NULL,
        member_guests int NOT NULL DEFAULT 0,
        non_member_guests int NOT NULL DEFAULT 0,
        children_guests int NOT NULL DEFAULT 0,
        registration_date datetime DEFAULT CURRENT_TIMESTAMP,
        is_winner tinyint(1) DEFAULT 0,
        unique_identifier varchar(255) NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY unique_event_email (event_id, email)
    ) ENGINE=INNODB $charset_collate;";
    dbDelta($sql);

    $sql = "CREATE TABLE {$wpdb->prefix}sct_event_emails (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        registration_id mediumint(9) NOT NULL,
        event_id mediumint(9) NOT NULL,
        email_type varchar(50) NOT NULL,
        subject text NOT NULL,
        message text NOT NULL,
        sent_date datetime NOT NULL,
        status varchar(20) NOT NULL,
        PRIMARY KEY (id),
        KEY registration_id (registration_id),
        FOREIGN KEY (event_id) REFERENCES {$wpdb->prefix}sct_events(id) ON DELETE CASCADE,
        FOREIGN KEY (registration_id) REFERENCES {$wpdb->prefix}sct_event_registrations(id) ON DELETE CASCADE
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
    $management_query = new WP_Query(array(
        'post_type' => 'page',
        'name' => 'sct-management',
        'post_status' => 'any',
        'posts_per_page' => 1
    ));
    $management_page = $management_query->have_posts() ? $management_query->posts[0] : null;
    if (!$management_page) {
        $management_page_args = array(
            'post_title'    => 'Management',
            'post_name'     => 'sct-management',
            'post_content'  => '[event_management]',
            'post_status'   => 'draft',
            'post_type'     => 'page',
            'post_author'   => get_current_user_id()
        );
        wp_insert_post($management_page_args);
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
}
add_action('plugins_loaded', 'event_admin_init');

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
        member_price decimal(10,2) DEFAULT 0.00,
        non_member_price decimal(10,2) DEFAULT 0.00,
        member_only tinyint(1) DEFAULT 0,
        children_counted_separately tinyint(1) DEFAULT 0,
        by_lottery tinyint(1) DEFAULT 0,
        custom_email_template longtext DEFAULT NULL,
        PRIMARY KEY  (id)
    ) ENGINE=INNODB $charset_collate;";
    dbDelta($sql);

    // Update the sct_event_registrations table
    $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}sct_event_registrations (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        event_id mediumint(9) NOT NULL,
        name varchar(255) NOT NULL,
        email varchar(255) NOT NULL,
        guest_count int NOT NULL,
        member_guests int NOT NULL DEFAULT 0,
        non_member_guests int NOT NULL DEFAULT 0,
        children_guests int NOT NULL DEFAULT 0,
        registration_date datetime DEFAULT CURRENT_TIMESTAMP,
        is_winner tinyint(1) DEFAULT 0,
        unique_identifier varchar(255) NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY unique_event_email (event_id, email)
    ) ENGINE=INNODB $charset_collate;";
    dbDelta($sql);

    // Update the sct_event_emails table
    $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}sct_event_emails (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        registration_id mediumint(9) NOT NULL,
        event_id mediumint(9) NOT NULL,
        email_type varchar(50) NOT NULL,
        subject text NOT NULL,
        message text NOT NULL,
        sent_date datetime NOT NULL,
        status varchar(20) NOT NULL,
        PRIMARY KEY (id),
        KEY registration_id (registration_id),
        FOREIGN KEY (event_id) REFERENCES {$wpdb->prefix}sct_events(id) ON DELETE CASCADE,
        FOREIGN KEY (registration_id) REFERENCES {$wpdb->prefix}sct_event_registrations(id) ON DELETE CASCADE
    ) ENGINE=INNODB $charset_collate;";
    dbDelta($sql);
}

function event_admin_set_default_options() {
    $eventAdminInit = new EventAdmin();
    $sct_settings = get_option('event_admin_settings', array(
        'event_registration_page' => get_registration_page_id(),
        'event_management_page' => get_management_page_id(),
        'admin_email' => get_option('admin_email'),
        'currency' => 'USD',
        'notification_subject' => 'New Event Registration: {event_name}',
        'notification_template' => $eventAdminInit->getDefaultNotificationTemplate(),
        'confirmation_subject' => 'Registration Confirmation: {event_name}',
        'confirmation_template' => $eventAdminInit->getDefaultConfirmationTemplate()
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

function get_management_page_id() {
    global $wpdb;

    $page_id = $wpdb->get_var($wpdb->prepare(
        "SELECT ID FROM $wpdb->posts
        WHERE post_title = %s
        AND post_type = 'page'
        AND post_status IN ('publish', 'draft')
        LIMIT 1",
        'Event Management'
    ));

    return $page_id ? (int)$page_id : null;
}

function event_admin_load_textdomain() {
    load_plugin_textdomain(
        'sct-event-administration',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );
}
add_action('plugins_loaded', 'event_admin_load_textdomain');

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
    $current_date = current_time('Y-m-d');
    $events = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}sct_events 
        WHERE DATE(CONCAT(event_date, ' ', event_time)) >= %s
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