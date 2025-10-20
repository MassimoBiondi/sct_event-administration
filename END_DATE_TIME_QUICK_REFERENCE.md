# Event End Date/Time - Quick Reference

## Creating Different Event Types

### 1. All-Day Event (Single Day)
- **Start Date**: September 15, 2025
- **Start Time**: *(leave blank)*
- **End Date**: *(leave blank)*
- **End Time**: *(leave blank)*

**Display**: September 15, 2025

---

### 2. Single-Day Event with Time
- **Start Date**: September 15, 2025
- **Start Time**: 2:00 PM
- **End Date**: *(leave blank)*
- **End Time**: *(leave blank)*

**Display**: September 15, 2025 at 2:00 PM

---

### 3. Single-Day Event with Time Range
- **Start Date**: September 15, 2025
- **Start Time**: 2:00 PM
- **End Date**: *(leave blank)*
- **End Time**: 5:00 PM

**Display**: September 15, 2025 at 2:00 PM – 5:00 PM

---

### 4. Multi-Day Event (No Time)
- **Start Date**: September 15, 2025
- **Start Time**: *(leave blank)*
- **End Date**: September 17, 2025
- **End Time**: *(leave blank)*

**Display**: September 15 – September 17, 2025 (all-day)

---

### 5. Multi-Day Event with Start Time
- **Start Date**: September 15, 2025
- **Start Time**: 2:00 PM
- **End Date**: September 17, 2025
- **End Time**: *(leave blank)*

**Display**: September 15 – September 17, 2025 at 2:00 PM

---

## Email Placeholders

### New Placeholders Available

| Placeholder | Description | Example |
|---|---|---|
| `{event_date}` or `{event.date}` | Start date | September 15, 2025 |
| `{event_time}` or `{event.time}` | Start time | 2:00 PM |
| `{event_end_date}` or `{event.end_date}` | End date | September 17, 2025 |
| `{event_end_time}` or `{event.end_time}` | End time | 5:00 PM |
| `{event_date_range}` or `{event.date_range}` | Full formatted range | September 15 – 17, 2025 at 2:00 PM |

### Example Email Template
```
Hello {name},

Thank you for registering for {event_name}!

Event Details:
- When: {event_date_range}
- Where: {location_name}
- Number of Attendees: {guest_count}

{manage_registration_link}

Best regards,
Event Team
```

---

## Validation Rules

The form will prevent invalid combinations:

❌ **Cannot create event with:**
- No start time AND no end date (requires at least one)
- End date before start date
- End time without start time

✓ **Valid combinations:**
- Start date only
- Start date + start time
- Start date + start time + end time (same day)
- Start date + end date (all-day multi-day)
- Start date + end date + start time

---

## Database Notes

### New Columns
All columns are **nullable** to maintain backward compatibility:
- `event_end_date` (DATE, NULL)
- `event_end_time` (TIME, NULL)
- `event_time` (TIME, NULL) - was changed from NOT NULL

### Existing Events
- Old events with only `event_date` and `event_time` work unchanged
- No data loss or migration needed
- Display automatically adapts to available data

---

## Frontend Display

### Events List
Events display with intelligent date/time formatting:
- Shows only relevant information (hides empty time fields)
- Multi-day events clearly show date range
- All-day events marked as "(all-day)"

### Events Block
The Events List block uses the same formatting:
- Can be customized to show/hide dates
- Responsive design adapts to screen size
- Consistent styling across frontend

---

## Admin Panel Features

### Event List Page
- All events display with their date ranges
- Past events section separate from upcoming
- Edit/delete options per event

### Add/Edit Event Page
- Clear field labels explain what each field does
- Helper text indicates optional fields
- Form validation provides clear error messages
- Can duplicate and modify existing events

---

## Troubleshooting

### "Either a start time or an end date must be provided"
**Solution**: You need to either:
- Set a start time, OR
- Set an end date for a multi-day event

### "End date cannot be before start date"
**Solution**: Make sure your end date is the same or after your start date

### "Start time must be provided if end time is set"
**Solution**: If you set an end time, you must also set a start time

### Date shows as all-day but I set a time
**Solution**: Make sure you filled in the start time field (not just the end time)

---

## Technical Details

### How Date Formatting Works
The `EventPublic::format_event_date_range()` method:
1. Checks if there's an end date (multi-day)
2. Checks if there's a start time
3. Checks if there's an end time
4. Formats appropriately based on what's available

### Email Processing
- Placeholders are replaced before sending
- Uses WordPress's wp_mail() function
- Supports HTML and plain text emails
- Logs all sent emails for history

---

## API/Developer Usage

### Get Formatted Date Range in Code
```php
$event = $wpdb->get_row("SELECT * FROM wp_sct_events WHERE id = 123");
$date_range = EventPublic::format_event_date_range($event);
echo $date_range; // Output: September 15 – 17, 2025 at 2:00 PM
```

### Access New Fields
```php
$event->event_end_date;  // 2025-09-17
$event->event_end_time;  // 17:00:00
```

### Custom Display in Templates
```php
<?php
  // Display only date without time
  echo date('F j, Y', strtotime($event->event_date));
  if (!empty($event->event_end_date)) {
    echo ' – ' . date('F j, Y', strtotime($event->event_end_date));
  }
?>
```
