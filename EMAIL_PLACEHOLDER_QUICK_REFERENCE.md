# Spring Ball Email Template - Quick Reference

## TL;DR - What Changed

### Before (Broken ❌)
```
Event:                 {{event.name}}              ← NOT replaced
Booking:               {{additional.field_XXXXX}}  ← Cryptic ID!
```

Email output:
```
Event:                 {{event.name}}              ← Still shows placeholder!
Booking:               {{additional.field_XXXXX}}  ← Cryptic!
```

### After (Working ✅)
```
Event:                 {{event_title}}
Booking:               {{additional_field_1}}
```

Email output:
```
Event:                 Spring Ball                 ← Replaced!
Booking:               Yes, 8 people              ← Replaced!
```

## All Available Placeholders

### Basic Info
- `{{attendee_name}}` - Person's name
- `{{attendee_email}}` - Person's email
- `{{attendee_guest_count}}` - Number of guests

### Event Info
- `{{event_title}}` - Event name
- `{{event_date}}` - Event date
- `{{event_time}}` - Event time
- `{{location_name}}` - Location

### Registration Info
- `{{registration_id}}` - Confirmation number
- `{{registration_date}}` - Registration date
- `{{registration_manage_link}}` - Link to manage

### Additional Fields
- `{{additional_field_1}}` - 1st custom field
- `{{additional_field_2}}` - 2nd custom field
- `{{whole_table_booking}}` - By field label
- `{{seating_preferences}}` - By field label

### System Info
- `{{website_name}}` - Your site name
- `{{website_url}}` - Your site URL
- `{{current_year}}` - Current year

### Special Sections
- `{pricing_overview}` - Pricing table
- `{payment_method_details}` - Payment info

## How to Use Templates

### Step 1: Copy Template
Copy from one of these files:
- `/email-templates/spring-ball-template.txt` (plain text)
- `/email-templates/spring-ball-template.html` (HTML)

### Step 2: Add to Event
1. Admin → Events → Spring Ball (edit)
2. Scroll to "Custom Email Template"
3. Paste template
4. Update

### Step 3: Test
Send a test registration and check email inbox.

## Common Mistakes ❌

❌ `{{event.name}}` - Wrong! Use `{{event_title}}`
❌ `{{additional.field_123}}` - Wrong! Use `{{additional_field_1}}`
❌ `{attendee_name}` - Works but not best format, use `{{attendee_name}}`

## Testing Checklist

- [ ] Event name shows correctly
- [ ] Date/time shows correctly
- [ ] Attendee name shows correctly
- [ ] No `{{placeholder}}` text visible
- [ ] Additional fields show values (not empty)
- [ ] Pricing table displays
- [ ] Payment info displays
- [ ] All links work

## Technical Details

**Files Changed:**
- `/includes/class-event-public.php` - Added field mapping + additional fields query
- `/email-templates/spring-ball-template.txt` - Updated placeholders
- `/email-templates/spring-ball-template.html` - Updated placeholders

**How It Works:**
1. Email sends
2. System gathers data from database
3. Automatically maps old field names to new ones
4. Queries additional fields and creates simple names
5. Replaces all placeholders with actual values
6. Sends formatted email

## Need Help?

1. **Placeholder not replacing?**
   - Check placeholder name spelling
   - Check WordPress debug.log for errors
   - Test with simple email first

2. **Additional fields showing empty?**
   - Make sure field has a value
   - Make sure field is configured for event
   - Resend test email

3. **Want to add custom fields?**
   - Create additional field in Spring Ball event settings
   - Use `{{additional_field_N}}` in template
   - Or use field label converted to snake_case: `{{my_field_name}}`

---

**Status**: ✅ Ready to Use
**Version**: 2.0 (Fixed Placeholders)
