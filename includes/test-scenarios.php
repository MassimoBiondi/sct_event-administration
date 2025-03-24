<?php
if (!defined('ABSPATH')) exit;

function create_event_test_scenarios() {
    global $wpdb;
    
    // Test Scenario 1: Basic Event
    $basic_event = array(
        'event_name' => 'Basic Event',
        'event_date' => date('Y-m-d', strtotime('+1 week')),
        'event_time' => '19:00:00',
        'location_name' => 'Main Hall',
        'description' => 'A basic event with standard settings',
        'location_link' => 'https://example.com/location',
        'guest_capacity' => 50,
        'max_guests_per_registration' => 4,
        'admin_email' => get_option('admin_email'),
        'member_price' => 20.00,
        'non_member_price' => 30.00,
        'member_only' => 0,
        'children_counted_separately' => 0,
        'by_lottery' => 0
    );
    
    // Test Scenario 2: Member-Only Event
    $member_only_event = array(
        'event_name' => 'Members Only Event',
        'event_date' => date('Y-m-d', strtotime('+2 weeks')),
        'event_time' => '18:00:00',
        'location_name' => 'Private Room',
        'description' => 'An exclusive event for members only',
        'location_link' => 'https://example.com/private',
        'guest_capacity' => 30,
        'max_guests_per_registration' => 2,
        'admin_email' => get_option('admin_email'),
        'member_price' => 15.00,
        'non_member_price' => 0.00,
        'member_only' => 1,
        'children_counted_separately' => 0,
        'by_lottery' => 0
    );
    
    // Test Scenario 3: Lottery Event
    $lottery_event = array(
        'event_name' => 'Lottery Event',
        'event_date' => date('Y-m-d', strtotime('+3 weeks')),
        'event_time' => '20:00:00',
        'location_name' => 'Grand Hall',
        'description' => 'A popular event with lottery registration',
        'location_link' => 'https://example.com/grand',
        'guest_capacity' => 100,
        'max_guests_per_registration' => 2,
        'admin_email' => get_option('admin_email'),
        'member_price' => 25.00,
        'non_member_price' => 35.00,
        'member_only' => 0,
        'children_counted_separately' => 0,
        'by_lottery' => 1
    );
    
    // Test Scenario 4: Family Event with Children
    $family_event = array(
        'event_name' => 'Family Event',
        'event_date' => date('Y-m-d', strtotime('+4 weeks')),
        'event_time' => '14:00:00',
        'location_name' => 'Family Center',
        'description' => 'A family-friendly event with separate children counting',
        'location_link' => 'https://example.com/family',
        'guest_capacity' => 80,
        'max_guests_per_registration' => 6,
        'admin_email' => get_option('admin_email'),
        'member_price' => 30.00,
        'non_member_price' => 40.00,
        'member_only' => 0,
        'children_counted_separately' => 1,
        'by_lottery' => 0
    );
    
    // Test Scenario 5: Free Event
    $free_event = array(
        'event_name' => 'Free Community Event',
        'event_date' => date('Y-m-d', strtotime('+5 weeks')),
        'event_time' => '15:00:00',
        'location_name' => 'Community Center',
        'description' => 'A free event for the community',
        'location_link' => 'https://example.com/community',
        'guest_capacity' => 200,
        'max_guests_per_registration' => 5,
        'admin_email' => get_option('admin_email'),
        'member_price' => 0.00,
        'non_member_price' => 0.00,
        'member_only' => 0,
        'children_counted_separately' => 0,
        'by_lottery' => 0
    );
    
    // Array of all test scenarios
    $test_scenarios = array(
        $basic_event,
        $member_only_event,
        $lottery_event,
        $family_event,
        $free_event
    );
    
    // Insert test events into database
    foreach ($test_scenarios as $event) {
        $wpdb->insert(
            $wpdb->prefix . 'sct_events',
            $event,
            array(
                '%s', // event_name
                '%s', // event_date
                '%s', // event_time
                '%s', // location_name
                '%s', // description
                '%s', // location_link
                '%d', // guest_capacity
                '%d', // max_guests_per_registration
                '%s', // admin_email
                '%f', // member_price
                '%f', // non_member_price
                '%d', // member_only
                '%d', // children_counted_separately
                '%d'  // by_lottery
            )
        );
    }
    
    return count($test_scenarios);
}

// Function to clean up test data
function cleanup_test_scenarios() {
    global $wpdb;
    
    // Delete all test events
    $wpdb->query("DELETE FROM {$wpdb->prefix}sct_events WHERE event_name LIKE '%Test%'");
    
    // Delete related registrations
    $wpdb->query("DELETE FROM {$wpdb->prefix}sct_event_registrations WHERE event_id NOT IN (SELECT id FROM {$wpdb->prefix}sct_events)");
    
    // Delete related emails
    $wpdb->query("DELETE FROM {$wpdb->prefix}sct_event_emails WHERE event_id NOT IN (SELECT id FROM {$wpdb->prefix}sct_events)");
}

// Add admin menu for test scenarios
function add_test_scenarios_menu() {
    add_submenu_page(
        'event-admin',
        'Test Scenarios',
        'Test Scenarios',
        'manage_options',
        'event-admin-test-scenarios',
        'render_test_scenarios_page'
    );
}
add_action('admin_menu', 'add_test_scenarios_menu', 20);

// Render the test scenarios page
function render_test_scenarios_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }
    
    if (isset($_POST['create_test_scenarios'])) {
        $count = create_event_test_scenarios();
        echo '<div class="notice notice-success"><p>' . sprintf(__('Created %d test events successfully.'), $count) . '</p></div>';
    }
    
    if (isset($_POST['cleanup_test_scenarios'])) {
        cleanup_test_scenarios();
        echo '<div class="notice notice-success"><p>' . __('Test data cleaned up successfully.') . '</p></div>';
    }
    ?>
    <div class="wrap">
        <h1><?php _e('Event Test Scenarios'); ?></h1>
        <p><?php _e('This page allows you to create and clean up test events with various configurations.'); ?></p>
        
        <form method="post" action="">
            <?php wp_nonce_field('event_test_scenarios'); ?>
            <p>
                <input type="submit" name="create_test_scenarios" class="button button-primary" value="<?php _e('Create Test Events'); ?>">
            </p>
            <p>
                <input type="submit" name="cleanup_test_scenarios" class="button button-secondary" value="<?php _e('Clean Up Test Data'); ?>">
            </p>
        </form>
        
        <h2><?php _e('Test Scenarios Description'); ?></h2>
        <ul>
            <li><strong><?php _e('Basic Event'); ?></strong>: <?php _e('Standard event with member and non-member pricing'); ?></li>
            <li><strong><?php _e('Members Only Event'); ?></strong>: <?php _e('Exclusive event for members only'); ?></li>
            <li><strong><?php _e('Lottery Event'); ?></strong>: <?php _e('Popular event with lottery-based registration'); ?></li>
            <li><strong><?php _e('Family Event'); ?></strong>: <?php _e('Family-friendly event with separate children counting'); ?></li>
            <li><strong><?php _e('Free Event'); ?></strong>: <?php _e('Community event with no admission fee'); ?></li>
        </ul>
    </div>
    <?php
} 