<?php
/*
Plugin Name: SCT Event Administration
Description: This WordPress plugin manages events and event registrations with integrated email communication capabilities. It's designed to handle event management workflows including registration tracking and automated email notifications.
Version: 1.4
Author: Massimo Biondi
Author URI: https://massimo.tokyo/
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: sct-event-administration
*/

if (!defined('ABSPATH')) exit;

define('EVENT_ADMIN_PATH', plugin_dir_path(__FILE__));
define('EVENT_ADMIN_URL', plugin_dir_url(__FILE__));

// Create database tables on activation
function event_admin_activate() {
    ob_start();

    global $wpdb;

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    $charset_collate = $wpdb->get_charset_collate();
    
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
        PRIMARY KEY  (id)
    ) ENGINE=INNODB $charset_collate;";
    dbDelta($sql);
    
    $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}sct_event_registrations (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        event_id mediumint(9) NOT NULL,
        name varchar(255) NOT NULL,
        email varchar(255) NOT NULL,
        guest_count int NOT NULL,
        registration_date datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY unique_event_email (event_id, email)
    ) ENGINE=INNODB $charset_collate;";
    dbDelta($sql);

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

    // Create Events page if it doesn't exist
    $events_page = get_page_by_title('Events');
    if (!$events_page) {
        $events_page_args = array(
            'post_title'    => 'Events',
            'post_content'  => '[event_list]',
            'post_status'   => 'draft',
            'post_type'     => 'page',
            'post_author'   => get_current_user_id()
        );
        wp_insert_post($events_page_args);
    }

    // Create Registration page if it doesn't exist
    $registration_page = get_page_by_title('Registration');
    if (!$registration_page) {
        $registration_page_args = array(
            'post_title'    => 'Registration',
            'post_content'  => '[event_registration]',
            'post_status'   => 'draft',
            'post_type'     => 'page',
            'post_author'   => get_current_user_id()
        );
        wp_insert_post($registration_page_args);
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
require_once EVENT_ADMIN_PATH . 'includes/class-email-handler.php';

// Initialize the plugin
function event_admin_init() {
    if (is_admin()) {
        new EventAdmin();
    }
    new EventPublic();
}
add_action('plugins_loaded', 'event_admin_init');

function event_admin_set_default_options() {
    $eventAdminInit = new EventAdmin();
    $sct_settings = get_option('event_admin_settings', array(
        'event_registration_page' => get_registration_page_id(),
        'admin_email' => get_option('admin_email'),
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

function event_admin_load_textdomain() {
    load_plugin_textdomain(
        'sct-event-administration',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );
}
add_action('plugins_loaded', 'event_admin_load_textdomain');