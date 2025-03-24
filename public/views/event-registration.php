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
                        <?php echo esc_html($event->location_name); ?>
                    </a>
                <?php else: ?>
                    <?php echo esc_html($event->location_name); ?>
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
    ?>

    <?php if (!$can_register): ?>
        <div class="registration-closed">
            <p>Sorry, this event is for members only.</p>
        </div>
    <?php elseif ($event->guest_capacity == 0 || $remaining_capacity > 0): ?>
        <div class="registration-form-container">
            <form id="event-registration-form" class="registration-form">
                <input type="hidden" name="action" value="register_event">
                <input type="hidden" name="event_id" value="<?php echo esc_attr($event->id); ?>">
                <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('event_registration_nonce'); ?>">
                <input type="hidden" id="member_price" value="<?php echo esc_attr($member_price); ?>">
                <input type="hidden" id="non_member_price" value="<?php echo esc_attr($non_member_price); ?>">
                <input type="hidden" id="price_type" value="<?php echo esc_attr($price_type); ?>">

                <div class="form-field">
                    <label for="name">Name:</label>
                    <input type="text" id="name" name="name" required>
                </div>
                
                <div class="form-field">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <?php if ($price_type == 'both' || $price_type == 'member_only'): ?>
                <div class="form-field">
                    <label for="member_guests">Number of Members:</label>
                    <input type="number" id="member_guests" name="member_guests" min="<?php echo $min_val; ?>" value="<?php echo $min_val; ?>" required>
                    <?php if ($member_price > 0): ?>
                    <div class="member-price-info">
                        <span class="price-text">Price per member: <?php echo $sct_settings['currency_symbol'].esc_html(number_format($member_price, $sct_settings['currency_format'])); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <?php if ($price_type == 'both' || $price_type == 'non_member_only'): ?>
                <div class="form-field">
                    <label for="non_member_guests">Number of Guests:</label>
                    <input type="number" id="non_member_guests" name="non_member_guests" min="<?php echo $min_val; ?>" value="<?php echo $min_val; ?>" required>
                    <?php if ($non_member_price > 0): ?>
                    <div class="non-member-price-info">
                        <span class="price-text">Price per Guest: <?php echo $sct_settings['currency_symbol'].esc_html(number_format($non_member_price, $sct_settings['currency_format'])); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php if ($price_type == 'free' || ($price_type == 'non_member_only' && $non_member_price == 0)): ?>
                <div class="form-field">
                    <label for="non_member_guests">Number of Guests:</label>
                    <input type="number" id="non_member_guests" name="non_member_guests" min="<?php echo $min_val; ?>" value="<?php echo $min_val; ?>" required>
                </div>
                <?php endif; ?>

                <?php if ($event->children_counted_separately): ?>
                <div class="form-field">
                    <label for="children_guests">Number of Children:</label>
                    <input type="number" id="children_guests" name="children_guests" min="0" value="0" required>
                </div>
                <?php endif; ?>
                
                <?php if ($price_type != 'free' && !($price_type == 'member_only' && $member_price == 0)): ?>
                <div class="form-field">
                    <label class="total_price" for="total_price">Total Price:</label>
                    <input type="text" id="total_price" name="total_price" value="0.00" readonly>
                </div>
                <?php endif; ?>

                <div class="event-registration">
                    <div class="event-available">
                        <?php if ($event->guest_capacity > 0): ?>
                            <span class="event-available-text">Available spots: <?php echo esc_html($remaining_capacity); ?></span>
                        <?php endif;?>
                        <button type="submit" class="submit-button">Submit Registration</button>
                    </div>
                </div>
            </form>
        </div>
    <?php else: ?>
        <div class="registration-closed">
            <p>Sorry, this event is fully booked.</p>
        </div>
    <?php endif; ?>
</div>
