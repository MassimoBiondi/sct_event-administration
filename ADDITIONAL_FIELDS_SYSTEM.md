# Additional Information Fields System

## Overview

The new Additional Information Fields system provides a structured way to manage custom fields collected during event registration and make them available as dynamic placeholders in email templates.

## Components

### 1. **Class: `SCT_Additional_Fields_Manager`**
Located in: `/includes/class-additional-fields.php`

Main utility class that handles all operations related to additional information fields.

#### Key Methods

```php
// Get all fields for an event
SCT_Additional_Fields_Manager::get_event_fields($event_id)

// Get field values for a registration
SCT_Additional_Fields_Manager::get_registration_field_values($registration_id)

// Format fields for HTML email
SCT_Additional_Fields_Manager::format_for_email($event_id, $registration_id)

// Format fields for plain text email
SCT_Additional_Fields_Manager::format_for_email_text($event_id, $registration_id)

// Validate field values
SCT_Additional_Fields_Manager::validate_fields($event_id, $field_values)

// Export field data for CSV
SCT_Additional_Fields_Manager::export_for_csv($event_id, $registration_id)
```

### 2. **Data Structure**

#### Event Configuration
Fields are stored in the `sct_events` table, column `comment_fields` as JSON:

```json
[
  {
    "id": "seating_prefs",
    "label": "Seating Preferences",
    "type": "textarea",
    "required": false,
    "placeholder": "Tell us about your seating preferences..."
  },
  {
    "id": "dietary_restrictions",
    "label": "Dietary Restrictions",
    "type": "text",
    "required": false
  },
  {
    "id": "attend_party",
    "label": "Will you attend the after-party?",
    "type": "checkbox",
    "required": false
  }
]
```

#### Registration Submission
Values are stored in `sct_event_registrations` table, column `comments` as JSON:

```json
{
  "seating_prefs": "Window seat preferred",
  "dietary_restrictions": "Vegetarian",
  "attend_party": true
}
```

### 3. **Email Template Placeholders**

#### Format
```
{{additional.FIELD_ID}}
```

#### Examples
```html
Dear {{attendee.name}},

Thank you for registering for {{event.title}}.

Your seating preference: {{additional.seating_prefs}}
Dietary restrictions: {{additional.dietary_restrictions}}
After-party: {{additional.attend_party}}

Best regards,
{{website.name}}
```

### 4. **Integration Points**

#### A. In Email Sending (class-event-public.php)
When sending confirmation emails:

```php
$registration_data = array(
    'event_id' => $event->id,
    'registration_id' => $registration->id,
    'attendee_name' => $registration->name,
    // ... other data
    'comments' => json_decode($registration->comments, true)
);

$email_body = SCT_Event_Email_Utilities::replace_email_placeholders(
    $template,
    $registration_data
);
```

#### B. In Admin Registration Display
When showing registration details:

```php
$additional_html = SCT_Additional_Fields_Manager::format_for_email(
    $event_id,
    $registration_id
);
```

#### C. In CSV Export
When exporting registrations:

```php
$fields_data = SCT_Additional_Fields_Manager::export_for_csv(
    $event_id,
    $registration_id
);
```

### 5. **Admin Interface**

#### Event Editor
- Navigate to Add/Edit Event page
- Scroll to "Additional Information Fields" section
- Add/edit fields with label, type, and required flag
- Fields are automatically saved to `comment_fields` column

#### Settings Page
- Shows all available placeholders for email templates
- Includes help section explaining how to use additional field placeholders
- Displays examples of field configuration

### 6. **Available Field Types**

| Type | Display in Email | Example |
|------|------------------|---------|
| `textarea` | With line breaks preserved | Multi-line text responses |
| `text` | Plain text | Single-line responses |
| `checkbox` | "Yes" or "No" | Boolean selections |
| `select` | Selected option | Dropdown selections |

## Usage Examples

### Example 1: Display All Additional Fields in Email

```php
$event_id = 123;
$registration_id = 456;

$html_email = SCT_Additional_Fields_Manager::format_for_email($event_id, $registration_id);
// Returns: Formatted HTML with all field data

$text_email = SCT_Additional_Fields_Manager::format_for_email_text($event_id, $registration_id);
// Returns: Plain text formatted for text emails
```

### Example 2: Get Specific Field Value

```php
$fields = SCT_Additional_Fields_Manager::get_event_fields($event_id);
// Returns array of field definitions

$values = SCT_Additional_Fields_Manager::get_registration_field_values($registration_id);
// Returns: ['seating_prefs' => 'Window seat', 'dietary_restrictions' => 'Vegetarian']

$seating = $values['seating_prefs'] ?? 'Not provided';
```

### Example 3: Validate Additional Fields on Registration

```php
$submitted_values = $_POST['comments'] ?? array();
$errors = SCT_Additional_Fields_Manager::validate_fields($event_id, $submitted_values);

if (!empty($errors)) {
    // Show validation errors to user
    foreach ($errors as $field_id => $error) {
        echo "Error in " . $field_id . ": " . $error;
    }
}
```

### Example 4: In Email Template

```
Dear {{attendee.name}},

Thank you for registering for {{event.title}}.

Event Details:
- Date: {{event.date}}
- Time: {{event.time}}
- Location: {{event.location_name}}

Your Information:
- Guest Count: {{attendee.guest_count}}
- Total Cost: {{payment.total}}

Additional Details:
{{additional.seating_prefs}}
{{additional.dietary_restrictions}}

Manage your registration: {{registration.manage_link}}

Best regards,
{{website.name}}
```

## Admin Views Updated

### 1. `/admin/views/settings.php`
- Added "Additional Information Fields" help section
- Shows how to use additional field placeholders
- Displays placeholder format and examples

### 2. `/admin/views/add-event.php`
- Added "Additional Information Fields" help section
- Shows when editing event-specific email templates
- Helps admins remember the placeholder syntax

### 3. `/admin/views/partial-additional-fields-help.php`
- Reusable component showing field placeholder information
- Included in both settings and event editor pages

## Database Schema

### sct_events table
```sql
comment_fields LONGTEXT NULL -- JSON array of field definitions
```

### sct_event_registrations table
```sql
comments LONGTEXT NULL -- JSON object of field values submitted by registrant
```

## Backward Compatibility

- Old placeholder formats (e.g., `{name}`, `{email}`) continue to work
- Additional fields use new format: `{{additional.field_id}}`
- Both formats can be mixed in email templates

## Future Enhancement Opportunities

1. **Field Conditional Display**: Show/hide fields based on other field values
2. **Field Dependencies**: Chain fields together (e.g., "number of children" field)
3. **Custom Field Types**: Add file upload, date picker, rating scales
4. **Field Templates**: Pre-configured field sets for common scenarios
5. **Reporting**: Generate reports on field responses
6. **Field Validation Rules**: Add custom validation patterns
7. **Multi-language Support**: Translate field labels and placeholders

## Troubleshooting

### Placeholders Not Rendering
- Ensure field ID matches exactly (case-sensitive)
- Verify field data was saved with registration
- Check that `comments` column in database contains valid JSON

### Missing Fields
- Verify fields were added to event configuration
- Check `comment_fields` JSON in `sct_events` table
- Ensure form included the field for registration

### Export Issues
- Verify registration has `comments` data
- Check that field IDs haven't changed
- Ensure database query has proper permissions

