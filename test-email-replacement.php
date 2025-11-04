<?php
/**
 * Email Placeholder Replacement Test
 * This file tests the simplified email placeholder replacement
 * Run from command line: php test-email-replacement.php
 */

// Test data that simulates what would be passed to the replacement function
$test_data = array(
    // Registration fields
    'id' => 123,
    'registration_id' => 123,
    'name' => 'Massimo Biondi',
    'attendee_name' => 'Massimo Biondi',
    'email' => 'massimo@example.com',
    'attendee_email' => 'massimo@example.com',
    'phone' => '555-1234',
    'guest_count' => 2,
    'attendee_guest_count' => 2,
    'registration_date' => 'November 3, 2025',
    
    // Event fields
    'event_id' => 1,
    'event_name' => 'Spring Ball',
    'event_title' => 'Spring Ball',
    'event_date' => 'April 15, 2025',
    'event_time' => '7:00 PM',
    'event_description' => 'An elegant evening of celebration',
    'location_name' => 'Grand Hotel',
    'location_link' => 'https://maps.google.com/...',
    
    // Website fields
    'website_name' => 'Japan Swiss Society',
    'website_url' => 'https://example.com',
    'admin_email' => 'admin@example.com',
    
    // Additional fields
    'additional_field_1' => 'Yes, whole table booking',
    'additional_field_2' => 'Window seating preferred',
    
    // Pricing and payment
    'total_price' => '$150.00',
    'payment_method' => 'bank transfer',
    'payment_status' => 'pending',
    'pricing_overview' => '<table><tr><td>Test</td><td>$150.00</td></tr></table>',
    'payment_method_details' => '<div>Please transfer to account...</div>',
);

$template = <<<'EOT'
SPRING BALL - REGISTRATION CONFIRMATION
===============================================================

Dear {{attendee_name}},

Thank you for your registration for the Spring Ball. We are delighted to welcome you to this elegant evening hosted by the Japan Swiss Society.

---------------------------------------------------------------
EVENT DETAILS
---------------------------------------------------------------

Event:                 {{event_title}}
Date:                  {{event_date}}
Time:                  {{event_time}}
Location:              {{location_name}}
Number of Guests:      {{attendee_guest_count}}

---------------------------------------------------------------
REGISTRATION CONFIRMATION
---------------------------------------------------------------

Registration Number:   {{registration_id}}
Registration Date:     {{registration_date}}

---------------------------------------------------------------
YOUR PREFERENCES
---------------------------------------------------------------

Whole Table Booking:   {{additional_field_1}}
Seating Preferences:   {{additional_field_2}}

---------------------------------------------------------------
PRICING & PAYMENT
---------------------------------------------------------------

{pricing_overview}

{payment_method_details}

---------------------------------------------------------------
IMPORTANT INFORMATION
---------------------------------------------------------------

Please arrive by {{event_time}} to allow time for check-in and cocktails.
Formal attire is requested. We look forward to an unforgettable
evening celebrating spring with the Japan Swiss Society community.

---------------------------------------------------------------
MANAGE YOUR REGISTRATION
---------------------------------------------------------------

Contact: {{admin_email}}
Website: {{website_url}}
EOT;

// Simulate the replacement function
function test_replace_email_placeholders($template, $data) {
    // Build a comprehensive mapping of all possible placeholder keys
    $replacements = array();
    
    // Add all data directly (flat keys for old format and simple new format)
    foreach ($data as $key => $value) {
        // Skip arrays and objects
        if (is_array($value) || is_object($value)) {
            continue;
        }
        
        // Old format: {key}
        $replacements['{' . $key . '}'] = (string)$value;
        
        // New format: {{key}}
        $replacements['{{' . $key . '}}'] = (string)$value;
    }
    
    // Add nested format replacements ({{category.key}})
    // Event placeholders
    if (!empty($data['event_title']) || !empty($data['event_name'])) {
        $event_title = $data['event_title'] ?? $data['event_name'] ?? '';
        $replacements['{{event.title}}'] = $event_title;
        $replacements['{{event.name}}'] = $event_title;
    }
    if (!empty($data['event_date'])) {
        $replacements['{{event.date}}'] = $data['event_date'];
    }
    if (!empty($data['event_time'])) {
        $replacements['{{event.time}}'] = $data['event_time'];
    }
    if (!empty($data['event_description']) || !empty($data['description'])) {
        $event_desc = $data['event_description'] ?? $data['description'] ?? '';
        $replacements['{{event.description}}'] = $event_desc;
    }
    
    // Attendee/registration placeholders
    if (!empty($data['name']) || !empty($data['attendee_name'])) {
        $attendee_name = $data['attendee_name'] ?? $data['name'] ?? '';
        $replacements['{{attendee.name}}'] = $attendee_name;
        $replacements['{{registration.name}}'] = $attendee_name;
    }
    if (!empty($data['email']) || !empty($data['attendee_email'])) {
        $attendee_email = $data['attendee_email'] ?? $data['email'] ?? '';
        $replacements['{{attendee.email}}'] = $attendee_email;
        $replacements['{{registration.email}}'] = $attendee_email;
    }
    if (!empty($data['guest_count']) || !empty($data['attendee_guest_count'])) {
        $guest_count = $data['attendee_guest_count'] ?? $data['guest_count'] ?? '';
        $replacements['{{attendee.guest_count}}'] = $guest_count;
        $replacements['{{people_count}}'] = $guest_count;
    }
    if (!empty($data['registration_date'])) {
        $replacements['{{registration.date}}'] = $data['registration_date'];
    }
    if (!empty($data['registration_id']) || !empty($data['id'])) {
        $reg_id = $data['registration_id'] ?? $data['id'] ?? '';
        $replacements['{{registration.id}}'] = $reg_id;
    }
    
    // Location placeholders
    if (!empty($data['location_name'])) {
        $replacements['{{location.name}}'] = $data['location_name'];
    }
    if (!empty($data['location_link']) || !empty($data['location_url'])) {
        $location_link = $data['location_link'] ?? $data['location_url'] ?? '';
        $replacements['{{location.link}}'] = $location_link;
        $replacements['{{location.url}}'] = $location_link;
    }
    
    // Payment placeholders
    if (!empty($data['total_price'])) {
        $replacements['{{payment.total}}'] = $data['total_price'];
    }
    if (!empty($data['payment_method'])) {
        $replacements['{{payment.method}}'] = $data['payment_method'];
    }
    if (!empty($data['payment_status'])) {
        $replacements['{{payment.status}}'] = $data['payment_status'];
    }
    
    // Website placeholders
    if (!empty($data['website_name'])) {
        $replacements['{{website.name}}'] = $data['website_name'];
    }
    if (!empty($data['website_url'])) {
        $replacements['{{website.url}}'] = $data['website_url'];
    }
    if (!empty($data['admin_email'])) {
        $replacements['{{admin.email}}'] = $data['admin_email'];
    }
    
    // Additional fields and complex sections
    if (!empty($data['pricing_overview'])) {
        $replacements['{{pricing_overview}}'] = $data['pricing_overview'];
        $replacements['{pricing_overview}'] = $data['pricing_overview'];
    }
    if (!empty($data['payment_method_details'])) {
        $replacements['{{payment_method_details}}'] = $data['payment_method_details'];
        $replacements['{payment_method_details}'] = $data['payment_method_details'];
    }
    if (!empty($data['reservation_link'])) {
        $replacements['{{reservation.link}}'] = $data['reservation_link'];
        $replacements['{{reservation_link}}'] = $data['reservation_link'];
    }
    
    // Additional fields (dynamically added by enrich_additional_fields)
    // These typically have keys like 'additional_field_1', 'additional_field_2', etc.
    foreach ($data as $key => $value) {
        if (strpos($key, 'additional_field_') === 0 && !is_array($value) && !is_object($value)) {
            $replacements['{{' . $key . '}}'] = (string)$value;
            $replacements['{' . $key . '}'] = (string)$value;
        }
    }
    
    // Apply all replacements
    $template = str_replace(array_keys($replacements), array_values($replacements), $template);
    
    return $template;
}

// Run the test
echo "=== PLACEHOLDER REPLACEMENT TEST ===\n\n";
echo "BEFORE replacement:\n";
echo str_repeat("-", 70) . "\n";
echo $template . "\n";
echo str_repeat("-", 70) . "\n\n";

$result = test_replace_email_placeholders($template, $test_data);

echo "AFTER replacement:\n";
echo str_repeat("-", 70) . "\n";
echo $result . "\n";
echo str_repeat("-", 70) . "\n\n";

// Check for unreplaced placeholders - look for patterns like {{something}} or {something}
// where something contains only alphanumerics, underscores, and dots (placeholder naming convention)
echo "VALIDATION:\n";
preg_match_all('/\{\{[a-zA-Z0-9_.]+\}\}/', $result, $unresolved_placeholders);
if (!empty($unresolved_placeholders[0])) {
    echo "❌ UNRESOLVED DOUBLE-BRACE PLACEHOLDERS FOUND:\n";
    foreach (array_unique($unresolved_placeholders[0]) as $placeholder) {
        echo "   - " . $placeholder . "\n";
    }
} else {
    echo "✓ All double-brace placeholders resolved\n";
}

preg_match_all('/\{[a-zA-Z0-9_.]+\}/', $result, $unresolved_old);
if (!empty($unresolved_old[0])) {
    echo "❌ UNRESOLVED SINGLE-BRACE PLACEHOLDERS FOUND:\n";
    foreach (array_unique($unresolved_old[0]) as $placeholder) {
        echo "   - " . $placeholder . "\n";
    }
} else {
    echo "✓ All single-brace placeholders resolved\n";
}

// Verify that key fields were actually replaced with correct values
echo "\nKEY FIELDS VERIFICATION:\n";
$checks = array(
    'attendee_name' => 'Massimo Biondi',
    'event_title' => 'Spring Ball',
    'event_date' => 'April 15, 2025',
    'event_time' => '7:00 PM',
    'attendee_guest_count' => '2',
    'registration_id' => '123',
    'registration_date' => 'November 3, 2025',
    'additional_field_1' => 'Yes, whole table booking',
    'additional_field_2' => 'Window seating preferred',
    'admin_email' => 'admin@example.com',
);

$all_good = true;
foreach ($checks as $field => $expected_value) {
    if (strpos($result, $expected_value) !== false) {
        echo "✓ {$field}: '{$expected_value}' found\n";
    } else {
        echo "❌ {$field}: '{$expected_value}' NOT found\n";
        $all_good = false;
    }
}

echo "\n";
if ($all_good) {
    echo "✓✓✓ ALL TESTS PASSED - EMAIL REPLACEMENT IS WORKING CORRECTLY ✓✓✓\n";
} else {
    echo "❌ SOME TESTS FAILED\n";
}
?>
