<?php
if (!defined('ABSPATH')) exit;


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