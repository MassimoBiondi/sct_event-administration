# Events List Block - Visual Enhancements

## Overview
The Events List block has been significantly improved with better visual design, capacity warnings, multi-column layouts, and filtering of past events.

## Changes Made

### 1. **Removed Boring Capacity Display**
- ❌ Removed generic "Capacity: X guests" text
- ✅ Replaced with smart, color-coded notices

### 2. **Added Capacity Warnings**

#### "Fully Booked" Notice
- **When**: Event has 0 or fewer available spots
- **Display**: Red notice with ❌ symbol
- **Background**: Light red (`#fee`)
- **Text**: Dark red (`#c00`)
- **Border**: Red left accent

#### "Low Capacity" Notice
- **When**: Less than 5 spots available (but not fully booked)
- **Display**: Orange warning with ⚠ symbol
- **Shows**: "⚠ Only X spot(s) left"
- **Background**: Light orange (`#ffd699`)
- **Text**: Dark brown (`#663300`)
- **Border**: Orange left accent

#### No Notice
- **When**: 5 or more spots available
- **Display**: Clean, no capacity message (less visual clutter)

### 3. **Multi-Column Layout**

#### New Columns Option
- **1 Column** (default): Single column layout
- **2 Columns**: Side-by-side on desktop
- **3 Columns**: Three across on desktop
- **4 Columns**: Four across on desktop

#### Responsive Behavior
- **Desktop (> 1024px)**: Full column layout
- **Tablet (768-1024px)**: 3 & 4 column layouts drop to 2 columns
- **Mobile (< 768px)**: All layouts stack to 1 column

### 4. **Past Events Filtering**

#### Query Changes
- ❌ No longer shows events that have already ended
- ✅ Only displays upcoming/current events
- Checks: `COALESCE(event_end_date, event_date) >= CURDATE()`

#### Empty State
- Message changed: "No events found" → "No upcoming events"

### 5. **Editor Improvements**

#### Removed
- ❌ "Show Capacity" toggle (always hidden now)

#### Layout
- Moved "Columns" control up in the settings
- Better organized controls
- Default sort changed to "Oldest First" (more logical for events)

### 6. **Visual Polish**

#### Card Design
- Clean white background
- Subtle border (`1px solid #ddd`)
- Light shadow effect
- Smooth hover animation (lift + enhanced shadow)
- Rounded corners (`8px`)

#### Spacing
- 30px gap between cards
- Responsive grid layout
- Proper padding and margins throughout

#### Typography
- Clear hierarchy with event titles
- Readable secondary text (dates, location)
- Consistent color scheme

## Block Settings

### Main Settings Panel
- **Number of Events**: 1-50 (default: 10)
- **Columns**: 1-4 (default: 1)
- **Sort By**: 
  - Event Date (Oldest First) ← default
  - Event Date (Newest First)
  - Event Name (A-Z)
  - Event Name (Z-A)

### Display Options Panel
- ✓ Show Event Date & Time (default: on)
- ✓ Show Location (default: on)
- ✓ Show Description (default: on)

## Database Queries

### Optimized Query
```sql
SELECT * FROM wp_sct_events
WHERE (publish_date IS NULL OR publish_date <= NOW())
AND (unpublish_date IS NULL OR unpublish_date >= NOW())
AND COALESCE(event_end_date, event_date) >= CURDATE()
ORDER BY event_date ASC
LIMIT 10
```

**Key Features:**
- Uses `COALESCE()` for multi-day events (checks end date if available)
- Respects publish/unpublish dates
- Filters out past events efficiently
- Sortable by multiple criteria

## Capacity Calculation

### Logic
```php
$registered_guests = SUM(guest_count) FROM registrations
$available_capacity = guest_capacity - registered_guests

// Determinations:
$is_fully_booked = (guest_capacity > 0 && available_capacity <= 0)
$is_low_capacity = (guest_capacity > 0 && 0 < available_capacity < 5)
```

### Unlimited Capacity
- Events with `guest_capacity = 0` are treated as unlimited
- No capacity notices shown
- Only shows when capacity is actually limited

## CSS Classes

### Event Item
- `.sct-event-item` - Main container
- `.sct-event-title` - Event name
- `.sct-event-meta` - Date/time
- `.sct-event-date` - Date/time text
- `.sct-event-location` - Location info
- `.sct-event-notice` - Base notice style
- `.sct-event-fully-booked` - Red fully booked notice
- `.sct-event-low-capacity` - Orange low capacity notice
- `.sct-event-description` - Event description

### Container Classes
- `.sct-events-list-block` - Main block container
- `.sct-events-grid` - Grid container
- `.sct-events-columns-1` - Single column
- `.sct-events-columns-2` - Two columns
- `.sct-events-columns-3` - Three columns
- `.sct-events-columns-4` - Four columns

## Color Scheme

| Element | Color | Usage |
|---------|-------|-------|
| Fully Booked BG | `#fee` | Red notice background |
| Fully Booked Text | `#c00` | Red notice text |
| Fully Booked Border | `#c00` | Red left accent |
| Low Capacity BG | `#ffd699` | Orange notice background |
| Low Capacity Text | `#663300` | Orange notice text |
| Low Capacity Border | `#ff9900` | Orange left accent |
| Card Background | `#fff` | Event card background |
| Card Border | `#ddd` | Event card border |
| Title Text | `#222` | Event title |
| Secondary Text | `#666` | Dates, locations |
| Link Color | `#0073aa` | Map link |

## Browser Support

- ✓ Chrome/Edge (latest)
- ✓ Firefox (latest)
- ✓ Safari (latest)
- ✓ Mobile browsers
- ✓ Responsive down to 320px width

## Performance

### Optimization Features
- Single database query per page load
- Efficient use of `COALESCE()` for multi-day events
- CSS grid for responsive layouts (hardware accelerated)
- Minimal hover animations (transform + box-shadow)

### Load Time
- Optimized CSS selectors
- No JavaScript on frontend (PHP rendered)
- Efficient grid layout calculation

## Accessibility

### Features
- ✓ Semantic HTML structure
- ✓ Proper heading hierarchy
- ✓ Color not sole indicator (uses symbols too)
- ✓ Readable text contrast
- ✓ Touch-friendly card size
- ✓ Responsive design

## Future Enhancements (Optional)

- [ ] Add event thumbnail/featured image
- [ ] Add "Register" button link
- [ ] Filter by category/tag
- [ ] Search functionality
- [ ] Map view option
- [ ] Event status badges (upcoming, happening now, etc.)
- [ ] Custom CSS per notice type
- [ ] Timezone support

## Testing Checklist

- [ ] Display 1, 2, 3, and 4 column layouts
- [ ] Verify past events are hidden
- [ ] Check fully booked notice displays in red
- [ ] Check low capacity notice displays in orange
- [ ] Test with no capacity limit events
- [ ] Verify responsive behavior on mobile
- [ ] Test all sort options
- [ ] Toggle display options on/off
- [ ] Verify hover effects smooth
- [ ] Check on different browsers
