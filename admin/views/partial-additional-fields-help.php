<?php
/**
 * Additional Fields Display in Admin Settings/Templates
 * 
 * Shows all available additional information fields that can be used as placeholders
 * in email templates.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Include the fields manager
require_once dirname(dirname(__DIR__)) . '/includes/class-additional-fields.php';
?>

<div class="postbox">
    <div class="postbox-header">
        <h2 class="hndle">Additional Information Fields</h2>
    </div>
    <div class="inside">
        <p>
            <em>When editing email templates for a specific event, you can reference any additional information fields configured for that event using the following placeholder format:</em>
        </p>
        <p style="margin: 15px 0; padding: 10px; background: #f0f0f0; border-left: 4px solid #0073aa;">
            <code style="display: block; word-break: break-all;">{{additional.FIELD_ID}}</code>
        </p>
        
        <h3 style="margin-top: 20px;">Example:</h3>
        <p>If you have a field called "Seating Preferences" with ID "seating_prefs", you would use:</p>
        <p style="margin: 15px 0; padding: 10px; background: #f5f5f5; border-left: 4px solid #0073aa;">
            <code>{{additional.seating_prefs}}</code>
        </p>
        
        <h3 style="margin-top: 20px;">In Your Email Template:</h3>
        <div style="margin: 15px 0; padding: 15px; background: #f9f9f9; border: 1px solid #ddd;">
            <pre style="margin: 0; overflow-x: auto;">Dear {{attendee.name}},

Thank you for registering for {{event.title}}.

Event Date: {{event.date}}
Location: {{event.location_name}}

---

Your Additional Information:
{{additional.seating_prefs}}
{{additional.dietary_restrictions}}
{{additional.other_notes}}

Best regards,
{{website.name}}</pre>
        </div>
        
        <h3 style="margin-top: 20px;">How to Find Field IDs:</h3>
        <ol>
            <li>Go to <strong>Add/Edit Event</strong></li>
            <li>Scroll to the <strong>"Additional Information Fields"</strong> section</li>
            <li>When you add or edit a field, note the <strong>"Field ID"</strong> (auto-generated or custom)</li>
            <li>Use that ID in your email template placeholder</li>
        </ol>
        
        <h3 style="margin-top: 20px;">Field Display Options:</h3>
        <p>Additional fields are automatically formatted for email display:</p>
        <ul style="list-style-type: disc; margin-left: 20px;">
            <li><strong>Textarea Fields</strong>: Displayed with line breaks preserved</li>
            <li><strong>Text Fields</strong>: Displayed as plain text</li>
            <li><strong>Checkbox Fields</strong>: Displayed as "Yes" or "No"</li>
            <li><strong>Dropdown Fields</strong>: Displayed as the selected option</li>
        </ul>
        
        <p style="margin-top: 20px; color: #666; font-size: 13px;">
            <em>Note: Additional fields are empty or show "—" if no value was provided during registration.</em>
        </p>
    </div>
</div>
