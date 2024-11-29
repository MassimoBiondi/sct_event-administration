<div class="wrap">
    <h1>Past Event Registrations</h1>

    <div class="tablenav top">
        <div class="alignleft actions">
            <form method="get">
                <input type="hidden" name="page" value="past-registrations">
                <select name="event_id" id="event-selector">
                    <option value="">Select Past Event</option>
                    <?php foreach ($past_events as $event) : ?>
                        <option value="<?php echo esc_attr($event->id); ?>" 
                                <?php selected($event_id, $event->id); ?>>
                            <?php echo esc_html($event->event_name); ?> 
                            (<?php echo esc_html($event->event_date); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="submit" class="button" value="View Registrations">
            </form>

            <!-- <?php if ($event_id && !empty($registrations)) : ?>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display: inline-block; margin-left: 10px;">
                    <input type="hidden" name="action" value="export_event_registrations" />
                    <input type="hidden" name="event_id" value="<?php echo esc_attr($event_id); ?>" />
                    <?php wp_nonce_field('export_event_registrations_' . $event_id); ?>
                    <button type="submit" class="button">
                        <span class="dashicons dashicons-download" style="vertical-align: middle; margin-right: 5px;">
                        Export to CSV
                    </button>
                </form>
            <?php endif; ?> -->

        </div>
    </div>

    <?php if ($event_id && !empty($registrations)) : ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Guest Count</th>
                    <th>Registration Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($registrations as $registration) : ?>
                    <tr>
                        <td><?php echo esc_html($registration->name); ?></td>
                        <td><?php echo esc_html($registration->email); ?></td>
                        <td><?php echo esc_html($registration->guest_count); ?></td>
                        <td><?php echo esc_html(
                            date('Y-m-d H:i', strtotime($registration->registration_date))
                        ); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2">
                        <strong>Total Registrations:</strong> <?php echo count($registrations); ?>
                    </td>
                    <td colspan="1">
                        <strong>Total Guests:</strong> 
                        <?php 
                        echo array_reduce($registrations, function($carry, $item) {
                            return $carry + $item->guest_count;
                        }, 0);
                        ?>
                    </td>
                    <td>
                        <?php if ($event_id && !empty($registrations)) : ?>
                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display: inline-block; margin-left: 10px;">
                                <input type="hidden" name="action" value="export_event_registrations" />
                                <input type="hidden" name="event_id" value="<?php echo esc_attr($event_id); ?>" />
                                <?php wp_nonce_field('export_event_registrations_' . $event_id); ?>
                                <button type="submit" class="button button-small">
                                    <span class="dashicons dashicons-download" style="vertical-align: middle; margin-right: 5px;"></span>
                                    Export CSV
                                </button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            </tfoot>
        </table>
    <?php elseif ($event_id) : ?>
        <p>No registrations found for this event.</p>
    <?php else : ?>
        <p>Please select an event to view its registrations.</p>
    <?php endif; ?>
</div>

<style>
.status-badge {
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 500;
}
.status-confirmed {
    background-color: #dff0d8;
    color: #3c763d;
}
.status-pending {
    background-color: #fcf8e3;
    color: #8a6d3b;
}
#event-selector {
    min-width: 300px;
}
</style>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Auto-submit form when event is selected (optional)
    $('#event-selector').on('change', function() {
        if (this.value) {
            $(this).closest('form').submit();
        }
    });
});
</script>
