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

            // Check if the event is unpublished
            $is_unpublished = !empty($event->unpublish_date) && strtotime($event->unpublish_date) <= time();
            
            // Check if event is fully booked and has waiting list
            $is_fully_booked = ($event->guest_capacity > 0 && $remaining_capacity <= 0);
            $has_waiting_list = !empty($event->has_waiting_list);
        ?>
            <div class="event-item">
                <h1><?php echo esc_html($event->event_name); ?></h1>
                <div>
                    <h3>
                        <?php echo wp_kses_post(EventPublic::format_event_date_range($event)); ?>
                    </h3>
                    <h4>
                        <?php if ($event->location_link): ?>
                            <a href="<?php echo esc_url($event->location_link); ?>" target="_blank">
                                <?php echo esc_html(stripslashes($event->location_name)); ?>
                            </a>
                        <?php else: ?>
                            <?php echo esc_html(stripslashes($event->location_name)); ?>
                        <?php endif; ?>
                    </h4>
                </div>
                <div class="event-description">
                    <p>
                        <?php echo wpautop(wp_kses_post($event->description)); ?>
                    </p>
                </div>
                <div class="event-registration">
                    <?php if ($is_unpublished): ?>
                        <div class="registration-closed">
                            <p>Registration for this event is closed.</p>
                        </div>
                    <?php elseif (isset($event->external_registration) && $event->external_registration): ?>
                        <div class="event-external">
                            <a href="<?php echo esc_url($event->external_registration_url); ?>" 
                               target="_blank" 
                               rel="noopener noreferrer"
                               class="register-button external-register-button">
                                <?php echo esc_html(!empty($event->external_registration_text) ? $event->external_registration_text : 'Register (External)'); ?> <span class="external-link-icon">↗</span>
                            </a>
                        </div>
                    <?php elseif ($event->guest_capacity == 0 || $remaining_capacity > 0): ?>
                        <div class="event-available">
                            <?php if ($event->guest_capacity > 0): ?>
                                <span class="event-available-text">Available Spots: <?php echo esc_html($remaining_capacity); ?></span>
                            <?php endif; ?>
                            <a href="<?php echo esc_url($event_registration_url); ?>" class="register-button">Register</a>
                        </div>
                    <?php elseif ($is_fully_booked && $has_waiting_list): ?>
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
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No upcoming events found.</p>
    <?php endif; ?>
</div>
