<?php
/**
 * Fix pricing and goods/services indices in existing events
 * This script reindexes all existing events to use sequential indices (0, 1, 2...)
 * 
 * Run this by accessing: http://yoursite.com/wp-content/plugins/sct_event-administration/fix-pricing-indices.php
 */

// Load WordPress
require_once(__DIR__ . '/../../../wp-load.php');

// Check if user is logged in as admin
if (!is_user_logged_in() || !current_user_can('manage_options')) {
    wp_die('You do not have permission to run this script.');
}

global $wpdb;
$table_name = $wpdb->prefix . 'sct_events';

// Get all events with pricing_options or goods_services
$events = $wpdb->get_results("SELECT ID, pricing_options, goods_services FROM $table_name WHERE pricing_options IS NOT NULL OR goods_services IS NOT NULL");

$fixed_count = 0;
$error_count = 0;

echo '<h2>Fixing Pricing and Goods/Services Indices</h2>';
echo '<p>Processing ' . count($events) . ' events...</p>';
echo '<ul>';

foreach ($events as $event) {
    $update_data = array();
    $needs_update = false;
    
    // Fix pricing_options
    if ($event->pricing_options) {
        $pricing_options = maybe_unserialize($event->pricing_options);
        if (is_array($pricing_options) && !empty($pricing_options)) {
            // Reindex to sequential (0, 1, 2, ...)
            $new_pricing = array_values($pricing_options);
            $serialized = maybe_serialize($new_pricing);
            
            if ($serialized !== $event->pricing_options) {
                $update_data['pricing_options'] = $serialized;
                $needs_update = true;
            }
        }
    }
    
    // Fix goods_services
    if ($event->goods_services) {
        $goods_services = maybe_unserialize($event->goods_services);
        if (is_array($goods_services) && !empty($goods_services)) {
            // Reindex to sequential (0, 1, 2, ...)
            $new_goods = array_values($goods_services);
            $serialized = maybe_serialize($new_goods);
            
            if ($serialized !== $event->goods_services) {
                $update_data['goods_services'] = $serialized;
                $needs_update = true;
            }
        }
    }
    
    if ($needs_update) {
        $result = $wpdb->update($table_name, $update_data, array('ID' => $event->ID));
        if ($result !== false) {
            echo '<li>✓ Fixed event ID ' . $event->ID . '</li>';
            $fixed_count++;
        } else {
            echo '<li>✗ Error fixing event ID ' . $event->ID . ': ' . $wpdb->last_error . '</li>';
            $error_count++;
        }
    }
}

echo '</ul>';
echo '<p><strong>Results:</strong> Fixed ' . $fixed_count . ' events. Errors: ' . $error_count . '</p>';
echo '<p><a href="' . admin_url('admin.php?page=sct-event-list') . '">Back to Events</a></p>';
?>
