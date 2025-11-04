<div class="event-registration-page">
    <div class="event-details" style="text-align: center;">
        <h2><?php echo esc_html($event->event_name); ?></h2>
        <div class="event-info">
            <p class="event-date">
                <strong>Date:</strong> <?php echo date('F j, Y', strtotime($event->event_date)); ?>
            </p>
            <?php if ($event->event_time !== '00:00:00'): ?>
            <p class="event-time">
                <strong>Time:</strong> <?php echo date('g:i A', strtotime($event->event_time)); ?>
            </p>
            <?php endif; ?>
            <p class="event-location">
                <strong>Location:</strong> 
                <?php if ($event->location_link): ?>
                    <a href="<?php echo esc_url($event->location_link); ?>" target="_blank">
                        <?php echo esc_html(stripslashes($event->location_name)); ?>
                    </a>
                <?php else: ?>
                    <?php echo esc_html(stripslashes($event->location_name)); ?>
                <?php endif; ?>
            </p>
            <?php if ($event->guest_capacity > 0): ?>
                <p class="event-capacity">
                    <strong>Available Spots:</strong> <?php echo esc_html($remaining_capacity); ?>
                </p>
            <?php endif;?>
            <?php if ($event->member_only): ?>
                <p class="event-member-only">
                    <strong>Note:</strong> This event is for members only.
                </p>
            <?php endif;?>
            <div id="event_description"><?php echo wpautop($event->description); ?></div>
        </div>
    </div>
    <?php
        $sct_settings = get_option('event_admin_settings', []);
        if (!is_array($sct_settings)) {
            $sct_settings = [];
        }
        // Ensure required keys exist with defaults
        if (!isset($sct_settings['currency_symbol']) || empty($sct_settings['currency_symbol'])) {
            $sct_settings['currency_symbol'] = '$';
        }
        // IMPORTANT: Use isset() not empty() because 0 is a valid value for JPY (no decimals)
        if (!isset($sct_settings['currency_format'])) {
            $sct_settings['currency_format'] = 2; // Default 2 decimals
        }
        $is_unpublished = !empty($event->unpublish_date) && strtotime($event->unpublish_date) <= time();
        
        // Helper function to format prices with proper decimal places
        $format_price = function($price, $settings) {
            $symbol = isset($settings['currency_symbol']) ? $settings['currency_symbol'] : '$';
            $decimals = intval($settings['currency_format'] ?? 2);
            // Use comma for thousands separator and period for decimal
            $formatted_price = number_format(floatval($price), $decimals, '.', ',');
            return $symbol . $formatted_price;
        };
    ?>

    <?php if ($is_unpublished): ?>
        <div class="registration-closed">
            <p>Registration for this event is closed.</p>
        </div>
    <?php elseif (isset($event->external_registration) && $event->external_registration): ?>
        <div class="external-registration">
            <div class="external-registration-notice">
                <p><strong>External Registration Required</strong></p>
                <p>This event uses external registration. Please click the button below to register on the event's official website.</p>
            </div>
            <div class="external-registration-button">
                <a href="<?php echo esc_url($event->external_registration_url); ?>" 
                   target="_blank" 
                   rel="noopener noreferrer"
                   class="uk-button uk-button-primary uk-button-large external-registration-link">
                    <?php echo esc_html(!empty($event->external_registration_text) ? $event->external_registration_text : 'Register for Event'); ?> <span class="external-link-icon" style="margin-left: 5px;">↗</span>
                </a>
            </div>
        </div>
    <?php elseif ($event->guest_capacity == 0 || $remaining_capacity > 0): ?>
        <div class="registration-form-container">
            <?php
                if(!empty($event->member_only))
                {
                    echo '<div class="uk-alert-warning uk-alert event-member-only uk-margin-remove uk-text-center" uk-alert>
                            <h3>This event is for members only.</h3>
                          </div>';
                }
            ?>
            <form id="event-registration-form" class="registration-form uk-form-horizontal">
                <input type="hidden" name="action" value="register_event">
                <input type="hidden" name="event_id" value="<?php echo esc_attr($event->id); ?>">
                <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('event_registration_nonce'); ?>">

                <div class="uk-margin">
                    <label for="name">Name:</label>
                    <div class="uk-form-controls">
                        <input type="text" id="name" name="name" required>
                    </div>
                </div>

                <div class="uk-margin">
                    <label for="email">Email:</label>
                    <div class="uk-form-controls">
                        <input type="email" id="email" name="email" required>
                    </div>
                </div>

                <?php if (!empty($event->collect_phone)): ?>
                <div class="uk-margin">
                    <label for="phone">Phone:</label>
                    <div class="uk-form-controls">
                        <input type="tel" id="phone" name="phone" placeholder="+1 (555) 123-4567">
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($event->collect_company)): ?>
                <div class="uk-margin">
                    <label for="company_name">Company Name:</label>
                    <div class="uk-form-controls">
                        <input type="text" id="company_name" name="company_name" placeholder="Your company or organization">
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($event->collect_address)): ?>
                <div class="uk-margin">
                    <label for="address">Address:</label>
                    <div class="uk-form-controls">
                        <textarea id="address" name="address" placeholder="Street address, Building, Apartment, etc." rows="3"></textarea>
                    </div>
                </div>

                <div class="uk-grid-small" uk-grid>
                    <div class="uk-width-1-2@m">
                        <div class="uk-margin">
                            <label for="city">City:</label>
                            <div class="uk-form-controls">
                                <input type="text" id="city" name="city" placeholder="City">
                            </div>
                        </div>
                    </div>
                    <div class="uk-width-1-2@m">
                        <div class="uk-margin">
                            <label for="postal_code" style="text-align: right;">Postal Code:</label>
                            <div class="uk-form-controls">
                                <input type="text" id="postal_code" name="postal_code" placeholder="Postal code">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="uk-margin">
                    <label for="country">Country:</label>
                    <div class="uk-form-controls">
                        <input type="text" id="country" name="country" placeholder="Country">
                    </div>
                </div>
                <?php endif; ?>

                <div id="pricing-options-container">
                    <h4 class="uk-heading-divider">Attendees</h4>
                    
                    <!-- Display pricing description if available and not blank -->
                    <?php if (!empty($event->pricing_description) && !empty(trim($event->pricing_description))) : ?>
                        <div class="pricing-description uk-margin-bottom" style="background: #f8f9fa; padding: 15px; border-radius: 4px; margin-bottom: 20px; border-left: 4px solid var(--red-color, #b71c1c);">
                            <?php echo wp_kses_post($event->pricing_description); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($event->pricing_options)) :
                        $pricing_options = maybe_unserialize($event->pricing_options);
                        if (is_array($pricing_options) && !empty($pricing_options)) :
                            $pricing_index = 0;
                            foreach ($pricing_options as $option) : 
                                // Ensure $option is an array with required keys
                                if (!is_array($option)) {
                                    continue;
                                }
                                $option_name = isset($option['name']) ? $option['name'] : '';
                                $option_price = isset($option['price']) ? $option['price'] : 0;
                            ?>
                            <div class="pricing-option uk-margin">
                                <label for="guest_count_<?php echo esc_attr($pricing_index); ?>">
                                    <?php echo esc_html($option_name); ?> (<?php echo esc_html($format_price($option_price, $sct_settings)); ?>):
                                </label>
                                <div class="uk-form-controls">
                                    <input type="number" 
                                        id="guest_count_<?php echo esc_attr($pricing_index); ?>" 
                                        name="guest_details[<?php echo esc_attr($pricing_index); ?>][count]" 
                                        class="small-text guest-count" 
                                        min="0" 
                                        placeholder="0">
                                    <input type="hidden" 
                                        name="guest_details[<?php echo esc_attr($pricing_index); ?>][name]" 
                                        value="<?php echo esc_attr($option_name); ?>">
                                    <input type="hidden" 
                                        name="guest_details[<?php echo esc_attr($pricing_index); ?>][price]" 
                                        value="<?php echo esc_attr($option_price); ?>">
                                </div>
                            </div>
                            <?php $pricing_index++; ?>
                        <?php endforeach;
                        endif; ?>
                    <?php else : ?>
                        <div class="uk-margin">
                            <label for="guest_count">Number of Guests:</label>
                            <div class="uk-form-controls">
                                <input type="number" id="guest_count" name="guest_count" class="small-text" min="0" placeholder="0">
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="error">
                        <p class="error-message">Please select at least one attendee option.</p>
                    </div>
                </div>

                <?php
                // Always show goods_services if available, regardless of pricing_options
                if (!empty($event->goods_services)) :
                    $goods_services = maybe_unserialize($event->goods_services);
                    if (is_array($goods_services) && !empty($goods_services)) :
                        echo  '<h4 class="uk-heading-divider">Extras</h4>';
                        
                        // Display goods/services description if available and not blank
                        if (!empty($event->goods_services_description) && !empty(trim($event->goods_services_description))) : ?>
                            <div class="goods-services-description uk-margin-bottom" style="background: #f8f9fa; padding: 15px; border-radius: 4px; margin-bottom: 20px; border-left: 4px solid var(--red-color, #b71c1c);">
                                <?php echo wp_kses_post($event->goods_services_description); ?>
                            </div>
                        <?php endif;
                        
                        $goods_index = 0;
                        foreach ($goods_services as $option) : 
                            // Ensure $option is an array with required keys
                            if (!is_array($option)) {
                                $goods_index++;
                                continue;
                            }
                            $option_name = isset($option['name']) ? $option['name'] : '';
                            $option_price = isset($option['price']) ? $option['price'] : 0;
                            $option_limit = isset($option['limit']) ? intval($option['limit']) : 0;
                        ?>
                        <div class="goods-service-option uk-margin">
                            <label for="goods_service_<?php echo esc_attr($goods_index); ?>">
                                <?php echo esc_html($option_name); ?> (<?php echo esc_html($format_price($option_price, $sct_settings)); ?>):
                            </label>
                            <div class="uk-form-controls">
                                <?php if ($option_limit == 1) : ?>
                                    <input type="checkbox" 
                                           class="uk-checkbox" 
                                           id="goods_service_<?php echo esc_attr($goods_index); ?>" 
                                           name="goods_services[<?php echo esc_attr($goods_index); ?>][count]"
                                           value="1">
                                <?php else : ?>
                                    <input type="number" 
                                           id="goods_service_<?php echo esc_attr($goods_index); ?>" 
                                           name="goods_services[<?php echo esc_attr($goods_index); ?>][count]" 
                                           class="small-text" 
                                           min="0" 
                                           max="<?php echo esc_attr($option_limit > 0 ? $option_limit : ''); ?>" 
                                           placeholder="0">
                                <?php endif; ?>
                                <input type="hidden" name="goods_services[<?php echo esc_attr($goods_index); ?>][name]" value="<?php echo esc_attr($option_name); ?>">
                                <input type="hidden" name="goods_services[<?php echo esc_attr($goods_index); ?>][price]" value="<?php echo esc_attr($option_price); ?>">
                            </div>
                        </div>
                        <?php $goods_index++; ?>
                    <?php endforeach;
                    endif;
                endif; ?>

                <div class="uk-margin" id="pricing-overview">
                    <h4 class="uk-heading-divider"></h4>
                    <table class="uk-table ">
                        <thead>
                            <tr>
                                <th style="width: 40%"></th>
                                <th style="text-align: center;">Count</th>
                                <th style="text-align: right;">Price</th>
                                <th style="text-align: right;">Total</th>
                            </tr>
                        </thead>
                        <tbody id="pricing-overview-body">
                            <!-- Dynamic rows will be added here -->
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3"><strong>Total Price</strong></td>
                                <td id="total-price" 
                                    style="text-align: right;" 
                                    data-currency-symbol="<?php echo esc_attr(isset($sct_settings['currency_symbol']) ? $sct_settings['currency_symbol'] : ''); ?>" 
                                    data-currency-format="<?php echo esc_attr(isset($sct_settings['currency_format']) ? $sct_settings['currency_format'] : ''); ?>">
                                    <?php echo esc_html(isset($sct_settings['currency_symbol']) ? $sct_settings['currency_symbol'] : ''); ?>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <?php if (!empty($event->payment_methods)) :
                    $payment_methods = maybe_unserialize($event->payment_methods);
                    if (!empty($payment_methods)) : ?>
                        <h4 class="uk-heading-divider">Payment Method</h4>
                        
                        <!-- Display payment methods description if available and not blank -->
                        <?php if (!empty($event->payment_methods_description) && !empty(trim($event->payment_methods_description))) : ?>
                            <div class="payment-methods-description uk-margin-bottom" style="background: #f8f9fa; padding: 15px; border-radius: 4px; margin-bottom: 20px; border-left: 4px solid var(--red-color, #b71c1c);">
                                <?php echo wp_kses_post($event->payment_methods_description); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php foreach ($payment_methods as $index => $method) : ?>
                            <div class="uk-margin">
                                <!-- Only show radio button if multiple payment methods -->
                                <?php if (count($payment_methods) > 1) : ?>
                                    <label class="uk-form-label">
                                        <?php echo esc_html($method['description']); ?>
                                    </label>
                                    <div class="uk-form-controls">
                                        <input type="radio"
                                               id="payment_method_<?php echo $index; ?>"
                                               name="payment_method"
                                               value="<?php echo esc_attr($method['type']); ?>"
                                               required
                                               <?php if ($index === 0) echo 'checked'; ?>>
                                        <label for="payment_method_<?php echo $index; ?>" style="display: inline; margin-left: 8px;">
                                            Select this option
                                        </label>
                                    </div>
                                <?php else : ?>
                                    <!-- Single payment method - hidden radio, auto-selected -->
                                    <label class="uk-form-label">
                                        <?php echo esc_html($method['description']); ?>
                                    </label>
                                    <input type="radio"
                                           id="payment_method_<?php echo $index; ?>"
                                           name="payment_method"
                                           value="<?php echo esc_attr($method['type']); ?>"
                                           required
                                           checked
                                           style="display: none;">
                                <?php endif; ?>

                                <!-- Show transfer details as paragraph -->
                                <?php if (!empty($method['transfer_details'])) : ?>
                                    <div class="uk-form-controls">
                                        <p><?php echo nl2br(esc_html($method['transfer_details'])); ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php endif; ?>

                <!-- Dynamic Comment Fields - Moved to End -->
                <?php if (!empty($event->comment_fields)):
                    $comment_fields = json_decode($event->comment_fields, true);
                    if (!empty($comment_fields)): ?>
                        <div id="comment-fields-section" class="uk-margin" style="border-top: 2px solid #e5e5e5; padding-top: 20px; margin-top: 20px;">
                            <h4 class="uk-heading-divider">Additional Information</h4>
                            <?php foreach ($comment_fields as $field): ?>
                                <?php if ($field['type'] === 'textarea'): ?>
                                    <div class="uk-margin">
                                        <label for="<?php echo esc_attr($field['id']); ?>">
                                            <?php echo esc_html($field['label']); ?>
                                            <?php if ($field['required']): ?>
                                                <span class="required" style="color: #e74c3c;">*</span>
                                            <?php endif; ?>
                                        </label>
                                        <div class="uk-form-controls">
                                            <textarea id="<?php echo esc_attr($field['id']); ?>" 
                                                      name="comments[<?php echo esc_attr($field['id']); ?>]"
                                                      rows="<?php echo esc_attr($field['rows'] ?? 3); ?>"
                                                      placeholder="<?php echo esc_attr($field['placeholder'] ?? ''); ?>"
                                                      <?php echo $field['required'] ? 'required' : ''; ?>></textarea>
                                        </div>
                                    </div>
                                <?php elseif ($field['type'] === 'text'): ?>
                                    <div class="uk-margin">
                                        <label for="<?php echo esc_attr($field['id']); ?>">
                                            <?php echo esc_html($field['label']); ?>
                                            <?php if ($field['required']): ?>
                                                <span class="required" style="color: #e74c3c;">*</span>
                                            <?php endif; ?>
                                        </label>
                                        <div class="uk-form-controls">
                                            <input type="text" 
                                                   id="<?php echo esc_attr($field['id']); ?>"
                                                   name="comments[<?php echo esc_attr($field['id']); ?>]"
                                                   placeholder="<?php echo esc_attr($field['placeholder'] ?? ''); ?>"
                                                   <?php echo $field['required'] ? 'required' : ''; ?>>
                                        </div>
                                    </div>
                                <?php elseif ($field['type'] === 'checkbox'): ?>
                                    <div class="uk-margin">
                                        <label class="uk-form-label">
                                            <?php echo esc_html($field['label']); ?>
                                            <?php if ($field['required']): ?>
                                                <span class="required" style="color: #e74c3c;">*</span>
                                            <?php endif; ?>
                                        </label>
                                        <div class="uk-form-controls">
                                            <input type="checkbox" 
                                                   id="<?php echo esc_attr($field['id']); ?>"
                                                   name="comments[<?php echo esc_attr($field['id']); ?>]"
                                                   value="1"
                                                   <?php echo $field['required'] ? 'required' : ''; ?>>
                                            <span style="display: inline; margin-left: 8px;">
                                                <?php echo wp_kses_post($field['placeholder'] ?? 'Check if applicable'); ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php elseif ($field['type'] === 'select'): ?>
                                    <div class="uk-margin">
                                        <label for="<?php echo esc_attr($field['id']); ?>" class="uk-form-label">
                                            <?php echo esc_html($field['label']); ?>
                                            <?php if ($field['required']): ?>
                                                <span class="required" style="color: #e74c3c;">*</span>
                                            <?php endif; ?>
                                        </label>
                                        <div class="uk-form-controls">
                                            <select id="<?php echo esc_attr($field['id']); ?>"
                                                    name="comments[<?php echo esc_attr($field['id']); ?>]"
                                                    <?php echo $field['required'] ? 'required' : ''; ?>>
                                                <option value="">Select an option...</option>
                                                <?php if (!empty($field['options']) && is_array($field['options'])): ?>
                                                    <?php foreach ($field['options'] as $option): ?>
                                                        <option value="<?php echo esc_attr($option); ?>">
                                                            <?php echo esc_html($option); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                        <?php if (!empty($field['placeholder'])): ?>
                                            <p class="description"><?php echo esc_html($field['placeholder']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <div class="uk-margin">
                    <button type="submit" class="submit-button">Submit Registration</button>
                </div>
            </form>
        </div>
    <?php elseif ($event->has_waiting_list): ?>
        <div class="event-waiting-list">
            <h3>This event is fully booked</h3>
            <p>However, you can join the waiting list to be notified if a spot opens up.</p>
            <div id="waiting-list-message-<?php echo $event->id; ?>" style="display:none;"></div>
            <form class="waiting-list-form uk-form-stacked" data-event-id="<?php echo esc_attr($event->id); ?>">
                <div class="uk-margin">
                    <label class="uk-form-label" for="waiting_list_name_<?php echo $event->id; ?>">Name:</label>
                    <input type="text" id="waiting_list_name_<?php echo $event->id; ?>" name="waiting_list_name" class="uk-input" placeholder="Your name" required />
                </div>
                <div class="uk-margin">
                    <label class="uk-form-label" for="waiting_list_email_<?php echo $event->id; ?>">Email:</label>
                    <input type="email" id="waiting_list_email_<?php echo $event->id; ?>" name="waiting_list_email" class="uk-input" placeholder="Your email" required />
                </div>
                <div class="uk-margin">
                    <label class="uk-form-label" for="waiting_list_people_<?php echo $event->id; ?>">Number of People:</label>
                    <input type="number" id="waiting_list_people_<?php echo $event->id; ?>" name="waiting_list_people" class="uk-input" min="1" value="1" required />
                </div>
                <div class="uk-margin">
                    <!-- <label class="uk-form-label" for="waiting_list_comment_<?php echo $event->id; ?>">Comment (optional):</label> -->
                    <textarea id="waiting_list_comment_<?php echo $event->id; ?>" name="waiting_list_comment" class="uk-textarea" placeholder="Optional comment" rows="3"></textarea>
                </div>
                <div class="uk-margin">
                    <button type="submit" class="uk-button uk-button-primary uk-width-1-1">Join Waiting List</button>
                </div>
            </form>
        </div>
    <?php else: ?>
        <div class="registration-closed">
            <p>Sorry, this event is fully booked.</p>
        </div>
    <?php endif; ?>
</div>
