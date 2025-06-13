<div class="event-registration-page">
    <div class="event-details">
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
        $member_price = $event->member_price;
        $non_member_price = $event->non_member_price;

        // Determine the price logic
        if ($member_price > 0 && $non_member_price == 0) {
            $event->member_only = 1; // Automatically set the event to member-only
        }

        if ($event->member_only) {
            $can_register = true;
            $price_type = 'member_only';
        } elseif ($member_price > 0 && $non_member_price > 0) {
            $can_register = true;
            $price_type = 'both';
        } elseif ($member_price == 0 && $non_member_price > 0) {
            $can_register = true;
            $price_type = 'non_member_only';
        } else {
            $can_register = true;
            $price_type = 'free';
        }
        $min_val = ($price_type == 'both') ? 0 : 1;

        // Check if the event is unpublished
        $is_unpublished = !empty($event->unpublish_date) && strtotime($event->unpublish_date) <= time();
    ?>

    <?php if ($is_unpublished): ?>
        <div class="registration-closed">
            <p>Registration for this event is closed.</p>
        </div>
    <?php elseif (!$can_register): ?>
        <div class="registration-closed">
            <p>Sorry, this event is for members only.</p>
        </div>
    <?php elseif ($event->guest_capacity == 0 || $remaining_capacity > 0): ?>
        <div class="registration-form-container">
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

                <div id="pricing-options-container">
                    <h4 class="uk-heading-divider">Attendees</h4>
                    <?php if (!empty($event->pricing_options)) :
                        $pricing_options = maybe_unserialize($event->pricing_options);
                        foreach ($pricing_options as $index => $option) : ?>
                            <div class="pricing-option uk-margin">
                                <label for="guest_count_<?php echo esc_attr($index); ?>">
                                    <?php echo esc_html($option['name']); ?> (<?php echo esc_html($sct_settings['currency_symbol'] . $option['price']); ?>):
                                </label>
                                <div class="uk-form-controls">
                                    <input type="number" 
                                        id="guest_count_<?php echo esc_attr($index); ?>" 
                                        name="guest_details[<?php echo esc_attr($index); ?>][count]" 
                                        class="small-text guest-count" 
                                        min="0" 
                                        placeholder="0">
                                    <input type="hidden" 
                                        name="guest_details[<?php echo esc_attr($index); ?>][name]" 
                                        value="<?php echo esc_attr($option['name']); ?>">
                                    <input type="hidden" 
                                        name="guest_details[<?php echo esc_attr($index); ?>][price]" 
                                        value="<?php echo esc_attr($option['price']); ?>">
                                </div>
                            </div>
                        <?php endforeach; ?>
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
                    echo  '<h4 class="uk-heading-divider">Extras</h4>';
                    foreach ($goods_services as $index => $option) : ?>
                        <div class="goods-service-option uk-margin">
                            <label for="goods_service_<?php echo esc_attr($index); ?>">
                                <?php echo esc_html($option['name']); ?> (<?php echo esc_html($sct_settings['currency_symbol'] . $option['price']); ?>):
                            </label>
                            <div class="uk-form-controls">
                                <?php if ($option['limit'] == 1) : ?>
                                    <input type="checkbox" 
                                           class="uk-checkbox" 
                                           id="goods_service_<?php echo esc_attr($index); ?>" 
                                           name="goods_services[<?php echo esc_attr($index); ?>][count]">
                                <?php else : ?>
                                    <input type="number" 
                                           id="goods_service_<?php echo esc_attr($index); ?>" 
                                           name="goods_services[<?php echo esc_attr($index); ?>][count]" 
                                           class="small-text" 
                                           min="0" 
                                           max="<?php echo esc_attr($option['limit'] > 0 ? $option['limit'] : ''); ?>" 
                                           placeholder="0">
                                <?php endif; ?>
                                <input type="hidden" name="goods_services[<?php echo esc_attr($index); ?>][name]" value="<?php echo esc_attr($option['name']); ?>">
                                <input type="hidden" name="goods_services[<?php echo esc_attr($index); ?>][price]" value="<?php echo esc_attr($option['price']); ?>">
                            </div>
                        </div>
                    <?php endforeach;
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
                                    data-currency-symbol="<?php echo esc_attr($sct_settings['currency_symbol']); ?>" 
                                    data-currency-format="<?php echo esc_attr($sct_settings['currency_format']); ?>">
                                    <?php echo esc_html($sct_settings['currency_symbol']); ?>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <?php if (!empty($event->pricing_options) || !empty($event->goods_services)) : ?>
                    <h4 class="uk-heading-divider">Payment Method</h4>
                    <?php if (!empty($event->payment_methods)) :
                        $payment_methods = maybe_unserialize($event->payment_methods);
                        foreach ($payment_methods as $index => $method) : ?>
                            <div class="payment-method-option uk-margin">
                                <input type="radio"
                                       id="payment_method_<?php echo $index; ?>"
                                       name="payment_method"
                                       value="<?php echo esc_attr($method['type']); ?>"
                                       required
                                       <?php if (count($payment_methods) === 1) echo 'checked'; ?>>
                                <label for="payment_method_<?php echo $index; ?>">
                                    <?php echo esc_html($method['description']); ?>
                                    <?php if (!empty($method['transfer_details'])) : ?>
                                        <span uk-icon="info" uk-tooltip="<?php echo nl2br(esc_html($method['transfer_details'])); ?>"></span>
                                    <?php else : ?>
                                        <span uk-icon="info" uk-tooltip="Instruction will be included in the Email"></span>
                                    <?php endif; ?>
                                </label>
                            </div>
                        <?php endforeach;
                    endif; ?>
                <?php endif; ?>

                <div class="uk-margin">
                    <button type="submit" class="submit-button">Submit Registration</button>
                </div>
            </form>
        </div>
    <?php else: ?>
        <div class="registration-closed">
            <p>Sorry, this event is fully booked.</p>
        </div>
    <?php endif; ?>
</div>
