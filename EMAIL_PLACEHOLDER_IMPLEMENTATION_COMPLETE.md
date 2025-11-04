# Email Placeholder System - Implementation Summary

## What Was Fixed

### 1. **Database Field Name Mapping Issue**
**Problem**: Database uses `event_name` but placeholders expected `event.title` or `event_title`

**Solution**: Added automatic field name normalization in `send_registration_emails()`:
```
event_name       → event_title, event.title
name             → attendee_name, registration_name
email            → attendee_email, registration_email
location_name    → location.name
```

### 2. **Additional Fields - Cryptic IDs Issue**
**Problem**: Had to use `{{additional.field_1762071449893_z4dllajxd}}` - unreadable!

**Solution**: New `enrich_additional_fields()` method generates simple placeholder names:
- `{{additional_field_1}}` - First additional field
- `{{additional_field_2}}` - Second additional field
- `{{whole_table_booking}}` - Field labeled "Whole Table Booking"
- `{{seating_preferences}}` - Field labeled "Seating Preferences"

### 3. **Missing Website/Date Variables**
**Problem**: `{{website.name}}`, `{{website.url}}`, `{{date.year}}` weren't available

**Solution**: Added automatic mapping:
```
website_name     → Gets from WordPress blog name
website_url      → Gets from WordPress site URL
current_year     → Gets from current date
```

## Implementation Details

### File: `/includes/class-event-public.php`

#### Change 1: Data Enrichment in `send_registration_emails()`
Added field name mapping after line 530:
```php
// Map database field names to placeholder-compatible names
$placeholder_data['event_title'] = $placeholder_data['event_name'] ?? '';
$placeholder_data['attendee_name'] = $placeholder_data['name'] ?? '';
// ... more mappings ...

// Add additional fields dynamically if they exist
$placeholder_data = $this->enrich_additional_fields($placeholder_data, $registration_data, $event_data);
```

#### Change 2: New Method `enrich_additional_fields()`
Added new method to automatically discover and add additional fields:
- Queries `sct_additional_fields` table for event fields
- Queries `sct_additional_field_values` table for registration values
- Generates both numbered (`additional_field_N`) and labeled placeholders
- Handles snake_case conversion of field labels

### Templates: `/email-templates/spring-ball-template.*`

#### Updated Placeholder Names (Old → New):

| Old | New | Description |
|-----|-----|-------------|
| `{{attendee.name}}` | `{{attendee_name}}` | Attendee name |
| `{{event.name}}` | `{{event_title}}` | Event title |
| `{{event.date}}` | `{{event_date}}` | Event date |
| `{{event.time}}` | `{{event_time}}` | Event time |
| `{{event.location_name}}` | `{{location_name}}` | Location |
| `{{attendee.guest_count}}` | `{{attendee_guest_count}}` | Guest count |
| `{{registration.id}}` | `{{registration_id}}` | Confirmation number |
| `{{registration.date}}` | `{{registration_date}}` | Registration date |
| `{{registration.manage_link}}` | `{{registration_manage_link}}` | Manage link |
| `{{website.name}}` | `{{website_name}}` | Site name |
| `{{website.url}}` | `{{website_url}}` | Site URL |
| `{{date.year}}` | `{{current_year}}` | Year |
| `{{additional.field_CRYPTIC_ID}}` | `{{additional_field_1}}` | 1st additional field |
| `{{additional.field_CRYPTIC_ID}}` | `{{additional_field_2}}` | 2nd additional field |

## Available Placeholders After Implementation

### Standard Fields (Always Available)
```
{{attendee_name}}           - Registrant's name
{{attendee_email}}          - Registrant's email
{{attendee_guest_count}}    - Number of guests
{{event_title}}             - Event name
{{event_date}}              - Event date
{{event_time}}              - Event time
{{location_name}}           - Event location
{{registration_id}}         - Confirmation number
{{registration_date}}       - Registration date
{{registration_manage_link}} - Link to manage registration
{{website_name}}            - Site name
{{website_url}}             - Site URL
{{current_year}}            - Current year
```

### Additional Fields (Dynamic per Event)
```
{{additional_field_1}}      - First configured additional field
{{additional_field_2}}      - Second configured additional field
{{whole_table_booking}}     - If field labeled "Whole Table Booking"
{{seating_preferences}}     - If field labeled "Seating Preferences"
[etc. - auto-generated from field labels]
```

### System-Generated Sections
```
{pricing_overview}          - Pricing breakdown table
{payment_method_details}    - Payment instructions
```

## Backward Compatibility

The new system maintains compatibility with:
- **Old placeholder format**: `{placeholder}` ✓
- **New placeholder format**: `{{placeholder}}` ✓
- **Both formats work in templates** ✓
- **Old templates still work** ✓

## How Additional Fields Work

### Database Setup
When you create an additional field for an event:
1. **Field Definition** saved to `sct_additional_fields` table
   - `field_id`, `event_id`, `field_label`, `field_order`

2. **Field Values** saved to `sct_additional_field_values` table
   - `field_id`, `registration_id`, `field_value`

### Template Generation
When an email is sent:
1. `send_registration_emails()` calls `enrich_additional_fields()`
2. System queries all fields for the event
3. System queries all field values for the registration
4. For each field, creates:
   - `additional_field_1`, `additional_field_2`, etc.
   - `whole_table_booking`, `seating_preferences`, etc. (from labels)

### Example
If you have fields:
- Field 1: "Whole Table Booking" 
- Field 2: "Seating Preferences"

Template can use:
```
{{additional_field_1}}    → Value from "Whole Table Booking"
{{additional_field_2}}    → Value from "Seating Preferences"
{{whole_table_booking}}   → Value from "Whole Table Booking"
{{seating_preferences}}   → Value from "Seating Preferences"
```

## Testing Checklist

✅ Create test registration for Spring Ball
✅ Verify `{{event_title}}` displays "Spring Ball"
✅ Verify `{{attendee_name}}` displays registrant name
✅ Verify `{{additional_field_1}}` displays whole table booking value
✅ Verify `{{additional_field_2}}` displays seating preferences value
✅ Verify pricing table displays if configured
✅ Verify payment info displays if payment method set
✅ Verify website name and year display correctly
✅ Check for any `{{placeholder}}` text (means placeholder not recognized)

## Performance Notes

- Additional fields query happens once per email send
- Uses prepared statements to prevent SQL injection
- Gracefully handles missing tables (for events without additional fields)
- Minimal performance impact

## Future Enhancements

Possible improvements:
1. **Template Builder UI** - Drag-and-drop template builder
2. **Preview Feature** - See template with actual data before sending
3. **Template Library** - Save and reuse templates across events
4. **Conditional Fields** - Show/hide sections based on data
5. **Custom Filters** - Format data (dates, numbers, etc.)

## Support

For questions or issues:
1. Check that `additional_field_1`, `additional_field_2` are correct field names
2. Verify additional fields are configured in event settings
3. Check WordPress debug.log for errors
4. Verify placeholder data is being populated correctly

---

**Status**: ✅ Implementation Complete
**Date**: November 2025
**Tested**: Spring Ball email template
