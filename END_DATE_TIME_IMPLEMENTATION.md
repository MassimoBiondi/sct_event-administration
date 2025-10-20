# Event End Date and End Time Implementation

## Overview
Successfully implemented support for multi-day events and optional times in the SCT Event Administration plugin. This allows events to:
- Span multiple days (start date → end date)
- Have optional start and end times
- Display all-day event designations

## Database Changes

### New Columns Added to `wp_sct_events` Table:
1. **`event_end_date`** (DATE, nullable) - End date for multi-day events
2. **`event_end_time`** (TIME, nullable) - End time for events with time ranges
3. **`event_time`** (TIME, changed from NOT NULL to nullable) - Now optional

### Migration Function
Updated `event_admin_update_database()` in `/sct_event-administraion.php` to:
- Add `event_end_date` column if missing
- Add `event_end_time` column if missing
- Make `event_time` nullable on existing tables
- Execute safely with conditional checks

## Form Changes

### Admin Event Creation/Edit Form
**File:** `/admin/views/add-event.php`

Updated the event date/time section to include:
1. **Start Date** (required) - Date picker
2. **Start Time** (optional) - Time picker with note about all-day events
3. **End Date** (optional) - Date picker for multi-day events
4. **End Time** (optional) - Time picker, only used if start time is set

### Form Validation Rules
**File:** `/includes/class-event-admin.php` - `save_event()` method

Added validation logic:
- ✓ Either `event_time` OR `event_end_date` must be provided
- ✓ End date cannot be before start date (if provided)
- ✓ Start time must be provided if end time is set
- ✓ End time must be after start time (for single-day events)

### Data Format
Updated the `$data_format` array to include format specifiers for new fields:
- `'%s'` for `event_end_date`
- `'%s'` for `event_end_time`

## Display Formatting

### New Helper Method
**File:** `/includes/class-event-public.php`

Created static method `format_event_date_range($event)` that intelligently displays:

**Multi-day events (with end date):**
- Without time: `August 15 – August 17, 2025 (all-day)`
- With start time: `August 15 – August 17, 2025 at 2:00 PM`

**Single-day events (no end date):**
- Without time: `August 15, 2025`
- With time: `August 15, 2025 at 2:00 PM`
- With time range: `August 15, 2025 at 2:00 PM – 5:00 PM`

### Views Updated

1. **Frontend Events List**
   - File: `/public/views/events-list.php`
   - Uses: `EventPublic::format_event_date_range($event)`

2. **Events List Block**
   - File: `/includes/class-event-list-block.php`
   - Uses: `format_event_date_range()` for consistent display

3. **Waiting List Emails**
   - File: `/includes/class-event-public.php`
   - Admin and user emails now display formatted date ranges

## Email Placeholders

### New Placeholders Added
**File:** `/includes/class-placeholder-system.php`

- **`{event_end_date}`** or **`event.end_date`** - End date for multi-day events
- **`{event_end_time}`** or **`event.end_time`** - End time for events
- **`{event_date_range}`** or **`event.date_range`** - Full formatted date/time range

### Backward Compatibility
Added old-format mappings for:
- `{event_end_date}` → `event.end_date`
- `{event_end_time}` → `event.end_time`
- `{event_date_range}` → `event.date_range`

## User Experience

### Admin Interface
- Clear labeling of start/end dates and times
- Helper text explaining optional fields
- All-day event support with visual distinction

### Frontend Display
- Automatic formatting of date ranges
- Smart display of times (only shown when relevant)
- All-day event indication

### Email Notifications
- Consistent date/time formatting across all emails
- Optional use of new placeholders
- Backward compatible with existing templates

## Testing Checklist

- [ ] Create all-day event (no start time, no end date)
- [ ] Create multi-day event (no time)
- [ ] Create multi-day event with start time
- [ ] Create single-day event with start and end time
- [ ] Verify form validation prevents invalid combinations
- [ ] Check waiting list emails display correctly
- [ ] Test Events List block with various event types
- [ ] Verify dates in frontend event list
- [ ] Test on existing installations (migration)

## Backward Compatibility

✓ Existing events without end dates/times continue to work
✓ Migration function handles existing tables gracefully
✓ Placeholder system maintains old format support
✓ Display logic gracefully handles NULL values

## File Changes Summary

| File | Changes |
|------|---------|
| `/sct_event-administraion.php` | Database schema & migration function updated |
| `/admin/views/add-event.php` | Added end date/time form fields |
| `/includes/class-event-admin.php` | Form validation & data format arrays |
| `/includes/class-event-public.php` | New formatter, updated email content |
| `/includes/class-event-list-block.php` | Updated date display |
| `/public/views/events-list.php` | Using new formatter |
| `/includes/class-placeholder-system.php` | New email placeholders |
