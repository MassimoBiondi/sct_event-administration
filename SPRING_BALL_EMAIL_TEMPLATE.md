# Spring Ball Email Template - Professional Design

## Overview

A sophisticated, elegant HTML email template designed specifically for the **Spring Ball** event hosted by the Japan Swiss Society. The template combines classic styling with modern email practices for a premium registration confirmation experience.

## Template Files

### 1. **spring-ball-template.html**
Professional HTML email with:
- Sophisticated dark navy and gold color scheme
- Elegant typography with Georgia serif font
- Responsive design for all devices
- Structured sections with clear visual hierarchy
- Professional footer with organization branding

### 2. **spring-ball-template.txt**
Plain text alternative for:
- Email clients that don't support HTML
- Accessibility compliance
- Email deliverability optimization

## Design Features

### Color Palette
- **Primary Dark**: `#2c3e50` (Deep Navy Blue)
- **Accent Gold**: `#c0a080` (Warm Champagne Gold)
- **Background**: `#f5f5f5` (Soft Gray)
- **Text**: `#333` / `#555` (Professional Charcoal)

### Typography
- **Header Font**: Georgia serif - elegant and traditional
- **Body Font**: Georgia serif - professional and readable
- **Font Size**: Scaled appropriately (36px header, 14px body, 12px fine print)
- **Letter Spacing**: Carefully applied for sophistication

### Layout Sections

```
┌─────────────────────────────────────────┐
│          HEADER (Dark Navy)             │
│      Spring Ball - Confirmation         │
│          Gold divider line              │
└─────────────────────────────────────────┘
│                                         │
│  • Greeting paragraph                   │
│  • Divider                              │
│  • EVENT DETAILS section                │
│  • Confirmation number box              │
│  • YOUR PREFERENCES section             │
│  • ADDITIONAL INFORMATION fields        │
│  • IMPORTANT INFORMATION                │
│  • Call-to-Action button                │
│  • QUESTIONS? contact info              │
│  • Note disclaimer                      │
│                                         │
├─────────────────────────────────────────┤
│     FOOTER (Dark Navy w/ Gold Top)      │
│   Copyright & Organization Info        │
└─────────────────────────────────────────┘
```

## Placeholders Used

### Event Information
- `{{event.name}}` - Spring Ball
- `{{event.date}}` - March 5, 2026
- `{{event.time}}` - 6:00 PM
- `{{event.location_name}}` - Imperial Hotel Tokyo

### Attendee Information
- `{{attendee.name}}` - Guest's full name
- `{{attendee.guest_count}}` - Number of guests

### Registration Details
- `{{registration.id}}` - Confirmation number
- `{{registration.date}}` - When they registered
- `{{registration.manage_link}}` - Management page URL

### Additional Fields (Spring Ball Specific)
- `{{additional.field_1762071449893_z4dllajxd}}` - Whole Table booking preference
- `{{additional.field_1762133522923_shixzl65f}}` - Seating preferences

### Website Information
- `{{website.name}}` - Organization name
- `{{website.url}}` - Organization website
- `{{date.year}}` - Current year for copyright

## How to Apply This Template

### Option 1: Use in WordPress Admin

1. **Go to**: Events > Add/Edit Event > Spring Ball
2. **Scroll to**: Custom Email Template
3. **Copy entire HTML** from `spring-ball-template.html`
4. **Paste** into the WYSIWYG editor
5. **Click**: Update Event
6. **Test**: Send a test email to verify formatting

### Option 2: Use from Settings

1. **Go to**: Settings > Email Templates
2. **Find**: Confirmation Email Template
3. **Copy content** from `spring-ball-template.html`
4. **Paste** and save as global default

### Option 3: Use Both HTML and Text

For maximum compatibility:
- **HTML version**: `spring-ball-template.html` (visual design)
- **Text version**: `spring-ball-template.txt` (fallback)

WordPress will automatically use the HTML version when supported.

## Features

✅ **Responsive Design**
- Adapts to mobile, tablet, and desktop screens
- Max-width: 600px for optimal readability

✅ **Professional Appearance**
- Premium color scheme (navy & gold)
- Classic serif typography
- Elegant dividers and spacing
- Sophisticated borders

✅ **Clear Information Hierarchy**
- Sections clearly labeled
- Important details highlighted
- Call-to-action prominently displayed

✅ **Complete Field Integration**
- All Spring Ball additional information fields included
- Event details clearly stated
- Confirmation details easy to find
- Registration preferences displayed

✅ **Modern Email Practices**
- Proper HTML structure
- Inline CSS for compatibility
- Alt text ready (if images added)
- Footer compliance information

✅ **Accessibility**
- Readable font sizes
- Good color contrast
- Semantic HTML structure
- Text fallback version included

## Customization Options

### Change Colors
Find and replace in the HTML:
- Navy Blue (`#2c3e50`) → Your primary color
- Champagne Gold (`#c0a080`) → Your accent color
- Light Background (`#f9f7f4`) → Your secondary background

### Modify Typography
Search for font-family declarations:
- `'Georgia', 'Times New Roman', serif` → Your preferred serif font
- Adjust `font-size` values for different scales

### Add Logo
In the header section, add before or after the title:
```html
<img src="{{your-logo-url}}" alt="Organization Logo" style="max-width: 80px; margin-bottom: 15px;">
```

### Add Images
Insert after the greeting:
```html
<img src="{{spring-ball-image-url}}" alt="Spring Ball" style="max-width: 100%; height: auto; margin: 20px 0;">
```

### Adjust Sections
Simply add, remove, or reorder sections as needed. The template is modular.

## Testing

### Before Sending to Guests

1. **Preview in Email Clients**:
   - Gmail (web & mobile)
   - Outlook (desktop & web)
   - Apple Mail
   - iOS Mail
   - Android Mail

2. **Check Placeholders**:
   - Verify all `{{placeholder}}` values render correctly
   - Test with sample data first

3. **Test Links**:
   - Click the "View Details" button
   - Verify `{{registration.manage_link}}` URL is correct

4. **Check Rendering**:
   - Verify gold dividers appear correctly
   - Check that sections have proper spacing
   - Ensure header and footer display properly

### Using Mailtrap or Litmus
- Send to Litmus testing addresses
- Review rendering across email clients
- Fix any compatibility issues

## Email Client Compatibility

| Client | HTML | Text | Notes |
|--------|------|------|-------|
| Gmail | ✅ | ✅ | Full support |
| Outlook | ✅ | ✅ | Full support |
| Apple Mail | ✅ | ✅ | Full support |
| Thunderbird | ✅ | ✅ | Full support |
| AOL Mail | ✅ | ✅ | Good support |
| Yahoo Mail | ✅ | ✅ | Good support |
| Android Gmail | ✅ | ✅ | Full support |
| iOS Mail | ✅ | ✅ | Full support |

## Spring Ball Event Details (For Reference)

| Detail | Value |
|--------|-------|
| **Event** | Spring Ball |
| **Date** | March 5, 2026 |
| **Time** | 6:00 PM (18:00) |
| **Location** | Imperial Hotel Tokyo, Banquet Hall "Fuji" (3F) |
| **Organization** | Japan Swiss Society |
| **Theme** | Classic Elegance |
| **Additional Fields** | Whole Table, Seating Preferences |

## Optimization Tips

1. **Subject Line Suggestions**:
   - "Your Spring Ball Registration Confirmed"
   - "Welcome to the Spring Ball - March 5, 2026"
   - "Confirmation: Spring Ball Registration"

2. **Send Time**: 
   - Avoid late evening/early morning
   - Best: 10 AM - 2 PM in recipient timezone

3. **Follow-up**:
   - Send 2 weeks before event: Reminder email
   - Send 3 days before: Final details email
   - Send 1 day after: Thank you email

4. **Tracking**:
   - Add UTM parameters to links
   - Track email opens (if using email service provider)
   - Monitor click-through rates

## Troubleshooting

### Template Not Rendering
- Check that all HTML is valid
- Verify no special characters are broken
- Test in different email client

### Placeholders Showing as Text
- Verify placeholder format: `{{key}}`
- Ensure data is available in registration
- Check spelling of field IDs exactly

### Colors Not Showing
- Inline styles may be stripped by email filters
- Test in Outlook specifically
- Consider adding background images

### Mobile Display Issues
- Verify responsive styles are intact
- Test on actual mobile devices
- Check that max-width is set to 600px

## File Sizes

- **HTML Template**: ~8 KB (optimized)
- **Text Template**: ~2 KB
- **Total**: ~10 KB (very efficient)

## Future Enhancements

1. Add Spring Ball logo image
2. Include interactive elements (if email client supports)
3. Add social media links
4. Include event schedule/agenda
5. Add payment information section
6. Include parking/transportation info
7. Add weather forecast widget

## Version History

- **v1.0** (Nov 3, 2025): Initial professional template created for Spring Ball event

