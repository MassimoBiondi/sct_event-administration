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
                    <input type="date" 
                           id="event_date" 
                           name="event_date"
                           value="<?php echo $event ? esc_attr($event->event_date) : ''; ?>"
                           required>
                    <input type="time" 
                           id="event_time" 
                           name="event_time"
                           value="<?php echo $event ? esc_attr($event->event_time) : ''; ?>"
                           required>
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
                           value="<?php echo $event->admin_email ? esc_attr($event->admin_email) : get_bloginfo('admin_email'); ?>"
                           required>
                </td>
            </tr>
            <tr>
                <th><label for="thumbnail_url"><?php _e('Event Thumbnail', 'sct-event-administration'); ?></label></th>
                <td>
                    <input type="hidden" id="thumbnail_url" name="thumbnail_url" value="<?php echo esc_attr($event->thumbnail_url ?? ''); ?>">
                    <button type="button" class="button" id="upload-thumbnail-button"><?php _e('Upload/Select Image', 'sct-event-administration'); ?></button>
                    <div id="thumbnail-preview" style="margin-top: 10px;">
                        <?php if (!empty($event->thumbnail_url)) : ?>
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
                        <th><label for="pricing_options"><?php _e('Pricing Options', 'sct-event-administration'); ?></label></th>
                        <td>
                            <div id="pricing-options-container">
                                <?php if (!empty($event->pricing_options)) :
                                    $pricing_options = maybe_unserialize($event->pricing_options);
                                    foreach ($pricing_options as $index => $option) : ?>
                                        <div class="pricing-option">
                                            <input type="text" name="pricing_options[<?php echo $index; ?>][name]" value="<?php echo esc_attr($option['name']); ?>" placeholder="Category Name" required>
                                            <input type="number" name="pricing_options[<?php echo $index; ?>][price]" value="<?php echo esc_attr($option['price']); ?>" placeholder="Price (0 for free)" step="0.01" min="0" required>
                                            <button type="button" class="remove-pricing-option button">Remove</button>
                                        </div>
                                    <?php endforeach;
                                endif; ?>
                            </div>
                            <button type="button" id="add-pricing-option" class="button">Add Pricing Option</button>
                            <p class="description">Add pricing <small>Set the price to 0 for free</small></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="goods_services"><?php _e('Goods/Services Options', 'sct-event-administration'); ?></label></th>
                        <td>
                            <div id="goods-services-container">
                                <?php if (!empty($event->goods_services)) :
                                    $goods_services = maybe_unserialize($event->goods_services);
                                    foreach ($goods_services as $index => $option) : ?>
                                        <div class="goods-service-option">
                                            <input type="text" name="goods_services[<?php echo $index; ?>][name]" value="<?php echo esc_attr($option['name']); ?>" placeholder="Item Name" required>
                                            <input type="number" name="goods_services[<?php echo $index; ?>][price]" value="<?php echo esc_attr($option['price']); ?>" placeholder="Price (0 for free)" step="0.01" min="0" required>
                                            <input type="number" name="goods_services[<?php echo $index; ?>][limit]" value="<?php echo esc_attr($option['limit']); ?>" placeholder="Limit (0 for unlimited)" min="0">
                                            <button type="button" class="remove-goods-service-option button">Remove</button>
                                        </div>
                                    <?php endforeach;
                                endif; ?>
                            </div>
                            <button type="button" id="add-goods-service-option" class="button">Add Goods/Service Option</button>
                            <p class="description">Add goods or services with optional limits. Set the price to 0 for free. Set the limit to 0 for unlimited selection.</p>
                        </td>
                    </tr>
                    <tr id="payment-methods-container" style="display: none;">
                        <th><label for="payment_methods"><?php _e('Payment Methods', 'sct-event-administration'); ?></label></th>
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
                        <th><label for="publish_date"><?php _e('Publish Date and Time', 'sct-event-administration'); ?></label></th>
                        <td>
                            <input type="datetime-local" id="publish_date" name="publish_date" 
                                value="<?php echo $event->publish_date ? esc_attr(date('Y-m-d\TH:i', strtotime($event->publish_date))) : NULL; ?>">
                            <p class="description">Set the date and time when the event should be published.</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="unpublish_date"><?php _e('Unpublish Date and Time', 'sct-event-administration'); ?></label></th>
                        <td>
                            <input type="datetime-local" id="unpublish_date" name="unpublish_date" 
                                value="<?php echo $event->unpublish_date ? esc_attr(date('Y-m-d\TH:i', strtotime($event->unpublish_date))) : NULL; ?>">
                            <p class="description">Set the date and time when the event should be unpublished.</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="max_guests_per_registration">Max Guests/Registration:</label></th>
                        <td>
                            <input type="number" 
                                   id="max_guests_per_registration" 
                                   name="max_guests_per_registration" 
                                   value="<?php echo esc_attr($event->max_guests_per_registration ?? 0); ?>" 
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
                        <th><label for="custom_email_template">Custom Email Template</label></th>
                        <td>
                            <?php 
                            wp_editor(
                                $event ? htmlspecialchars_decode($event->custom_email_template) : '',
                                'confirmation_template',
                                array(
                                    'textarea_name' => 'custom_email_template',
                                    'textarea_rows' => 15,
                                    'media_buttons' => true, // Allow media uploads
                                    'teeny' => false, // Use the full editor toolbar
                                    'quicktags' => true, // Enable quicktags for HTML editing
                                    'tinymce' => array(
                                        'toolbar1' => 'formatselect,bold,italic,bullist,numlist,blockquote,alignleft,aligncenter,alignright,link,unlink,wp_more,spellchecker,fullscreen,wp_adv',
                                        'toolbar2' => 'strikethrough,hr,forecolor,pastetext,removeformat,charmap,outdent,indent,undo,redo,wp_help',
                                        'content_css' => get_stylesheet_directory_uri() . '/editor-style.css', // Optional: Add custom editor styles
                                    )
                                )
                            );
                            ?>



                            <div class="form-field placeholder-info">
                                <p><strong>Available placeholders:</strong></p>
                                <p>
                                    <code>{name}</code> - Registrant's name<br>
                                    <code>{email}</code> - Registrant's email<br>
                                    <code>{guest_count}</code> - Number of guests<br>
                                    <code>{event_name}</code> - Event name<br>
                                    <code>{event_date}</code> - Event date<br>
                                    <code>{event_time}</code> - Event time<br>
                                    <code>{description}</code> - Event description<br>
                                    <code>{location_name}</code> - Event location name<br>
                                    <code>{location_url}</code> - Event location URL<br>
                                    <code>{location_link}</code> - Event location link<br>
                                    <code>{guest_capacity}</code> - Event guest capacity<br>
                                    <code>{member_only}</code> - Member-only event<br>
                                    <code>{total_price}</code> - Total price<br>
                                    <code>{remaining_capacity}</code> - Remaining capacity<br>
                                    <code>{payment_status}</code> - Payment Status<br>
                                    <code>{payment_type}</code> - Payment Type<br>
                                    <code>{payment_name}</code> - Payment Name<br>
                                    <code>{payment_link}</code> - Payment link<br>
                                    <code>{payment_description}</code> - Payment description<br>
                                    <code>{payment_method_details}</code> - Payment method details<br>
                                    <code>{pricing_overview}</code> - Pricing overview table<br>
                                    <code>{reservation_link}</code> - Reservation Management Link<br>
                                </p>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
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
