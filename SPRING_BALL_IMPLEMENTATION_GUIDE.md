# Spring Ball Email Template - Implementation Guide

## Quick Start (5 Minutes)

### Step 1: Copy the HTML Template
1. Open: `email-templates/spring-ball-template.html`
2. Select all content (Ctrl+A / Cmd+A)
3. Copy to clipboard

### Step 2: Add to Spring Ball Event
1. Go to WordPress Admin
2. Navigate: **Events → Add/Edit Event → Spring Ball**
3. Scroll down to: **"Custom Email Template"**
4. Click in the editor and paste the HTML
5. Click: **Update Event**

### Step 3: Test Send
1. Go to: **Event Registrations**
2. Click: **Add Registration**
3. Fill in test data:
   - Name: "Test Person"
   - Email: your-email@example.com
   - Guest Count: 2
   - Fill in seating preferences
4. Submit and check your email

---

## Complete Implementation

### Option A: Event-Specific Template (Recommended)

Best for: One-time use for this specific event

```
WordPress Admin
↓
Events → Edit Spring Ball
↓
Scroll to: Custom Email Template section
↓
Paste HTML from spring-ball-template.html
↓
Click: Update Event
↓
Done! All confirmations use this template
```

**Advantages:**
- ✓ Unique template per event
- ✓ Easy to customize for future Spring Balls
- ✓ Doesn't affect other events

**Disadvantages:**
- Must repeat for each new event

---

### Option B: Global Default Template

Best for: Consistent branding across all events

```
WordPress Admin
↓
Settings → Email Templates
↓
Scroll to: Confirmation Email Template
↓
Replace with spring-ball-template.html content
↓
Click: Save Settings
↓
Done! All events use this template
```

**Advantages:**
- ✓ One-time setup
- ✓ Consistent across all events
- ✓ Professional appearance for all

**Disadvantages:**
- Affects all events (be cautious)

---

### Option C: Event + Global Fallback

Best for: Flexibility and consistency

```
Implementation:
1. Set up Option B (Global default)
2. Override with Option A (Event-specific) for Spring Ball
3. Other events use global template
4. Spring Ball uses custom template

Result:
- Spring Ball: Special branded template
- Other events: Professional default
- Best of both worlds
```

---

## Customization Guide

### 1. Add Your Organization Logo

**Find this section:**
```html
<div class="header">
    <h1 class="header-title">Spring Ball</h1>
    <div class="header-subtitle">Registration Confirmation</div>
</div>
```

**Add logo (before title):**
```html
<div class="header">
    <img src="https://example.com/logo.png" alt="Organization Logo" 
         style="max-width: 100px; margin-bottom: 15px;">
    <h1 class="header-title">Spring Ball</h1>
    <div class="header-subtitle">Registration Confirmation</div>
</div>
```

### 2. Change Colors

**Find the style section:**
```html
<style>
    .header {
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
    }
</style>
```

**Change colors:**
```html
<style>
    .header {
        background: linear-gradient(135deg, #YOUR_COLOR_1 0%, #YOUR_COLOR_2 100%);
    }
    
    /* Change all #c0a080 to your accent color */
    /* Change all #2c3e50 to your primary color */
</style>
```

**Use this tool to generate gradient:** https://www.colordot.it/

### 3. Modify Event Information

**Find:**
```html
<div class="detail-row">
    <span class="detail-label">Time</span>
    <span class="detail-value">{{event.time}}</span>
</div>
```

**Add new details (e.g., RSVP deadline):**
```html
<div class="detail-row">
    <span class="detail-label">RSVP Deadline</span>
    <span class="detail-value">February 28, 2026</span>
</div>
```

### 4. Add Additional Sections

**Example: Parking Information**

Add after the "Important Information" section:
```html
<div class="section">
    <div class="section-title">Parking Information</div>
    <p style="font-size: 14px; line-height: 1.8; color: #555;">
        Complimentary parking is available at the Imperial Hotel. 
        Please provide your license plate at check-in.
    </p>
</div>
```

### 5. Adjust Placeholders

**All available placeholders:**
```
{{event.name}}                    - Event title
{{event.date}}                    - Event date
{{event.time}}                    - Event start time
{{event.location_name}}           - Location
{{attendee.name}}                 - Guest name
{{attendee.guest_count}}          - Number of guests
{{registration.id}}               - Confirmation #
{{registration.date}}             - Registration date
{{registration.manage_link}}      - Management URL
{{additional.FIELD_ID}}           - Custom fields
{{website.name}}                  - Organization name
{{website.url}}                   - Website URL
{{date.year}}                     - Current year
```

---

## Testing & Quality Assurance

### Test Checklist

Before sending to real guests:

```
BEFORE SENDING
□ Copy template correctly
□ Paste into correct location
□ Save changes
□ Clear cache if needed

VISUAL TESTING
□ Desktop email client (Gmail, Outlook)
□ Mobile email (iPhone Mail, Gmail app)
□ Check header displays correctly
□ Verify gold dividers appear
□ Test CTA button color

DATA TESTING
□ All placeholders render correctly
□ Registration number displays
□ Event details are accurate
□ Additional fields show data
□ Links are clickable

FUNCTIONAL TESTING
□ Click "View Details" button
□ Verify it goes to management page
□ Check all links work correctly
□ Test from different email addresses

CONTENT REVIEW
□ Proofread all text
□ Verify organization name is correct
□ Check event details are current
□ Ensure footer information is accurate
```

### Send Test Email

1. Go to **Event Registrations → Spring Ball**
2. Click **Add Registration**
3. Fill in your email as test recipient
4. Add sample data
5. Submit registration
6. Check your email inbox
7. Review layout, colors, placeholders
8. Make any adjustments needed

### Email Client Testing Services

For professional testing:
- **Litmus**: https://litmusapp.com (Premium)
- **Email on Acid**: https://www.emailonacid.com (Premium)
- **Stripo**: https://stripo.email (Free & Premium)

---

## Troubleshooting

### Issue: Placeholders Show as `{{placeholder}}`

**Cause:** Placeholders not being replaced by system

**Solution:**
1. Verify placeholder syntax is exactly correct
2. Check that field data exists in registration
3. Test with actual registration submission (not manual entry)
4. Review email sending code

### Issue: Colors Don't Display

**Cause:** Email client doesn't support inline CSS

**Solution:**
1. Test in different email clients
2. Some clients strip CSS - this is normal
3. Text will still be readable
4. Consider simpler design for maximum compatibility

### Issue: Gold Dividers Missing

**Cause:** Email client strips CSS borders

**Solution:**
1. Use table-based layout (more compatible)
2. Or accept that some clients won't display them
3. Content is still readable without dividers

### Issue: Images Don't Load

**Cause:** Email image blocking

**Solution:**
1. Always include alt text
2. Ensure alt text conveys message
3. Test with images disabled
4. Current template has no images (safe)

### Issue: Mobile Layout Broken

**Cause:** Template not responsive

**Solution:**
- Current template includes responsive CSS
- If still broken: test in different email client
- Some old clients don't support media queries

---

## Optimization Tips

### For Better Deliverability

1. **Subject Line:** Use clear, non-spammy subject
   - ✓ Good: "Your Spring Ball Registration Confirmed"
   - ✗ Bad: "URGENT: CLICK HERE SPRING BALL SAVE 50%"

2. **From Address:** Use recognizable sender
   - ✓ Good: events@jsgsociety.com
   - ✗ Bad: noreply@unknown-domain.com

3. **List-Unsubscribe:** Consider adding footer link
   - Required for compliance in some regions

4. **SPF/DKIM:** Set up proper DNS records
   - Improves deliverability
   - Talk to your IT team

### For Better Open Rates

1. **Send Time:** Best times are typically
   - Tuesday-Thursday: 10 AM - 2 PM
   - Avoid: Late evening, early morning, weekends

2. **Frequency:** Not too many emails
   - 1 confirmation: Immediate (this template)
   - 1 reminder: 2 weeks before
   - 1 final: 3 days before
   - 1 thank you: 1 day after

3. **Personalization:** Use names in greeting
   - Already done: "Dear {{attendee.name}}"

---

## Performance Metrics

### Email Size
- HTML + CSS: ~8 KB
- Total: Very lightweight
- Load time: Instant

### Expected Performance
- Delivery rate: >95% (with proper setup)
- Open rate: 20-40% (typical for event emails)
- Click rate: 2-5% (typical for CTAs)

### Monitor These:
- Bounce rate (keep <5%)
- Spam complaints (keep <0.1%)
- Unsubscribe rate (keep <1%)

---

## Going Live - Checklist

### 1-2 Weeks Before Event
- [ ] Template tested in all email clients
- [ ] All placeholders verified correct
- [ ] Subject line approved
- [ ] Sender address set
- [ ] Management page link tested

### 3 Days Before Event
- [ ] Final review of template
- [ ] Any last-minute updates made
- [ ] Test registrations cleared
- [ ] Ready to send to real guests

### Day of Event
- [ ] Confirmations sent automatically on registration
- [ ] Monitor for any issues
- [ ] Check for bounced emails
- [ ] Monitor spam complaints

### After Event
- [ ] Send thank you email
- [ ] Collect feedback on email design
- [ ] Save template for next year
- [ ] Archive registrations

---

## Next Steps

1. **Immediate:**
   - [ ] Copy template
   - [ ] Paste into Spring Ball event
   - [ ] Send test email
   - [ ] Review in email client

2. **Short-term:**
   - [ ] Customize colors/content as needed
   - [ ] Add organization logo if desired
   - [ ] Verify all links work
   - [ ] Set up confirmation process

3. **Long-term:**
   - [ ] Gather feedback from recipients
   - [ ] Update template for next year
   - [ ] Create templates for other events
   - [ ] Build library of branded templates

---

## Support & Resources

### Documentation Files
- `SPRING_BALL_EMAIL_TEMPLATE.md` - Full details
- `SPRING_BALL_EMAIL_STYLE_GUIDE.md` - Design reference
- `spring-ball-template.html` - HTML code
- `spring-ball-template.txt` - Plain text fallback

### Email Template Files
- Location: `/email-templates/spring-ball-template.html`
- Location: `/email-templates/spring-ball-template.txt`

### Contact for Help
- For template questions: Review documentation
- For customization: Modify HTML/CSS as shown above
- For technical issues: Check WordPress admin email settings

---

## Version Information

- **Template Version:** 1.0
- **Created:** November 3, 2025
- **Event:** Spring Ball
- **Date:** March 5, 2026
- **Status:** Ready for production use

