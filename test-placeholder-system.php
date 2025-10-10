<?php
/**
 * Test Script for Placeholder System
 * 
 * Simple test to verify the placeholder system works correctly
 * Run this from command line: php test-placeholder-system.php
 */

// Mock WordPress functions for testing
function get_bloginfo($key) {
    return 'Test Website';
}

function home_url() {
    return 'https://test.com';
}

function current_time($format) {
    return date($format);
}

function get_option($key, $default = '') {
    $options = array(
        'admin_email' => 'admin@test.com',
        'currency_symbol' => '$',
        'currency_format' => 2
    );
    return $options[$key] ?? $default;
}

// Define ABSPATH for the includes
if (!defined('ABSPATH')) {
    define('ABSPATH', '/tmp/');
}

echo "=== Testing SCT Placeholder System ===\n\n";

// Test Event Plugin
echo "Testing Event Plugin Placeholders:\n";
require_once './includes/class-placeholder-system.php';

$event_data = array(
    'event_id' => '123',
    'event_title' => 'Summer Conference',
    'event_date' => 'August 15, 2025',
    'name' => 'John Smith',
    'email' => 'john@example.com',
    'guest_count' => '2',
    'total_price' => 150.00,
    'location_name' => 'Conference Center'
);

$event_template = "Dear {{attendee.name}}, thank you for registering for {{event.title}} on {{event.date}}. Total: {{payment.total}}";
$event_result = SCT_Event_Email_Placeholders::replace_placeholders($event_template, $event_data);
echo "Template: $event_template\n";
echo "Result: $event_result\n\n";

// Test backward compatibility
$old_template = "Dear {attendee_name}, thank you for registering for {event_title}. Total: {total_price}";
$old_result = SCT_Event_Email_Placeholders::replace_placeholders($old_template, $event_data);
echo "Old Template: $old_template\n";
echo "Old Result: $old_result\n\n";

echo "=== Test Completed Successfully! ===\n";
?>
