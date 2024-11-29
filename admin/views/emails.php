<div class="wrap">
    <h1>Event Emails History</h1>
    <?php if (!empty($emails)): ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Event</th>
                    <th>Recipient</th>
                    <th>Type</th>
                    <th>Subject</th>
                    <th>Sent Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($emails as $email): ?>
                    <tr>
                        <td><?php echo esc_html($email->event_name); ?></td>
                        <td>
                            <?php 
                            if ($email->email_type === 'mass_email') {
                                echo '<span class="dashicons dashicons-groups" style="vertical-align: middle;"></span>';
                                echo esc_html($email->recipient_email);
                                // if ($email->failed_count > 0) {
                                //     echo sprintf(
                                //         ' <span class="failed-indicator">(%d failed)</span>',
                                //         $email->failed_count
                                //     );
                                // }
                            } else {
                                echo '<span class="dashicons dashicons-admin-users" style="vertical-align: middle;"></span>';
                                echo esc_html($email->recipient_email);
                            }
                            ?>
                        </td>
                        <td><?php echo esc_html(ucfirst(str_replace('_', ' ', $email->email_type))); ?></td>
                        <td><?php echo esc_html($email->subject); ?></td>
                        <td><?php echo esc_html(date('Y-m-d H:i:s', strtotime($email->grouped_sent_date))); ?></td>
                        <td>
                            <span class="status-indicator <?php echo $email->status === 'sent' ? 'status-success' : 'status-failed'; ?>">
                                <?php echo esc_html(ucfirst($email->status)); ?>
                            </span>
                            <?php
                                if ($email->email_type === 'mass_email' && $email->failed_count > 0) {
                                    echo sprintf(
                                        ' <span class="status-indicator status-failed">(%d failed)</span>',
                                        $email->failed_count
                                    );
                                }
                            ?>
                        </td>
                        <td>
                            <button class="button button-small view-email-content" 
                                    data-email-id="<?php echo esc_attr($email->id); ?>"
                                    data-subject="<?php echo esc_attr($email->subject); ?>"
                                    data-content="<?php echo esc_attr($email->email_content); ?>">
                                <span class="dashicons dashicons-visibility" style="vertical-align: middle; margin-right: 5px;"></span>
                                Content
                            </button>
                            <?php if ($email->email_type === 'mass_email'): ?>
                                <button class="button button-small view-recipients" 
                                        data-recipients="<?php echo esc_attr($email->all_recipients); ?>"
                                        data-subject="<?php echo esc_attr($email->subject); ?>"
                                        data-event-id="<?php echo esc_attr($email->event_id); ?>"
                                        data-nonce="<?php echo wp_create_nonce('sct_email_nonce'); ?>">
                                    <span class="dashicons dashicons-groups" style="vertical-align: middle; margin-right: 5px;"></span>
                                     Recipients
                                </button>
                            <?php endif; ?>
                            <?php if ($email->status === 'failed'): ?>
                                <button class="button button-small retry-email" 
                                        data-email="<?php echo esc_attr($email->recipient_email); ?>" 
                                        data-event-id="<?php echo esc_attr($email->event_id); ?>"
                                        data-nonce="<?php echo wp_create_nonce('sct_email_nonce'); ?>">
                                    <span class="dashicons dashicons-update" style="vertical-align: middle; margin-right: 5px;"></span>
                                    Retry
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    <?php else: ?>
        <p>No email records found.</p>
    <?php endif; ?>

    <!-- Email Content Modal -->
    <div id="email-content-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="close-modal">&times;</span>
                <h2 id="modal-email-subject"></h2>
            </div>
            <div class="modal-body">
                <div id="email-content"></div>
            </div>
        </div>
    </div>

    <!-- Recipients Modal -->
    <div id="recipients-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="close-modal">&times;</span>
                <h2 id="modal-recipients-title"></h2>
            </div>
            <div class="modal-body">
                <div class="modal-notice-container"></div>
                <div id="recipients-list"></div>
            </div>
        </div>
    </div>


</div>