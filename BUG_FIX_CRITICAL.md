# CRITICAL BUG FIX - Data Loss Prevention

## Problem
When editing events and saving the form, **pricing_options and goods_services data were being deleted** (set to NULL) even though they weren't being modified.

## Root Cause
Two related bugs in `class-event-admin.php` `save_event()` function:

### Bug 1: Static Format Array Mismatch
- The `$data_format` array was hardcoded with a fixed order of fields
- The `$event_data` array was built dynamically with conditional fields
- When `$event_data` included/excluded certain keys (like pricing_options, goods_services), the format array positions no longer matched
- This caused field values to be shifted to wrong columns in the database

### Bug 2: Incorrect UPDATE vs INSERT Detection
- The code checked `if (!isset($_POST['event_id']))` to distinguish INSERT from UPDATE
- But this logic was backwards - it would set pricing_options to NULL on UPDATE if they weren't in POST
- When a form was saved without modifying the pricing section, the pricing_options field wasn't included in POST
- Result: existing pricing data was overwritten with NULL

## Solution

### Part 1: Dynamic Format Array (CRITICAL)
Changed from a hardcoded, static format array to a **dynamic format array** that exactly matches the keys in `$event_data`:

```php
// Build format array dynamically based on actual $event_data keys
$field_formats = array(
    'event_name' => '%s',
    'event_date' => '%s',
    // ... all field formats ...
);

// Build format array with ONLY the keys that are actually in $event_data
foreach ($event_data as $key => $value) {
    if (isset($field_formats[$key])) {
        $data_format[] = $field_formats[$key];
    }
}
```

This ensures the format array **always** matches the $event_data key order, regardless of which fields are included.

### Part 2: Proper INSERT vs UPDATE Logic
Fixed the conditional logic for pricing_options and goods_services:

```php
// Handle pricing_options - CRITICAL: Only set on INSERT or if explicitly provided
if (isset($_POST['pricing_options']) && is_array($_POST['pricing_options'])) {
    // Process and save pricing_options
} elseif (!isset($_POST['event_id'])) {
    // New event (INSERT) - set to null if not provided
    $event_data['pricing_options'] = null;
}
// For UPDATE: if not in POST, don't include it in $event_data to preserve existing value
```

**Key difference:** Don't add the field to `$event_data` at all if it's an UPDATE and not provided in POST. This preserves the existing database value.

## Impact
- ✅ Pricing options no longer disappear when editing an event
- ✅ Goods/services no longer disappear when editing an event  
- ✅ All fields maintain their values across edits
- ✅ Format array always matches data array order
- ✅ Database integrity maintained

## Testing
To verify the fix works:
1. Edit Event #14 (Outdoor Christmas) without modifying pricing options
2. Add pricing options and save
3. Edit the event again without modifying pricing options
4. Verify pricing options are still there
5. Do the same test for goods/services and payment methods

## Files Modified
- `/includes/class-event-admin.php` - save_event() function (lines 323-450)

## Version
Updated to 2.10.7 to trigger database migration on next activation
