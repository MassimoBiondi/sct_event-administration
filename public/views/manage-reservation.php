<?php
    if ($reservation) {
        $event = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sct_events WHERE id = %d",
            $reservation->event_id
        ));
        ?>
        <div class="manage-reservation-page">
            <h2>Manage Your Reservation</h2>
            <p><strong>Event Name:</strong> <?php echo esc_html($event->event_name); ?></p>
            <p><strong>Guest Count:</strong> <?php echo esc_html($reservation->guest_count); ?></p>
            <p><strong>Registration Date:</strong> <?php echo esc_html(date('Y-m-d H:i', strtotime($reservation->registration_date))); ?></p>
            <button class="button delete-reservation" data-reservation-id="<?php echo esc_attr($reservation->id); ?>" data-unique-id="<?php echo esc_attr($unique_id); ?>" data-nonce="<?php echo wp_create_nonce('delete_reservation_nonce'); ?>">Delete Reservation</button>
        </div>
        <?php
    } else {
        echo '<p>from template Invalid reservation identifier.</p>';
    }