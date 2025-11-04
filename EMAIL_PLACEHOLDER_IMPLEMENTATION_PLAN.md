# Email Placeholder System - Implementation Plan

## Current Issues

### 1. **Field Name Mismatch**
Database uses `event_name`, `event_date`, `event_time`, `location_name` 
But placeholders expect `event_title`, `event_date`, `event_time`, `location.name`

### 2. **Additional Fields System**
- Currently cryptic IDs: `{{additional.field_1762071449893_z4dllajxd}}`
- Need: Simple names like `{{additional_field_1}}` or `{{whole_table_booking}}`
- Need: Dynamic generation based on event configuration

### 3. **Missing Placeholder Data Mapping**
- Event fields not properly mapped to placeholder data
- Additional fields not queried or added to template data
- No normalization of field names

## Proposed Solution

### Phase 1: Fix Database Field Name Mapping
**File**: `/includes/class-event-public.php`

In `send_registration_emails()` method, add data enrichment:

```php
// Map database field names to placeholder-compatible names
$placeholder_data = array_merge($registration_data, $event_data);

// Add normalized event field names
$placeholder_data['event_name'] = $event_data['event_name'] ?? '';
$placeholder_data['event_title'] = $event_data['event_name'] ?? '';
$placeholder_data['location_name'] = $event_data['location_name'] ?? '';
$placeholder_data['location.name'] = $event_data['location_name'] ?? '';
$placeholder_data['event.name'] = $event_data['event_name'] ?? '';

// Add website info
$placeholder_data['website_name'] = get_bloginfo('name');
$placeholder_data['website_url'] = get_bloginfo('url');
$placeholder_data['current_date'] = current_time('F j, Y');
$placeholder_data['current_year'] = date('Y');
```

### Phase 2: Dynamic Additional Fields System
**File**: `/includes/class-event-public.php`

New method to fetch and map additional fields:

```php
private function add_additional_fields_to_template($placeholder_data, $event_id) {
    global $wpdb;
    
    // Query additional fields from database
    $fields = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sct_additional_fields WHERE event_id = %d",
            $event_id
        )
    );
    
    if (empty($fields)) {
        return $placeholder_data;
    }
    
    // Create simple placeholder names for each field
    $field_counter = 1;
    foreach ($fields as $field) {
        $field_id = $field->field_id;
        $field_label = sanitize_key($field->field_label);
        
        // Add both: numbered ({{additional_field_1}}) and named ({{whole_table_booking}})
        $placeholder_data['additional_field_' . $field_counter] = 
            $placeholder_data['additional_field_' . $field_id] ?? '';
        
        $placeholder_data[$field_label] = 
            $placeholder_data['additional_field_' . $field_id] ?? '';
        
        $field_counter++;
    }
    
    return $placeholder_data;
}
```

### Phase 3: Query Registration Additional Fields
**File**: `/includes/class-event-public.php`

When fetching registration data, also get additional fields:

```php
// In save_registration() or send_registration_emails()

// Query additional fields for this registration
$additional_field_values = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT af.field_id, af.field_label, afv.field_value
         FROM {$wpdb->prefix}sct_additional_fields af
         LEFT JOIN {$wpdb->prefix}sct_additional_field_values afv 
         ON af.field_id = afv.field_id AND afv.registration_id = %d
         WHERE af.event_id = %d",
        $registration_id,
        $event_id
    )
);

// Add to placeholder data with clean names
$field_counter = 1;
foreach ($additional_field_values as $field) {
    $placeholder_data['additional_field_' . $field_counter] = $field->field_value ?? '';
    
    // Also add by label (snake_case)
    $label_key = sanitize_key(strtolower($field->field_label));
    $placeholder_data[$label_key] = $field->field_value ?? '';
    
    $field_counter++;
}
```

### Phase 4: Template Simplification
**File**: `/email-templates/spring-ball-template.txt`

Instead of:
```
Whole Table Booking:   {{additional.field_1762071449893_z4dllajxd}}
```

Use either:
```
Whole Table Booking:   {{additional_field_1}}
```

Or:
```
Whole Table Booking:   {{whole_table_booking}}
```

## Implementation Steps

### Step 1: Update send_registration_emails() - Data Enrichment
Add field name mapping and additional fields querying

### Step 2: Create new table for additional fields metadata (if not exists)
- `sct_additional_fields` - stores field definitions per event
- `sct_additional_field_values` - stores field values per registration

### Step 3: Update template files with simple placeholder names

### Step 4: Create admin UI to configure additional field placeholders

### Step 5: Update documentation

## Files to Modify

1. **`/includes/class-event-public.php`** (MAIN)
   - Update `send_registration_emails()` method
   - Add `add_additional_fields_to_template()` method
   - Add `map_event_data_for_placeholders()` method

2. **`/email-templates/spring-ball-template.txt`** (UPDATE)
   - Replace cryptic additional field IDs with simple numbered fields
   - Fix event field name mismatches

3. **`/includes/class-additional-fields.php`** (REVIEW)
   - Verify table structure
   - Ensure field_id and field_label are accessible

## Example: Template Before & After

### BEFORE (Broken)
```
Event:                 {{event.name}}
Date:                  {{event.date}}
Whole Table Booking:   {{additional.field_1762071449893_z4dllajxd}}
```

Output email (broken):
```
Event:                 {{event.name}}              ← NOT replaced
Date:                  2026-03-05                 ← replaced
Whole Table Booking:   {{additional.field_176...}} ← NOT replaced
```

### AFTER (Working)
```
Event:                 {{event_title}}
Date:                  {{event_date}}
Whole Table Booking:   {{additional_field_1}}
```

Output email (working):
```
Event:                 Spring Ball                ← Replaced ✓
Date:                  March 5, 2026              ← Replaced ✓
Whole Table Booking:   Yes, 8 people             ← Replaced ✓
```

## Additional Placeholders to Support

After implementation, templates can use:

**Simple Numbered** (for all events):
- `{{additional_field_1}}` - First additional field
- `{{additional_field_2}}` - Second additional field
- `{{additional_field_3}}` - etc.

**Named** (specific to field labels):
- `{{whole_table_booking}}` - If field label is "Whole Table Booking"
- `{{seating_preferences}}` - If field label is "Seating Preferences"

**Standard Fields** (always available):
- `{{event_title}}` - Event name
- `{{event_date}}` - Event date
- `{{event_time}}` - Event time
- `{{location_name}}` - Location name
- `{{attendee_name}}` - Registrant name
- `{{attendee_email}}` - Registrant email
- `{{attendee_guest_count}}` - Number of guests
- `{{registration_id}}` - Confirmation number
- `{{registration_date}}` - Registration date
- `{{pricing_overview}}` - Pricing table
- `{{payment_method_details}}` - Payment info

## Backward Compatibility

The new system maintains compatibility with:
- Old placeholder format: `{placeholder}`
- New placeholder format: `{{placeholder}}`
- Event-specific placeholders: `{{event.title}}`
- Both snake_case and dot notation

## Benefits

✅ Simple, readable placeholder names in templates
✅ No need to know cryptic field IDs
✅ Support for multiple additional fields per event
✅ Dynamic field discovery - templates work automatically
✅ Admin-friendly configuration
✅ Backward compatible with existing templates

## Timeline

- Phase 1 (Field Mapping): 1-2 hours
- Phase 2 (Additional Fields): 2-3 hours
- Phase 3 (Query & Integration): 1-2 hours
- Phase 4 (Templates): 30 minutes
- Phase 5 (Testing): 1 hour
- Total: 5-8 hours
