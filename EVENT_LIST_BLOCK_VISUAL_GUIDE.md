# Events List Block - Quick Visual Guide

## What's New

### Before
- Generic "Capacity: 50 guests" text
- Showed all events, including past ones
- Single column only
- Boring layout

### After
- Smart capacity warnings with color coding
- Only upcoming events displayed
- 1-4 column responsive layouts
- Modern card design with hover effects

---

## Visual Notices

### ✅ Plenty of Spots (No Notice)
```
Event Title
August 15, 2025 at 2:00 PM
Location: Downtown Hall
Description of the event...
```

### ⚠ Low Capacity (Orange Warning)
```
Event Title
August 15, 2025 at 2:00 PM
⚠ Only 3 spot(s) left
Location: Downtown Hall
Description of the event...
```

### ❌ Fully Booked (Red Alert)
```
Event Title
August 15, 2025 at 2:00 PM
❌ Fully Booked
Location: Downtown Hall
Description of the event...
```

---

## Layout Examples

### 1 Column (Default)
```
┌─────────────────┐
│  Event 1        │
└─────────────────┘
┌─────────────────┐
│  Event 2        │
└─────────────────┘
┌─────────────────┐
│  Event 3        │
└─────────────────┘
```

### 2 Columns
```
┌──────────────┐  ┌──────────────┐
│   Event 1    │  │   Event 2    │
└──────────────┘  └──────────────┘
┌──────────────┐  ┌──────────────┐
│   Event 3    │  │   Event 4    │
└──────────────┘  └──────────────┘
```

### 3 Columns
```
┌────────────┐  ┌────────────┐  ┌────────────┐
│ Event 1    │  │ Event 2    │  │ Event 3    │
└────────────┘  └────────────┘  └────────────┘
┌────────────┐  ┌────────────┐  ┌────────────┐
│ Event 4    │  │ Event 5    │  │ Event 6    │
└────────────┘  └────────────┘  └────────────┘
```

### 4 Columns
```
┌──────┐  ┌──────┐  ┌──────┐  ┌──────┐
│ E1   │  │ E2   │  │ E3   │  │ E4   │
└──────┘  └──────┘  └──────┘  └──────┘
┌──────┐  ┌──────┐  ┌──────┐  ┌──────┐
│ E5   │  │ E6   │  │ E7   │  │ E8   │
└──────┘  └──────┘  └──────┘  └──────┘
```

---

## Block Settings

### Main Settings
```
Number of Events: [10]  (1-50)
Columns:          [1]   (1, 2, 3, 4)
Sort By:          [Event Date (Oldest First)]
  - Event Date (Oldest First)
  - Event Date (Newest First)
  - Event Name (A-Z)
  - Event Name (Z-A)
```

### Display Options
```
☑ Show Event Date & Time
☑ Show Location
☑ Show Description
```

---

## Color Scheme

### Notices
- **Red** (Fully Booked): `#c00` on `#fee`
- **Orange** (Low Capacity): `#663300` on `#ffd699`

### Cards
- **Background**: White (`#fff`)
- **Border**: Light gray (`#ddd`)
- **Title**: Dark gray (`#222`)
- **Text**: Medium gray (`#666`)

### Hover Effect
- Lifts up 3px
- Enhanced shadow
- Smooth animation

---

## Responsive Breakpoints

| Screen Size | 1 Col | 2 Col | 3 Col | 4 Col |
|---|---|---|---|---|
| Mobile < 768px | 1 | 1 | 1 | 1 |
| Tablet 768-1024px | 1 | 2 | 2 | 2 |
| Desktop > 1024px | 1 | 2 | 3 | 4 |

---

## Key Features

✓ **Smart Capacity Warnings**
  - Red alert when fully booked
  - Orange warning at < 5 spots
  - Clean when plenty available

✓ **Multi-Column Layouts**
  - Choose 1, 2, 3, or 4 columns
  - Responsive design
  - Mobile-friendly

✓ **Past Events Filtered**
  - Only upcoming events shown
  - Based on end date for multi-day
  - Smart date comparison

✓ **Professional Design**
  - Card-based layout
  - Smooth hover effects
  - Consistent styling
  - Accessible colors

✓ **Flexible Display**
  - Show/hide dates, location, description
  - Multiple sort options
  - Set event count limit

---

## Example Block Usage

```
Add Block > Events List
  • Number of Events: 6
  • Columns: 3 (will be 2 on tablet, 1 on mobile)
  • Sort By: Event Date (Oldest First)
  • Show: Date, Location, Description
```

Result: Displays 6 upcoming events in a 3-column grid, automatically responsive, with smart capacity warnings.
