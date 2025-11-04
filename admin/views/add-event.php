<?php
    $event = null;
    if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
        global $wpdb;
        $event = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sct_events WHERE id = %d",
            intval($_GET['id'])
        ));
    }
    $sct_settings = get_option('event_admin_settings');
?>
<div class="wrap">
    <h1><?php echo $event ? 'Edit Event' : 'Add New Event'; ?></h1>
    
    <form id="add-event-form" class="event-form" accept-charset="utf-8">
        <input type="hidden" name="action" value="save_event">
        <?php if ($event): ?>
            <input type="hidden" name="event_id" value="<?php echo $event->id; ?>">
        <?php endif; ?>
        <?php wp_nonce_field('save_event', 'event_admin_nonce'); ?>
        
        <table class="form-table">
            <tr>
                <th><label for="event_name">Event Name</label></th>
                <td>
                    <input type="text" 
                           id="event_name" 
                           name="event_name" 
                           class="regular-text"
                           value="<?php echo $event ? esc_attr($event->event_name) : ''; ?>"
                           required>
                </td>
            </tr>
            <tr>
                <th><label for="event_date">Event Date / Time</label></th>
                <td>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <!-- Start Date -->
                        <div>
                            <label style="display: block; margin-bottom: 8px;">
                                <strong>Start Date:</strong>
                                <input type="date" 
                                       id="event_date" 
                                       name="event_date"
                                       value="<?php echo $event ? esc_attr($event->event_date) : ''; ?>"
                                       required
                                       style="width: 100%;">
                            </label>
                        </div>
                        
                        <!-- End Date -->
                        <div>
                            <label style="display: block; margin-bottom: 8px;">
                                <strong>End Date (optional):</strong>
                                <input type="date" 
                                       id="event_end_date" 
                                       name="event_end_date"
                                       value="<?php echo $event ? esc_attr($event->event_end_date) : ''; ?>"
                                       style="width: 100%;">
                                <small style="display: block; margin-top: 4px; color: #666;">Multi-day events</small>
                            </label>
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 12px;">
                        <!-- Start Time -->
                        <div>
                            <label style="display: block; margin-bottom: 8px;">
                                <strong>Start Time (optional):</strong>
                                <input type="time" 
                                       id="event_time" 
                                       name="event_time"
                                       value="<?php echo $event ? esc_attr($event->event_time) : ''; ?>"
                                       style="width: 100%;">
                                <small style="display: block; margin-top: 4px; color: #666;">Leave blank for all-day</small>
                            </label>
                        </div>
                        
                        <!-- End Time -->
                        <div>
                            <label style="display: block; margin-bottom: 8px;">
                                <strong>End Time (optional):</strong>
                                <input type="time" 
                                       id="event_end_time" 
                                       name="event_end_time"
                                       value="<?php echo $event ? esc_attr($event->event_end_time) : ''; ?>"
                                       style="width: 100%;">
                                <small style="display: block; margin-top: 4px; color: #666;">Time range on same day</small>
                            </label>
                        </div>
                    </div>
                </td>
            </tr>
            <tr>
                <th><label for="location_name">Location Name</label></th>
                <td>
                    <input type="text" 
                           id="location_name" 
                           name="location_name" 
                           class="regular-text"
                           value="<?php echo $event ? esc_html(stripslashes($event->location_name)) : ''; ?>"
                           required>
                </td>
            </tr>
            <tr>
                <th><label for="location_link">Location Link</label></th>
                <td>
                    <input type="url" 
                           id="location_link" 
                           name="location_link" 
                           class="regular-text"
                           value="<?php echo $event ? esc_attr($event->location_link) : ''; ?>">
                </td>
            </tr>
            <tr>
                <th><label for="guest_capacity">Guest Capacity</label></th>
                <td>
                    <input type="number" 
                           id="guest_capacity" 
                           name="guest_capacity" 
                           min="0"
                           value="<?php echo $event ? esc_attr($event->guest_capacity) : 0; ?>"
                           required>
                        
                        <p class="description">Set to 0 for unlimited</p>
                </td>
            </tr>
            <tr>
                <th><label for="event_description">Description</label></th>
                <td>
                    <?php 
                    wp_editor(
                        $event ? htmlspecialchars_decode($event->description) : '',
                        'event_description',
                        array(
                            'textarea_name' => 'event_description',
                            'textarea_rows' => 10,
                            'media_buttons' => true,
                            'teeny' => false,
                            'quicktags' => true,
                            'tinymce' => array(
                                'toolbar1' => 'formatselect,bold,italic,bullist,numlist,blockquote,alignleft,aligncenter,alignright,link,unlink,wp_more,spellchecker,fullscreen,wp_adv',
                                'toolbar2' => 'strikethrough,hr,forecolor,pastetext,removeformat,charmap,outdent,indent,undo,redo,wp_help'
                            )
                        )
                    );
                    ?>
                </td>
            </tr>
            <tr>
                <th><label for="admin_email">Admin Email</label></th>
                <td>
                    <input type="email" 
                           id="admin_email" 
                           name="admin_email" 
                           class="regular-text"
                           value="<?php echo ($event && $event->admin_email) ? esc_attr($event->admin_email) : get_bloginfo('admin_email'); ?>"
                           required>
                </td>
            </tr>
            <tr>
                <th><label for="thumbnail_url">Event Thumbnail</label></th>
                <td>
                    <input type="hidden" id="thumbnail_url" name="thumbnail_url" value="<?php echo ($event && isset($event->thumbnail_url)) ? esc_attr($event->thumbnail_url) : ''; ?>">
                    <button type="button" class="button" id="upload-thumbnail-button">Upload/Select Image</button>
                    <div id="thumbnail-preview" style="margin-top: 10px;">
                        <?php if ($event && !empty($event->thumbnail_url)) : ?>
                            <img src="<?php echo esc_url($event->thumbnail_url); ?>" alt="" style="max-width: 100%; height: auto;">
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
        </table>

        <div id="accordion">
            <h3>Additional Options</h3>
            <div>
                <table class="form-table">
                    <tr>
                        <th><label for="pricing_options">Pricing Options</label></th>
                        <td>
                            <div id="pricing-options-container">
                                <?php if (!empty($event->pricing_options)) :
                                    $pricing_options = maybe_unserialize($event->pricing_options);
                                    foreach ($pricing_options as $option) : 
                                        // Reset index to ensure sequential numbering (0, 1, 2...)
                                        static $pricing_index = 0;
                                        $current_index = $pricing_index++;
                                    ?>
                                        <div class="pricing-option">
                                            <input type="text" name="pricing_options[<?php echo $current_index; ?>][name]" value="<?php echo esc_attr($option['name']); ?>" placeholder="Category Name" required>
                                            <input type="number" name="pricing_options[<?php echo $current_index; ?>][price]" value="<?php echo esc_attr($option['price']); ?>" placeholder="Price (0 for free)" step="0.01" min="0" required>
                                            <button type="button" class="remove-pricing-option button">Remove</button>
                                        </div>
                                    <?php endforeach;
                                    $pricing_index = 0; // Reset for next use
                                endif; ?>
                            </div>
                            <button type="button" id="add-pricing-option" class="button">Add Pricing Option</button>
                            <p class="description">Add pricing <small>Set the price to 0 for free</small></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="pricing_description">Attendees Description</label></th>
                        <td>
                            <textarea 
                                id="pricing_description"
                                name="pricing_description"
                                placeholder="Enter HTML-formatted description for attendees section (optional)"
                                rows="4" 
                                style="width: 100%;">
                                <?php echo isset($event->pricing_description) ? esc_textarea($event->pricing_description) : ''; ?>
                            </textarea>
                            <p class="description">HTML tags allowed: &lt;strong&gt;, &lt;em&gt;, &lt;br&gt;, &lt;p&gt;, &lt;ul&gt;, &lt;li&gt;, &lt;a&gt;</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="goods_services">Goods/Services Options</label></th>
                        <td>
                            <div id="goods-services-container">
                                <?php if (!empty($event->goods_services)) :
                                    $goods_services = maybe_unserialize($event->goods_services);
                                    foreach ($goods_services as $option) :
                                        // Reset index to ensure sequential numbering (0, 1, 2...)
                                        static $goods_index = 0;
                                        $current_index = $goods_index++;
                                    ?>
                                        <div class="goods-service-option">
                                            <input type="text" name="goods_services[<?php echo $current_index; ?>][name]" value="<?php echo esc_attr($option['name']); ?>" placeholder="Item Name" required>
                                            <input type="number" name="goods_services[<?php echo $current_index; ?>][price]" value="<?php echo esc_attr($option['price']); ?>" placeholder="Price (0 for free)" step="0.01" min="0" required>
                                            <input type="number" name="goods_services[<?php echo $current_index; ?>][limit]" value="<?php echo esc_attr($option['limit']); ?>" placeholder="Limit (0 for unlimited)" min="0">
                                            <button type="button" class="remove-goods-service-option button">Remove</button>
                                        </div>
                                    <?php endforeach;
                                    $goods_index = 0; // Reset for next use
                                endif; ?>
                            </div>
                            <button type="button" id="add-goods-service-option" class="button">Add Goods/Service Option</button>
                            <p class="description">Add goods or services with optional limits. Set the price to 0 for free. Set the limit to 0 for unlimited selection.</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="goods_services_description">Extras Description</label></th>
                        <td>
                            <textarea 
                                id="goods_services_description"
                                name="goods_services_description"
                                placeholder="Enter HTML-formatted description for extras section (optional)"
                                rows="4" 
                                style="width: 100%;">
                                <?php echo isset($event->goods_services_description) ? esc_textarea($event->goods_services_description) : ''; ?>
                            </textarea>
                            <p class="description">HTML tags allowed: &lt;strong&gt;, &lt;em&gt;, &lt;br&gt;, &lt;p&gt;, &lt;ul&gt;, &lt;li&gt;, &lt;a&gt;</p>
                        </td>
                    </tr>
                    <tr id="payment-methods-container" style="display: none;">
                        <th><label for="payment_methods">Payment Methods</label></th>
                        <td>
                            <div id="payment-methods-list">
                                <?php if (!empty($event->payment_methods)) :
                                    $payment_methods = maybe_unserialize($event->payment_methods);
                                    foreach ($payment_methods as $index => $method) : ?>
                                        <div class="payment-method">
                                            <select name="payment_methods[<?php echo $index; ?>][type]" required>
                                                <option value="online" <?php selected($method['type'], 'online'); ?>>Online</option>
                                                <option value="transfer" <?php selected($method['type'], 'transfer'); ?>>Transfer</option>
                                                <option value="cash" <?php selected($method['type'], 'cash'); ?>>Cash</option>
                                            </select>
                                            <input type="text" name="payment_methods[<?php echo $index; ?>][description]" value="<?php echo esc_attr($method['description']); ?>" placeholder="Description" required>
                                            <input type="url" name="payment_methods[<?php echo $index; ?>][link]" value="<?php echo esc_attr($method['link']); ?>" placeholder="Payment Link (for online)">
                                            <textarea name="payment_methods[<?php echo $index; ?>][transfer_details]" placeholder="Transfer Details (for transfer)" rows="5" required><?php echo esc_textarea($method['transfer_details']); ?></textarea>
                                            <button type="button" class="remove-payment-method button">Remove</button>
                                        </div>
                                    <?php endforeach;
                                endif; ?>
                            </div>
                            <button type="button" id="add-payment-method" class="button">Add Payment Method</button>
                            <p class="description">Add Payment Options for the event.</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="payment_methods_description">Payment Methods Description</label></th>
                        <td>
                            <textarea 
                                id="payment_methods_description"
                                name="payment_methods_description"
                                placeholder="Enter HTML-formatted description for payment methods section (optional)"
                                rows="4" 
                                style="width: 100%;">
                                <?php echo isset($event->payment_methods_description) ? esc_textarea($event->payment_methods_description) : ''; ?>
                            </textarea>
                            <p class="description">HTML tags allowed: &lt;strong&gt;, &lt;em&gt;, &lt;br&gt;, &lt;p&gt;, &lt;ul&gt;, &lt;li&gt;, &lt;a&gt;</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="publish_date">Publish Date and Time</label></th>
                        <td>
                            <input type="datetime-local" id="publish_date" name="publish_date" 
                                value="<?php echo ($event && $event->publish_date) ? esc_attr(date('Y-m-d\TH:i', strtotime($event->publish_date))) : ''; ?>">
                            <p class="description">Set the date and time when the event should be published.</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="unpublish_date">Unpublish Date and Time</label></th>
                        <td>
                            <input type="datetime-local" id="unpublish_date" name="unpublish_date" 
                                value="<?php echo ($event && $event->unpublish_date) ? esc_attr(date('Y-m-d\TH:i', strtotime($event->unpublish_date))) : ''; ?>">>
                            <p class="description">Set the date and time when the event should be unpublished.</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="max_guests_per_registration">Max Guests/Registration:</label></th>
                        <td>
                            <input type="number" 
                                   id="max_guests_per_registration" 
                                   name="max_guests_per_registration" 
                                   value="<?php echo ($event && isset($event->max_guests_per_registration)) ? esc_attr($event->max_guests_per_registration) : 0; ?>" 
                                   min="0" 
                                   required>
                            
                            <p class="description">Set to 0 for unlimited</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="member_only">Members Only</label></th>
                        <td>
                            <input type="checkbox" 
                                   id="member_only" 
                                   name="member_only" 
                                   value="1"
                                   <?php echo $event && $event->member_only ? 'checked' : ''; ?>>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="by_lottery">By Lottery</label></th>
                        <td>
                            <input type="checkbox" 
                                   id="by_lottery" 
                                   name="by_lottery" 
                                   value="1"
                                   <?php echo $event && $event->by_lottery ? 'checked' : ''; ?>>
                                <p class="description">Allow winners to be selected by lottery</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="has_waiting_list">Enable Waiting List</label></th>
                        <td>
                            <input type="checkbox" 
                                   id="has_waiting_list" 
                                   name="has_waiting_list" 
                                   value="1"
                                   <?php echo $event && $event->has_waiting_list ? 'checked' : ''; ?>>
                                <p class="description">Allow users to join waiting list when event is fully booked</p>
                        </td>
                    </tr>
                    <tr>
                        <th colspan="2" style="background-color: #f5f5f5; padding: 12px;">
                            <strong>Registration Form Fields</strong>
                        </th>
                    </tr>
                    <tr>
                        <th><label for="collect_phone">Collect Phone Number</label></th>
                        <td>
                            <input type="checkbox" 
                                   id="collect_phone" 
                                   name="collect_phone" 
                                   value="1"
                                   <?php echo ($event && !empty($event->collect_phone)) ? 'checked' : ''; ?>>
                                <p class="description">Ask guests for their telephone number during registration</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="collect_company">Collect Company Name</label></th>
                        <td>
                            <input type="checkbox" 
                                   id="collect_company" 
                                   name="collect_company" 
                                   value="1"
                                   <?php echo ($event && !empty($event->collect_company)) ? 'checked' : ''; ?>>
                                <p class="description">Ask guests for their company or organization name</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="collect_address">Collect Address</label></th>
                        <td>
                            <input type="checkbox" 
                                   id="collect_address" 
                                   name="collect_address" 
                                   value="1"
                                   <?php echo ($event && !empty($event->collect_address)) ? 'checked' : ''; ?>>
                                <p class="description">Ask guests for their full address (Street Address, City, Postal Code, Country)</p>
                        </td>
                    </tr>
                    <tr>
                        <th colspan="2" style="background-color: #f5f5f5; padding: 15px; border-top: 2px solid #ddd;">
                            <h3 style="margin: 0; font-size: 16px;">Optional Comment Fields</h3>
                            <p style="margin: 8px 0 0 0; color: #666; font-size: 13px;">Add custom optional fields for additional guest information</p>
                        </th>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <div id="comment-fields-container">
                                <!-- Comment fields will be added here dynamically -->
                            </div>
                            <button type="button" class="button button-secondary" id="add-comment-field-btn">
                                + Add Comment Field
                            </button>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="external_registration">External Registration</label></th>
                        <td>
                            <input type="checkbox" 
                                   id="external_registration" 
                                   name="external_registration" 
                                   value="1"
                                   <?php echo $event && isset($event->external_registration) && $event->external_registration ? 'checked' : ''; ?>>
                                <p class="description">Use external website for event registration</p>
                        </td>
                    </tr>
                    <tr id="external_url_row" style="<?php echo (!$event || !isset($event->external_registration) || !$event->external_registration) ? 'display: none;' : ''; ?>">
                        <th><label for="external_registration_url">External Registration URL</label></th>
                        <td>
                            <input type="url" 
                                   id="external_registration_url" 
                                   name="external_registration_url" 
                                   class="regular-text"
                                   value="<?php echo $event && isset($event->external_registration_url) ? esc_attr($event->external_registration_url) : ''; ?>"
                                   placeholder="https://example.com/register">
                                <p class="description">Enter the complete URL where participants can register for this event</p>
                        </td>
                    </tr>
                    <tr id="external_text_row" style="<?php echo (!$event || !isset($event->external_registration) || !$event->external_registration) ? 'display: none;' : ''; ?>">
                        <th><label for="external_registration_text">Button Text</label></th>
                        <td>
                            <input type="text" 
                                   id="external_registration_text" 
                                   name="external_registration_text" 
                                   class="regular-text"
                                   value="<?php echo $event && isset($event->external_registration_text) ? esc_attr($event->external_registration_text) : ''; ?>"
                                   placeholder="Register for Event">
                                <p class="description">Text to display on the external registration button (optional - defaults to "Register for Event")</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="custom_email_template">Custom Email Template</label></th>
                        <td>
                            <p class="description">Paste your HTML email template below. Use {{placeholder}} for dynamic content.</p>
                            <textarea 
                                id="custom_email_template" 
                                name="custom_email_template" 
                                rows="20" 
                                style="width:100%; font-family: 'Courier New', monospace; font-size: 12px;"
                            ><?php 
                                if ($event) {
                                    // Repair corrupted templates before displaying
                                    $template = EventPublic::repair_corrupted_template($event->custom_email_template);
                                    echo esc_textarea($template);
                                }
                            ?></textarea>
                            <p class="description" style="margin-top: 10px;">
                                <strong>Important:</strong> This template is saved as-is without any WordPress processing.
                                Ensure your HTML is valid and complete with &lt;style&gt; tags included.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <!-- Available Variables Postbox -->
                            <div class="postbox">
                                <div class="postbox-header">
                                    <h2 class="hndle">Available Variables</h2>
                                </div>
                                <div class="inside">
                                    <div class="placeholder-category">
                                        <h4>Registration Data</h4>
                                        <div class="placeholder-list">
                                            <code class="variable-code">{{registration.name}}</code>
                                            <code class="variable-code">{{registration.email}}</code>
                                            <code class="variable-code">{{registration.guest_count}}</code>
                                            <code class="variable-code">{{registration.id}}</code>
                                            <code class="variable-code">{{registration.manage_link}}</code>
                                        </div>
                                    </div>
                                    
                                    <div class="placeholder-category">
                                        <h4>Event Information</h4>
                                        <div class="placeholder-list">
                                            <code class="variable-code">{{event.name}}</code>
                                            <code class="variable-code">{{event.date}}</code>
                                            <code class="variable-code">{{event.time}}</code>
                                            <code class="variable-code">{{event.description}}</code>
                                            <code class="variable-code">{{event.location_name}}</code>
                                            <code class="variable-code">{{event.location_link}}</code>
                                        </div>
                                    </div>
                                    
                                    <div class="placeholder-category">
                                        <h4>Capacity & Payment</h4>
                                        <div class="placeholder-list">
                                            <code class="variable-code">{{capacity.total}}</code>
                                            <code class="variable-code">{{capacity.remaining}}</code>
                                            <code class="variable-code">{{payment.total}}</code>
                                            <code class="variable-code">{{payment.status}}</code>
                                        </div>
                                    </div>
                                    
                                    <div class="placeholder-category">
                                        <h4>Website Information</h4>
                                        <div class="placeholder-list">
                                            <code class="variable-code">{{website.name}}</code>
                                            <code class="variable-code">{{website.url}}</code>
                                            <code class="variable-code">{{date.current}}</code>
                                            <code class="variable-code">{{date.year}}</code>
                                        </div>
                                    </div>
                                    
                                    <p class="description">
                                        <em>Note: Old format like {name} still works for backward compatibility</em>
                                    </p>
                                </div>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        <!-- Additional Fields Help Section -->
        <div style="margin-top: 30px;">
            <?php include dirname(__FILE__) . '/partial-additional-fields-help.php'; ?>
        </div>
        
        <p class="submit">
            <input type="submit" 
                   class="button button-primary" 
                   value="<?php echo $event ? 'Update Event' : 'Add Event'; ?>">
            <a href="<?php echo admin_url('admin.php?page=event-admin'); ?>" 
               class="button">Cancel</a>
        </p>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const externalCheckbox = document.getElementById('external_registration');
    const externalUrlRow = document.getElementById('external_url_row');
    const externalTextRow = document.getElementById('external_text_row');
    const externalUrlInput = document.getElementById('external_registration_url');
    
    function toggleExternalFields() {
        if (externalCheckbox.checked) {
            externalUrlRow.style.display = 'table-row';
            externalTextRow.style.display = 'table-row';
            externalUrlInput.required = true;
        } else {
            externalUrlRow.style.display = 'none';
            externalTextRow.style.display = 'none';
            externalUrlInput.required = false;
            externalUrlInput.value = '';
            document.getElementById('external_registration_text').value = '';
        }
    }
    
    externalCheckbox.addEventListener('change', toggleExternalFields);
    toggleExternalFields(); // Run on page load

    // Comment Fields Management
    let commentFieldsData = [];
    let fieldCounter = 0;

    // Helper function to escape HTML
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }

    // Initialize comment fields from existing event data
    function initializeCommentFields() {
        const eventId = document.querySelector('input[name="event_id"]');
        if (eventId && eventId.value) {
            const nonce = document.querySelector('input[name="event_admin_nonce"]');
            fetch(ajaxurl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'get_event_comment_fields',
                    event_id: eventId.value,
                    nonce: nonce ? nonce.value : ''
                })
            })
            .then(r => r.json())
            .then(data => {
                console.log('Comment fields response:', data);
                // WordPress wp_send_json_success wraps data in a 'data' property
                const fields = data.data && data.data.fields ? data.data.fields : (data.fields || []);
                if (fields && Array.isArray(fields) && fields.length > 0) {
                    commentFieldsData = fields;
                    renderCommentFields();
                }
            })
            .catch(err => console.error('Error loading comment fields:', err));
        }
    }

    function renderCommentFields() {
        const container = document.getElementById('comment-fields-container');
        container.innerHTML = '';
        
        commentFieldsData.forEach((field, index) => {
            container.appendChild(createFieldElement(field, index));
        });
    }

    function createFieldElement(field, index) {
        const div = document.createElement('div');
        div.className = 'comment-field-item';
        div.style.cssText = 'border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; background: #fafafa; position: relative;';
        
        const escapedLabel = escapeHtml(field.label || '');
        const escapedPlaceholder = escapeHtml(field.placeholder || '');
        const selectedRows = field.rows || 3;
        const escapedOptions = escapeHtml((field.options && Array.isArray(field.options)) ? field.options.join('\n') : '');
        
        div.innerHTML = `
            <div style="position: absolute; top: 10px; right: 10px;">
                <button type="button" class="button button-small button-link-delete remove-field-btn" data-index="${index}">Remove</button>
            </div>
            <div class="uk-grid-small uk-grid" uk-grid>
                <div class="uk-width-1-3@m">
                    <label><strong>Field Label *</strong></label>
                    <input type="text" class="field-label" value="${escapedLabel}" placeholder="e.g., Seating Preferences" style="width: 100%; padding: 6px;">
                </div>
                <div class="uk-width-1-3@m">
                    <label><strong>Field Type *</strong></label>
                    <select class="field-type" style="width: 100%; padding: 6px;">
                        <option value="textarea" ${field.type === 'textarea' ? 'selected' : ''}>Textarea</option>
                        <option value="text" ${field.type === 'text' ? 'selected' : ''}>Text Input</option>
                        <option value="checkbox" ${field.type === 'checkbox' ? 'selected' : ''}>Checkbox</option>
                        <option value="select" ${field.type === 'select' ? 'selected' : ''}>Dropdown</option>
                    </select>
                </div>
                <div class="uk-width-1-3@m">
                    <label><strong>Required?</strong></label>
                    <label style="display: flex; align-items: center; margin-top: 6px;">
                        <input type="checkbox" class="field-required" ${field.required ? 'checked' : ''} style="margin-right: 8px;">
                        <span>Required field</span>
                    </label>
                </div>
            </div>
            <div style="margin-top: 12px;">
                <label><strong>Placeholder / Help Text</strong></label>
                <input type="text" class="field-placeholder" value="${escapedPlaceholder}" placeholder="Optional placeholder text" style="width: 100%; padding: 6px;">
            </div>
            <div style="margin-top: 12px;">
                <label><strong>Rows (for textarea)</strong></label>
                <input type="number" class="field-rows" value="${selectedRows}" min="1" max="10" style="width: 60px; padding: 6px;">
            </div>
            <div style="margin-top: 12px; display: ${field.type === 'select' ? 'block' : 'none'};" class="field-options-container">
                <label><strong>Dropdown Options (one per line)</strong></label>
                <textarea class="field-options" placeholder="Option 1&#10;Option 2&#10;Option 3" style="width: 100%; padding: 6px; height: 80px; font-family: monospace;">${escapedOptions}</textarea>
                <small style="color: #666;">Enter each option on a new line</small>
            </div>
        `;
        
        // Event listener for remove button
        div.querySelector('.remove-field-btn').addEventListener('click', (e) => {
            e.preventDefault();
            commentFieldsData.splice(index, 1);
            renderCommentFields();
        });

        // Toggle options field visibility based on type
        const fieldTypeSelect = div.querySelector('.field-type');
        const optionsContainer = div.querySelector('.field-options-container');
        fieldTypeSelect.addEventListener('change', () => {
            optionsContainer.style.display = fieldTypeSelect.value === 'select' ? 'block' : 'none';
        });

        // Update data when field changes
        const updateField = () => {
            const optionsText = div.querySelector('.field-options') ? div.querySelector('.field-options').value : '';
            const options = optionsText.trim() ? optionsText.split('\n').map(opt => opt.trim()).filter(opt => opt) : [];
            
            commentFieldsData[index] = {
                id: field.id || `field_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`,
                label: div.querySelector('.field-label').value,
                type: div.querySelector('.field-type').value,
                required: div.querySelector('.field-required').checked,
                placeholder: div.querySelector('.field-placeholder').value,
                rows: parseInt(div.querySelector('.field-rows').value) || 3,
                options: options.length > 0 ? options : undefined
            };
        };

        div.querySelectorAll('input, select, textarea').forEach(el => {
            el.addEventListener('change', updateField);
            el.addEventListener('input', updateField);
        });

        return div;
    }

    const addFieldBtn = document.getElementById('add-comment-field-btn');
    if (addFieldBtn) {
        addFieldBtn.addEventListener('click', (e) => {
            e.preventDefault();
            console.log('Add comment field clicked');
            commentFieldsData.push({
                id: `field_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`,
                label: '',
                type: 'textarea',
                required: false,
                placeholder: '',
                rows: 3
            });
            console.log('commentFieldsData updated:', commentFieldsData);
            renderCommentFields();
        });
    } else {
        console.warn('Add comment field button not found');
    }

    // Store comment fields data before form submission
    const eventForm = document.getElementById('add-event-form');
    if (eventForm) {
        eventForm.addEventListener('submit', () => {
            console.log('Form submitted with comment fields:', commentFieldsData);
            const existingInput = document.querySelector('input[name="comment_fields_json"]');
            if (existingInput) {
                existingInput.remove();
            }
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'comment_fields_json';
            input.value = JSON.stringify(commentFieldsData);
            eventForm.appendChild(input);
        });
    }

    // Initialize on page load
    console.log('Initializing comment fields...');
    initializeCommentFields();
});
</script>

<style>
.comment-field-item {
    transition: all 0.3s ease;
}
.comment-field-item:hover {
    background: #f0f0f0;
    border-color: #999;
}
</style>
