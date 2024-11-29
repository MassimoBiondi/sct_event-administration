<div class="event-list">
    <?php if (!empty($events)): ?>
        <?php foreach ($events as $event): 
            $registered_guests = $wpdb->get_var($wpdb->prepare(
                "SELECT SUM(guest_count) FROM {$wpdb->prefix}sct_event_registrations WHERE event_id = %d",
                $event->id
            ));
            $registered_guests = $registered_guests ?: 0;
            $remaining_capacity = $event->guest_capacity - $registered_guests;

            // Generate the specific registration URL for this event
            $event_registration_url = add_query_arg('id', $event->id, $registration_url);
            
            // Generate the specific registration URL for this event
        ?>
            <div class="event-item">
                <h1><?php echo esc_html($event->event_name); ?></h1>
                <div>
                    <h3>
                        <?php echo date('F j, Y', strtotime($event->event_date)); ?>
                        &nbsp;
                        <?php echo date('g:i A', strtotime($event->event_time)); ?>
                    </h3>
                    <h4>
                        <?php if ($event->location_link): ?>
                            <a href="<?php echo esc_url($event->location_link); ?>" target="_blank">
                                <?php echo esc_html($event->location_name); ?>
                            </a>
                        <?php else: ?>
                            <?php echo esc_html($event->location_name); ?>
                        <?php endif; ?>
                    </h4>
                </div>
                <div class="event-description">
                    <p>
                        <?php echo wpautop(wp_kses_post($event->description)); ?>
                    </p>
                </div>
                <div class="event-registration">
                    <?php if ($event->guest_capacity == 0 || $remaining_capacity > 0): ?>
                        <div class="event-available">
                            <?php if ($event->guest_capacity > 0): ?>
                                <span class="event-available-text">Available Spots: <?php echo esc_html($remaining_capacity); ?></span>
                            <?php endif;?>
                            <!-- <span class="event-available-text">Available Spots: <?php echo esc_html($remaining_capacity); ?></span> -->
                            <a href="<?php echo esc_url($event_registration_url); ?>" class="register-button">
                                Register Now
                            </a>
                        </div>
                    <?php else: ?>
                        <span class="fully-booked">Fully Booked</span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No upcoming events available.</p>
    <?php endif; ?>
</div>
