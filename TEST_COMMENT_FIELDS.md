# Testing Dynamic Comment Fields

## Quick Start Test

### 1. Create New Event
1. Go to **Events → Add New** in WordPress admin
2. Fill in basic event details:
   - Event Name: "Test Event"
   - Event Date: Any future date
   - Location: "Test Location"
   - Capacity: 50
3. Scroll down to **"Optional Comment Fields"** section

### 2. Add Comment Fields
1. Click **"+ Add Comment Field"** button
   - You should see a new field appear with input boxes
2. Fill in the field:
   - **Label**: "Seating Preferences"
   - **Type**: "Textarea" (already selected)
   - **Required**: Leave unchecked
   - **Placeholder**: "Names of people you'd like to sit with"
   - **Rows**: 3
3. Click **"Save Event"** button

### 3. Verify in Admin
1. Go to **Events → All Events**
2. Edit the event you just created
3. Scroll to **"Optional Comment Fields"**
   - Your field should load and display with all your settings

### 4. Test on Frontend
1. Go to the Events page on your website (public side)
2. Find the test event
3. Click "Register"
4. Scroll down to **"Additional Information"** section
5. You should see:
   - **"Seating Preferences"** textarea with placeholder text
6. Fill it with test text and submit

### 5. Verify in Admin Registrations
1. Go to **Events → Registrations**
2. Find your test event
3. Click **"View Details"** button on your registration
4. In the modal, scroll down to **"Additional Information"**
5. You should see your seating preferences text

## Troubleshooting

### Button Doesn't Respond
**Check:**
1. Open browser **Developer Tools** (F12)
2. Go to **Console** tab
3. You should see logs like:
   - "Initializing comment fields..."
   - When clicking button: "Add comment field clicked"
4. If you see "Add comment field button not found" - the button element isn't loading

**Solution:**
- Clear browser cache (Ctrl+Shift+Delete)
- Refresh page (Ctrl+Shift+R)
- Check if page is fully loaded

### Fields Don't Save
**Check:**
1. Open Developer Tools (F12)
2. Go to **Network** tab
3. Click "Save Event"
4. Look for POST request to `admin-ajax.php?action=save_event`
5. Click on it and check the **Response** tab
6. Should show success message

**If error:**
- Check WordPress error logs
- Verify `comment_fields_json` is in POST parameters

### Fields Don't Show on Frontend
**Check:**
1. Database: Does the event have `comment_fields` data?
   ```sql
   SELECT comment_fields FROM wp_sct_events WHERE id = 1;
   ```
2. Check `event-registration.php` includes the comment fields section
3. Verify JSON is valid:
   ```php
   $fields = json_decode($event->comment_fields, true);
   var_dump($fields); // Should be array, not null
   ```

## Advanced Testing

### Test All Field Types

Create an event with fields:

1. **Text Input**
   - Label: "Your Age"
   - Type: "Text Input"
   - Placeholder: "Enter your age"

2. **Checkbox**
   - Label: "I would like to reserve a table"
   - Type: "Checkbox"
   - Placeholder: "Minimum 8 guests required"

3. **Textarea**
   - Label: "Special Requests"
   - Type: "Textarea"
   - Rows: 4
   - Placeholder: "Any dietary restrictions or special needs?"

4. **Select (if implemented)**
   - Label: "Meal Preference"
   - Type: "Dropdown"

### Test Data Submission

1. Register with all fields filled
2. Check registrations table for the data
3. View registration details
4. Export CSV and verify comment columns are present

### Test Required Fields

1. Create field with "Required" checked
2. Try submitting empty form
3. Should show browser validation error
4. Field should be marked with red asterisk

### Test Field Removal

1. Add multiple fields
2. Remove one from middle of list
3. Others should shift up
4. Save event
5. Edit event - removed field should be gone

## Database Queries

### View Stored Comment Fields Configuration
```sql
SELECT id, event_name, comment_fields 
FROM wp_sct_events 
WHERE comment_fields IS NOT NULL;
```

### View Registration Comments
```sql
SELECT id, name, email, comments 
FROM wp_sct_event_registrations 
WHERE comments IS NOT NULL;
```

### Check JSON Validity
```sql
SELECT id, JSON_VALID(comment_fields) as valid_json 
FROM wp_sct_events 
WHERE comment_fields IS NOT NULL;
```

## Console Debugging

Open DevTools Console (F12) and run:

```javascript
// Check if commentFieldsData exists
console.log('Comment Fields Data:', commentFieldsData);

// Check if button listeners are attached
console.log(document.getElementById('add-comment-field-btn'));

// Check if form is found
console.log(document.getElementById('add-event-form'));

// Manually add field for testing
commentFieldsData.push({
    id: 'test_field',
    label: 'Test Field',
    type: 'textarea',
    required: false,
    placeholder: 'Test placeholder',
    rows: 3
});
renderCommentFields(); // Should display new field
```

## Expected Behavior

### When Creating New Event:
✅ Button exists
✅ Clicking button adds field UI
✅ Can fill field properties
✅ Can remove fields
✅ Saving stores JSON to database

### When Editing Event:
✅ Fields load from database
✅ All properties are populated
✅ Can modify existing fields
✅ Can add/remove fields
✅ Changes save correctly

### When Registering:
✅ Fields appear on form (if configured)
✅ Can submit with text content
✅ Checkboxes toggle correctly
✅ Required fields validate
✅ Data saves to database

### When Viewing Registration:
✅ Comments appear in details modal
✅ All field values display
✅ Formatting is readable
✅ Multi-line text is preserved

## Common Issues & Fixes

| Issue | Check | Fix |
|-------|-------|-----|
| Button not responding | Console has errors | Clear cache, reload page |
| Fields not saving | Network request fails | Check nonce, user permissions |
| Frontend fields missing | JSON null in DB | Save event again with fields |
| Comments not showing | DB query returns NULL | Submit registration form with data |
| Validation not working | Check form HTML | Verify required attribute in HTML |

## Performance Notes

- Comment fields stored as JSON for flexibility
- No performance impact unless querying thousands of fields
- CSV export handles comments efficiently
- Admin display is lazy-loaded in modal
