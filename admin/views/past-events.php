<div class="wrap">
    <h1>Past Events</h1>
    
    <?php if (empty($past_events)) : ?>
        <p>No past events found.</p>
    <?php else : ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Event Name</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Location</th>
                    <th>Registrations</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($past_events as $event) : 
                    // Get registration count for this event
                    $registration_count = $this->get_registration_count($event->id);
                    ?>
                    <tr>
                        <td><?php echo esc_html($event->event_name); ?></td>
                        <td><?php echo esc_html($event->event_date); ?></td>
                        <td><?php echo esc_html($event->event_time); ?></td>
                        <td>
                            <?php if ($event->location_link) : ?>
                                <a href="<?php echo esc_url($event->location_link); ?>" target="_blank">
                                    <?php echo esc_html($event->location_name); ?>
                                </a>
                            <?php else : ?>
                                <?php echo esc_html($event->location_name); ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo $registration_count; ?> / <?php echo $event->guest_capacity; ?>
                        </td>
                        <td>
                            <a href="?page=past-registrations&event_id=<?php echo $event->id; ?>" 
                            class="button">View Registrations</a>
                            <!-- <a href="?page=event-registrations&view=list&event_id=<?php echo $event->id; ?>" 
                               class="button">View Registrations</a> -->
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
