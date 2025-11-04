# Dynamic Optional Comment Fields Implementation

## Overview

Added a flexible system for collecting optional custom information from event guests. Each event can define its own set of comment fields that guests can fill out during registration. Fields are stored as JSON configurations and guest responses are serialized in the database.

## Features

✅ **Per-Event Configuration** - Each event can have different optional fields  
✅ **Multiple Field Types** - Textarea, Text Input, Checkbox, Dropdown  
✅ **Required/Optional Toggle** - Make specific fields mandatory  
✅ **Field Settings** - Customizable labels, placeholders, help text, and rows  
✅ **Admin UI** - Easy field builder interface with add/remove functionality  
✅ **Guest Display** - Beautiful responsive form in registration  
✅ **Admin Viewing** - Comments displayed in registration detail modal  
✅ **CSV Export** - Comment data included in registration exports  

## Database Schema

### New Columns Added

#### `sct_events` table
```sql
comment_fields LONGTEXT NULL
```
- Stores JSON array of field definitions
- Default: NULL (no additional fields)
- Format: Serialized JSON array

#### `sct_event_registrations` table
```sql
comments LONGTEXT NULL
```
- Stores guest's responses to comment fields
- Default: NULL (no comments provided)
- Format: Serialized array of field_id => value pairs

## Field Configuration Format

### JSON Structure in `sct_events.comment_fields`

```json
[
  {
    "id": "seating_preferences",
    "type": "textarea",
    "label": "Names of people you'd like to sit with",
    "placeholder": "Enter names separated by commas",
    "required": false,
    "rows": 3
  },
  {
    "id": "table_reservation",
    "type": "checkbox",
    "label": "I would like to reserve a whole table",
    "required": false,
    "placeholder": "Table reservation requires minimum 8 guests"
  },
  {
    "id": "special_requests",
    "type": "textarea",
    "label": "Special requests or notes",
    "placeholder": "Any special needs we should know about?",
    "required": false,
    "rows": 2
  }
]
```

### Field Properties

| Property | Type | Description | Required |
|----------|------|-------------|----------|
| `id` | string | Unique identifier for field (auto-generated) | Yes |
| `label` | string | Display label shown to guest | Yes |
| `type` | string | Field type: `textarea`, `text`, `checkbox`, `select` | Yes |
| `required` | boolean | Mark field as required | No (default: false) |
| `placeholder` | string | Placeholder text or help text | No |
| `rows` | number | Number of rows for textarea (1-10) | No (default: 3) |
| `options` | array | Options for select field | Only for select type |

## Guest Data Storage

### JSON Structure in `sct_event_registrations.comments`

```php
// Serialized array structure:
{
  "seating_preferences": "John Smith, Mary Johnson",
  "table_reservation": "1",
  "special_requests": "We need vegetarian options"
}
```

Retrieved with:
```php
$comments = maybe_unserialize($registration->comments);
$seating = $comments['seating_preferences'] ?? '';
```

## Admin Interface

### Event Editor UI

1. Go to **Events → Edit Event**
2. Scroll to **"Optional Comment Fields"** section
3. Click **"+ Add Comment Field"** button
4. Configure field:
   - Enter field label
   - Select field type
   - Set required/optional
   - Add placeholder/help text
   - For textarea: specify number of rows
5. Click **Save Event** to store configuration

### Field Management

- **Add Field**: Click "+ Add Comment Field" button
- **Edit Field**: Modify any field's properties directly
- **Remove Field**: Click "Remove" button on the field
- **Reorder Fields**: Fields appear in order added (can drag to reorder in future)

## Frontend Registration Form

### Display

Fields appear in a dedicated **"Additional Information"** section on the registration form:

```
[Additional Information]
┌─────────────────────────────────────────┐
│ Names of people you'd like to sit with  │
│ ┌─────────────────────────────────────┐ │
│ │ John Smith, Mary Johnson            │ │
│ └─────────────────────────────────────┘ │
├─────────────────────────────────────────┤
│ ☑ I would like to reserve a whole table│
│   (Table reservation requires minimum 8)│
├─────────────────────────────────────────┤
│ Special requests or notes               │
│ ┌─────────────────────────────────────┐ │
│ │ Vegetarian meals needed             │ │
│ └─────────────────────────────────────┘ │
└─────────────────────────────────────────┘
```

### Styling

- Uses UIKit CSS framework
- Responsive grid layout
- Consistent with existing form styling
- Red asterisk (*) for required fields

## Admin Registration Details

Comments appear in the registration details modal when viewing individual registrations:

**Registration Details Modal**
```
Name: John Doe
Email: john@example.com
Guest Count: 3
...
──────────────────────────
Additional Information

Names of people you'd like to sit with:
Jane Smith, Mike Johnson

I would like to reserve a whole table:
Yes

Special requests or notes:
Vegetarian and nut-free meals needed
```

## Use Cases

### Event A: Corporate Gala
**Configured Fields:**
- Dietary restrictions (textarea)
- Reserved table (checkbox)
- Special seating preferences (textarea)

### Event B: Community Meetup
**Configured Fields:**
- How did you hear about us? (select)
- Additional comments (textarea)

### Event C: Internal Meeting
**Configured Fields:**
- (None - only basic info collected)

## Code Examples

### Render Comment Fields in Frontend

```php
<?php if (!empty($event->comment_fields)):
    $comment_fields = json_decode($event->comment_fields, true);
    if (!empty($comment_fields)):
        foreach ($comment_fields as $field):
            if ($field['type'] === 'textarea'): ?>
                <textarea name="comments[<?php echo $field['id']; ?>]" 
                          rows="<?php echo $field['rows'] ?? 3; ?>"
                          <?php echo $field['required'] ? 'required' : ''; ?>>
                </textarea>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>
<?php endif; ?>
```

### Retrieve Comments in Admin/Code

```php
// Get registration with comments
$registration = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}sct_event_registrations WHERE id = %d",
    $reg_id
));

// Unserialize comments
$comments = maybe_unserialize($registration->comments);

// Access specific field
$seating = $comments['seating_preferences'] ?? '';
```

### Access Comment Field Definitions

```php
// Get event with comment field definitions
$event = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}sct_events WHERE id = %d",
    $event_id
));

// Decode field definitions
$fields = json_decode($event->comment_fields, true);

// Find specific field definition
foreach ($fields as $field) {
    if ($field['id'] === 'seating_preferences') {
        echo $field['label']; // "Names of people you'd like to sit with"
        echo $field['type'];  // "textarea"
    }
}
```

## CSV Export

When exporting registrations, comment fields appear as separate columns:

```csv
Name,Email,Phone,Company,Address,Seating Preferences,Table Reservation,Special Requests
John Doe,john@example.com,(555) 123-4567,Acme Corp,"123 Main St",Jane Smith / Mike Johnson,Yes,Vegetarian menu
```

Empty comments display as blank cells.

## Data Flow

1. **Admin configures fields** in event editor
2. **Fields saved** as JSON to `sct_events.comment_fields`
3. **Frontend form renders** fields dynamically from JSON
4. **Guest fills form** and submits registration
5. **Comments collected** as POST array `$_POST['comments']`
6. **Comments serialized** and saved to `sct_event_registrations.comments`
7. **Admin views** comments in registration details modal
8. **CSV export includes** comment data as columns

## File Modifications

**Updated Files:**
- `sct_event-administraion.php` - Added `comment_fields` column to `sct_events` table and `comments` column to `sct_event_registrations`
- `admin/views/add-event.php` - Added comment field builder UI with JavaScript
- `admin/views/registrations.php` - Added comments section to registration form
- `public/views/event-registration.php` - Dynamically render comment fields on frontend
- `includes/class-event-admin.php` - Added AJAX handler and registration details display
- `includes/class-event-public.php` - Handle comment data in registration submission

## Backward Compatibility

✅ **Fully backward compatible:**
- New columns default to NULL
- Existing events have no comment fields (works fine)
- Existing registrations work without comments
- No breaking changes to existing functionality

## Future Enhancements

- [ ] Drag-to-reorder fields in admin UI
- [ ] Field type: Date picker
- [ ] Field type: Radio buttons
- [ ] Conditional field display (show field if another has specific value)
- [ ] Field validation rules (email, phone format, etc.)
- [ ] Multi-language support for field labels
- [ ] Import/export field configurations between events
- [ ] Field response analytics
- [ ] Webhook integration for comment submissions

## Testing Checklist

- [ ] Create event without comment fields - form works normally
- [ ] Create event with multiple comment field types
- [ ] Verify field labels, placeholders display correctly
- [ ] Submit registration with all fields filled
- [ ] Submit registration with optional fields empty
- [ ] View registration details - comments display correctly
- [ ] Export CSV - comment columns present
- [ ] Edit event - existing fields load correctly
- [ ] Remove field from event - submitted data still shows in old registrations
- [ ] Test required field validation (client-side, server-side)
- [ ] Test on mobile - form responsive
- [ ] Test with special characters in field labels and data
