# Multi-Day All-Day Events Admin Fix

## Problem

Multi-day events without a start time (all-day events with optional `event_time = NULL`) were not showing up in the Event Admin overview list, making them impossible to edit.

## Root Cause

The database query in `display_events_list_page()` used:

```sql
WHERE CONCAT(event_date, ' ', event_time) >= NOW()
```

When `event_time` is NULL (for all-day events), MySQL's `CONCAT()` function returns NULL, which fails the comparison. This caused all-day multi-day events to be filtered out.

## Solution

Updated all affected database queries and display logic to handle NULL time values:

### 1. Database Query Fix (`class-event-admin.php`)

**File:** `includes/class-event-admin.php`
**Method:** `display_events_list_page()`

**Before:**
```php
WHERE CONCAT(event_date, ' ', event_time) >= NOW()
```

**After:**
```php
WHERE COALESCE(event_end_date, event_date) >= CURDATE()
```

**Benefits:**
- Uses `COALESCE()` to handle NULL values gracefully
- Checks end date for multi-day events (backward-compatible)
- Simpler logic matching the frontend block query
- Works for all event types:
  - Single-day with time
  - Single-day all-day (time = NULL)
  - Multi-day with time
  - Multi-day all-day (time = NULL)

### 2. Admin Display Fixes

#### events-list.php
**File:** `admin/views/events-list.php`

**Before:**
```php
<?php if ($event->event_time == '00:00:00'): ?>
```

**After:**
```php
<?php if (empty($event->event_time) || $event->event_time == '00:00:00'): ?>
```

#### events.php
**File:** `admin/views/events.php`

**Before:**
```php
<td><?php echo esc_html(date('H:i', strtotime($event->event_time))); ?></td>
```

**After:**
```php
<td><?php echo !empty($event->event_time) ? esc_html(date('H:i', strtotime($event->event_time))) : ''; ?></td>
```

**Result:** Displays blank cell instead of formatting error when time is NULL

## Event Types Now Supported in Admin List

✅ **Single-day with time**
- event_date: 2025-10-20
- event_time: 14:30:00
- event_end_date: NULL
- event_end_time: NULL

✅ **Single-day all-day (no start time)**
- event_date: 2025-10-20
- event_time: NULL
- event_end_date: NULL
- event_end_time: NULL

✅ **Multi-day with time**
- event_date: 2025-10-20
- event_time: 14:30:00
- event_end_date: 2025-10-22
- event_end_time: 17:00:00

✅ **Multi-day all-day (no start time)**
- event_date: 2025-10-20
- event_time: NULL
- event_end_date: 2025-10-22
- event_end_time: NULL

## Files Modified

1. `includes/class-event-admin.php` - Database query fix
2. `admin/views/events-list.php` - Null check for time display
3. `admin/views/events.php` - Null check for time display

## Testing Checklist

- ✅ PHP syntax validated (no errors)
- ✅ All-day single-day events now visible in admin list
- ✅ All-day multi-day events now visible in admin list
- ✅ Events with times still display correctly
- ✅ Time column shows blank for all-day events
- ✅ Events are editable from the list
- ✅ Sorting by date/time works correctly

## Backward Compatibility

✅ No database migration required
✅ Existing events continue to work
✅ No changes to form validation
✅ No changes to frontend display
✅ No changes to email functionality

## Version Updated

Plugin version updated to **2.10.0** to reflect this fix.

---

**Implementation Date:** October 20, 2025
**Status:** ✅ Complete and Validated
