<div class="wrap">
    <h1>Event Management</h1>
    <form id="add-event-form" class="event-form">
        <h2>Add New Event</h2>
        <input type="hidden" name="action" value="save_event">
        <?php wp_nonce_field('event_admin_nonce'); ?>
        
        <table class="form-table">
            <tr>
                <th><label for="event_name">Event Name</label></th>
                <td><input type="text" id="event_name" name="event_name" required></td>
            </tr>
            <tr>
                <th><label for="event_date">Event Date</label></th>
                <td><input type="date" id="event_date" name="event_date" required></td>
            </tr>
            <tr>
                <th><label for="event_time">Event Time</label></th>
                <td><input type="time" id="event_time" name="event_time" required></td>
            </tr>
            <tr>
                <th><label for="location_name">Location Name</label></th>
                <td><input type="text" id="location_name" name="location_name" required></td>
            </tr>
            <tr>
                <th><label for="location_link">Location Link</label></th>
                <td><input type="url" id="location_link" name="location_link"></td>
            </tr>
            <tr>
                <th><label for="guest_capacity">Guest Capacity</label></th>
                <td><input type="number" id="guest_capacity" name="guest_capacity" min="1" required></td>
            </tr>
            <tr>
                <th><label for="admin_email">Admin Email</label></th>
                <td><input type="email" id="admin_email" name="admin_email" required></td>
            </tr>
        </table>
        
        <p class="submit">
            <input type="submit" class="button button-primary" value="Add Event">
        </p>
    </form>

    <h2>Existing Events</h2>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Event Name</th>
                <th>Date</th>
                <th>Time</th>
                <th>Location</th>
                <th>Capacity</th>
                <th>Registered</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($events as $event): 
                $registered = $wpdb->get_var($wpdb->prepare(
                    "SELECT SUM(guest_count) FROM {$wpdb->prefix}sct_event_registrations WHERE event_id = %d",
                    $event->id
                ));
                $registered = $registered ?: 0;
            ?>
                <tr>
                    <td><?php echo esc_html($event->event_name); ?></td>
                    <td><?php echo esc_html(date('Y-m-d', strtotime($event->event_date))); ?></td>
                    <td><?php echo esc_html(date('H:i', strtotime($event->event_time))); ?></td>
                    <td><?php echo esc_html($event->location_name); ?></td>
                    <td><?php echo esc_html($event->guest_capacity); ?></td>
                    <td><?php echo esc_html($registered); ?></td>
                    <td>
                        <button class="button edit-event" data-id="<?php echo $event->id; ?>">Edit</button>
                        <button class="button delete-event" data-id="<?php echo $event->id; ?>">Delete</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
