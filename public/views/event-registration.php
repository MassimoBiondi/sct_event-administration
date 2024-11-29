<div class="event-registration-page">
    <div class="event-details">
        <h2><?php echo esc_html($event->event_name); ?></h2>
        <!-- <div><?php echo wpautop(wp_kses_post($event->description)); ?></div> -->
        <div class="event-info">
            <p class="event-date">
                <strong>Date:</strong> <?php echo date('F j, Y', strtotime($event->event_date)); ?>
            </p>
            <p class="event-time">
                <strong>Time:</strong> <?php echo date('g:i A', strtotime($event->event_time)); ?>
            </p>
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
        </div>
    </div>
    <?php if ($event->guest_capacity == 0 || $remaining_capacity > 0): ?>
        <div class="registration-form-container">
            <!-- <h3>Register for this Event</h3> -->
            <form id="event-registration-form" class="registration-form">
                <input type="hidden" name="action" value="register_event">
                <input type="hidden" name="event_id" value="<?php echo esc_attr($event->id); ?>">
                <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('event_registration_nonce'); ?>">
                
                <div class="form-field">
                    <label for="name">Name:</label>
                    <input type="text" id="name" name="name" required>
                </div>
                
                <div class="form-field">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-field">
                    <label for="guests">Number of Guests:</label>
                    <input type="number" id="guests" name="guests" min="1" 
                           max="<?php 
                                    if ($event->guest_capacity == 0 && $event->max_guests_per_registration == 0) {
                                        echo 999; // or any large number you want to use for unlimited
                                    } elseif ($event->guest_capacity == 0) {
                                        echo $event->max_guests_per_registration;
                                    } elseif ($event->max_guests_per_registration == 0) {
                                        echo $remaining_capacity;
                                    } else {
                                        echo min($remaining_capacity, $event->max_guests_per_registration);
                                    }
                                ?>" required>
                    <?php if ($event->guest_capacity > 0): ?>
                        <small>Maximum registrations: <?php echo esc_html($event->max_guests_per_registration); ?></small>
                    <?php endif;?>
                </div>
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
