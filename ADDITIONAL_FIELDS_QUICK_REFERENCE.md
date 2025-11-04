# Additional Information Fields System - Quick Reference

## 🎯 What Was Added

A complete system for managing custom fields collected during event registration and using them as dynamic placeholders in email templates.

## 📁 Files Created

### 1. **`/includes/class-additional-fields.php`** (New)
Core utility class with 13 methods:
- Get event fields and registration values
- Format fields for email display (HTML & plain text)
- Validate field submissions
- Export data for CSV
- Manage field metadata

### 2. **`/admin/views/partial-additional-fields-help.php`** (New)
Reusable help component showing:
- Placeholder syntax: `{{additional.field_id}}`
- Example usage in email templates
- How to find field IDs
- Field display options

## 📝 Files Modified

### 1. **`/admin/views/settings.php`**
- Added "Additional Information Fields" help section
- Shows in Global Settings for email template configuration

### 2. **`/admin/views/add-event.php`**
- Added "Additional Information Fields" help section
- Shows when editing event-specific email templates
- Helps admins use field placeholders

## 🚀 How It Works

### Step 1: Configure Fields (Existing)
1. Go to Add/Edit Event
2. Scroll to "Additional Information Fields" section
3. Add fields (Seating Preferences, Dietary Restrictions, etc.)

### Step 2: Create Email Template
1. Go to Settings → Email Templates (or Event-specific template)
2. Use placeholders like: `{{additional.seating_prefs}}`
3. Fields will be replaced with actual registration data

### Step 3: Data Storage
- Field definitions → `sct_events.comment_fields` (JSON)
- Field values → `sct_event_registrations.comments` (JSON)

## 📊 Key Methods

```php
// Get all fields for an event
$fields = SCT_Additional_Fields_Manager::get_event_fields($event_id);

// Get values submitted for a registration
$values = SCT_Additional_Fields_Manager::get_registration_field_values($registration_id);

// Format for HTML email
$html = SCT_Additional_Fields_Manager::format_for_email($event_id, $registration_id);

// Format for text email
$text = SCT_Additional_Fields_Manager::format_for_email_text($event_id, $registration_id);

// Validate submissions
$errors = SCT_Additional_Fields_Manager::validate_fields($event_id, $submitted_values);

// Export to CSV
$csv_data = SCT_Additional_Fields_Manager::export_for_csv($event_id, $registration_id);
```

## 💡 Usage Examples

### Email Template Example
```
Dear {{attendee.name}},

Thank you for registering for {{event.title}}.

Your seating preference: {{additional.seating_prefs}}
Dietary restrictions: {{additional.dietary_restrictions}}
Will attend after-party: {{additional.attend_party}}

Best regards,
{{website.name}}
```

### In PHP Code
```php
// Format all additional fields for email
$additional_fields_html = SCT_Additional_Fields_Manager::format_for_email(123, 456);

// Get specific field value
$fields = SCT_Additional_Fields_Manager::get_event_fields(123);
$values = SCT_Additional_Fields_Manager::get_registration_field_values(456);
$seating = $values['seating_prefs'] ?? 'Not specified';
```

## 🔄 Integration Points

### For Email Sending
The system integrates with existing email placeholder replacement:
- When `replace_email_placeholders()` is called
- Additional fields are automatically available as `{{additional.field_id}}`

### For Admin Display
Show registration details with all custom fields:
```php
echo SCT_Additional_Fields_Manager::format_for_email($event_id, $registration_id);
```

### For CSV Export
Include custom fields in exported data:
```php
$additional = SCT_Additional_Fields_Manager::export_for_csv($event_id, $registration_id);
```

## ✨ Features

✅ Automatic placeholder generation from field IDs  
✅ Multiple field types: textarea, text, checkbox, dropdown  
✅ Required field validation  
✅ HTML and plain text formatting  
✅ CSV export support  
✅ Backward compatible with existing email templates  
✅ Admin help and documentation  

## 📚 Documentation

Full documentation available in: `/ADDITIONAL_FIELDS_SYSTEM.md`

Covers:
- Complete component overview
- Data structure details
- All available methods
- Integration examples
- Database schema
- Troubleshooting guide
- Future enhancement ideas

## 🔧 How to Use

### For Admin Users
1. Configure additional fields in event settings
2. Use `{{additional.field_id}}` in email templates
3. Fields automatically display when emails are sent

### For Developers
1. Import the class: `require_once 'class-additional-fields.php'`
2. Use methods to retrieve, validate, and format field data
3. Integrate into custom email workflows

## 🎨 Field Types Supported

| Type | Display | Example |
|------|---------|---------|
| textarea | With line breaks | Multi-line text |
| text | Plain text | Single line |
| checkbox | "Yes"/"No" | Boolean |
| select | Selected value | Dropdown option |

## 🚦 Status

✅ **Complete and Ready to Use**

- Class implemented with 13 utility methods
- Admin views updated with help sections
- Full documentation provided
- Examples and usage patterns documented
- Integration points identified

