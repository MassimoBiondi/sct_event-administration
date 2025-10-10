<div class="wrap">
    <h1>Registration Management</h1>
    <?php wp_nonce_field('delete_registration_nonce', 'delete_registration_security'); ?>
    <?php $sct_settings = get_option('event_admin_settings', []); ?>
    <div id="currency-format" data-currency-format="<?php echo esc_attr($sct_settings['currency_format']); ?>" style="display: none;"></div>
    <div id="currency-symbol" data-currency-symbol="<?php echo esc_attr($sct_settings['currency_symbol']); ?>" style="display: none;"></div>
    <?php foreach ($upcoming_events as $event): 
        $registrations = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sct_event_registrations WHERE event_id = %d ORDER BY registration_date DESC",
            $event->id
        ));
        
        // Get total capacity and registered guests
        $total_registered = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(guest_count) FROM {$wpdb->prefix}sct_event_registrations WHERE event_id = %d",
            $event->id
        ));
        $total_registered = $total_registered ?: 0;
        $remaining_capacity = ($event->guest_capacity > 0) ? $event->guest_capacity - $total_registered : 'n/a';

    ?>
        <div class="event-registrations">

        

                <div class="event-header">
                    <?php if ($event->member_only): ?>
                        <img src="<?php echo plugin_dir_url(__DIR__) . 'css/img/member.svg'; ?>" alt="Lottery" class="lottery-icon" style="width: 20px; height: 20px;">
                    <?php endif; ?>
                    <?php if ($event->by_lottery): ?>
                        <img src="<?php echo plugin_dir_url(__DIR__) . 'css/img/lottery_wheel.svg'; ?>" alt="Lottery" class="lottery-icon" style="width: 20px; height: 20px;">
                    <?php endif; ?>
                    <h2><?php echo esc_html($event->event_name); ?></h2>
                    <span class="event-date"><?php echo date('Y-m-d', strtotime($event->event_date)); ?></span>

                    <a class="button button-small mail-to-all" 
                            href="#email-modal" uk-toggle
                            data-event-id="<?php echo esc_attr($event->id); ?>"
                            data-event-name="<?php echo esc_attr($event->event_name); ?>">
                            <!-- <span class="dashicons dashicons-email-alt" style="vertical-align: middle; margin-right: 5px;"></span> -->
                            Email to All
                    </a>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display: inline-block; margin-left: 15px;">
                        <input type="hidden" name="action" value="export_event_registrations" />
                        <input type="hidden" name="event_id" value="<?php echo esc_attr($event->id); ?>" />
                        <?php wp_nonce_field('export_event_registrations_' . $event->id); ?>
                        <button type="submit" class="button button-small">
                            <!-- <span class="dashicons dashicons-download" style="vertical-align: middle; margin-right: 5px;"></span> -->
                            Export CSV
                        </button>
                    </form>
                    <a class="button button-small add-registration" 
                            href="#add-registration-modal" uk-toggle
                            data-event-id="<?php echo esc_attr($event->id); ?>"
                            data-pricing-options="<?php echo esc_attr(json_encode(maybe_unserialize($event->pricing_options))); ?>"
                            data-goods-services="<?php echo esc_attr(json_encode(maybe_unserialize($event->goods_services))); ?>">
                        Add Registration
                    </a>
                    <?php 
                    // Check if winners have already been selected
                    $winners_selected = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$wpdb->prefix}sct_event_registrations WHERE event_id = %d AND is_winner = 1",
                        $event->id
                    )) > 0;
                    ?>
                    <?php if ($event->by_lottery): ?>
                        <button class="button button-small select-winners" style="display: inline-block; margin-left: 15px;" data-event-id="<?php echo esc_attr($event->id); ?>" <?php echo $winners_selected ? 'disabled' : ''; ?>>
                            <span class="dashicons dashicons-awards" style="vertical-align: middle; margin-right: 5px;"></span>
                            Select Winners
                        </button>
                    <?php endif; ?>
                </div>            
                
                <?php if (!empty($registrations)): ?>
                    <div class="uk-overflow-auto">
                        <table class="uk-table wp-list-table widefat striped">
                            <?php
                            $pricing_options = !empty($event->pricing_options) ? maybe_unserialize($event->pricing_options) : [];
                            $goods_services_options = !empty($event->goods_services) ? maybe_unserialize($event->goods_services) : [];
                            $total_columns = count($pricing_options) + count($goods_services_options);
                            ?>

                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <?php if (!empty($pricing_options)) : ?>
                                        <?php foreach ($pricing_options as $option) : ?>
                                            <th class="collapse column-small-number center"
                                                <?php if ($total_columns > 4): ?>
                                                    uk-tooltip="title: <?php echo esc_attr($option['name']); ?>"
                                                <?php endif; ?>>
                                                <?php
                                                if ($total_columns > 4) {
                                                    echo '<span uk-icon="user"></span>';
                                                    // echo esc_html(mb_strimwidth($option['name'], 0, 5, '…'));
                                                } else {
                                                    echo esc_html($option['name']);
                                                }
                                                ?>
                                            </th>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    <?php if (!empty($goods_services_options)) : ?>
                                        <?php foreach ($goods_services_options as $service) : ?>
                                            <th class="collapse column-small-number center"
                                                <?php if ($total_columns > 4): ?>
                                                    uk-tooltip="title: <?php echo esc_attr($service['name']); ?>"
                                                <?php endif; ?>>
                                                <?php
                                                if ($total_columns > 4) {
                                                    echo '<span uk-icon="tag"></span>';
                                                    // echo esc_html(mb_strimwidth($service['name'], 0, 5, '…'));
                                                } else {
                                                    echo esc_html($service['name']);
                                                }
                                                ?>
                                            </th>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    <th class="collapse column-small center">Total Guests</th>
                                    <?php if (!empty($event->goods_services) || !empty($event->pricing_options)) : ?>
                                        <th class="collapse column-small center">Price</th>
                                        <th class="collapse column-small center">Method</th>
                                        <th class="collapse column-small center sort-status">Status</th>
                                    <?php endif; ?>
                                    <th class="right"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($registrations as $registration) : 
                                    $guest_details = maybe_unserialize($registration->guest_details);
                                    $goods_services = maybe_unserialize($registration->goods_services);
                                    $total_guests = $registration->guest_count;

                                    // Calculate total price
                                    $total_price = 0;

                                    // Add pricing options total
                                    if (!empty($event->pricing_options)) {
                                        $pricing_options = maybe_unserialize($event->pricing_options);
                                        foreach ($pricing_options as $index => $option) {
                                            $guest_count = isset($guest_details[$index]['count']) ? intval($guest_details[$index]['count']) : 0;
                                            $price = isset($option['price']) ? floatval($option['price']) : 0;
                                            $total_price += $guest_count * $price;
                                        }
                                    }

                                    // Add goods/services total
                                    if (!empty($event->goods_services)) {
                                        $goods_services_options = maybe_unserialize($event->goods_services);
                                        foreach ($goods_services_options as $index => $service) {
                                            $service_count = isset($goods_services[$index]['count']) ? intval($goods_services[$index]['count']) : 0;
                                            $price = isset($service['price']) ? floatval($service['price']) : 0;
                                            $total_price += $service_count * $price;
                                        }
                                    }
                                ?>
                                    <tr data-registration-id="<?php echo esc_attr($registration->id); ?>">
                                        <td><?php echo esc_html($registration->name); ?></td>
                                        <td class="collapse"><?php echo esc_html($registration->email); ?></td>
                                        <?php if (!empty($event->pricing_options)) : ?>
                                            <?php foreach ($pricing_options as $index => $option) : 
                                                $guest_count = isset($guest_details[$index]['count']) ? intval($guest_details[$index]['count']) : 0;
                                            ?>
                                                <td class="collapse column-small center" uk-tooltip="title: <?php echo esc_attr($option['name']); ?>" data-name="<?php echo esc_attr($option['name']); ?>" data-price="<?php echo esc_attr($option['price']); ?>">
                                                    <input type="number" 
                                                        class="pricing-option-guest-count small-text" 
                                                        data-pricing-index="<?php echo esc_attr($index); ?>" 
                                                        value="<?php echo $guest_count == 0 ? '' : esc_attr($guest_count); ?>"
                                                        data-original="<?php echo esc_attr($guest_count); ?>" 
                                                        min="0">
                                                </td>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                        <?php if (!empty($event->goods_services)) : ?>
                                            <?php foreach ($goods_services_options as $index => $service) : 
                                                $service_count = isset($goods_services[$index]['count']) ? intval($goods_services[$index]['count']) : 0;
                                            ?>
                                                <td class="collapse column-small center" uk-tooltip="title: <?php echo esc_attr($service['name']); ?>" data-name="<?php echo esc_attr($service['name']); ?>" data-price="<?php echo esc_attr($service['price']); ?>">
                                                    <input type="number" 
                                                        class="goods-service-count small-text" 
                                                        data-service-index="<?php echo esc_attr($index); ?>" 
                                                        value="<?php echo $service_count == 0 ? '' : esc_attr($service_count); ?>"
                                                        data-original="<?php echo esc_attr($service_count); ?>" 
                                                        min="0">
                                                </td>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                        <td class="total-guests collapse column-small center">
                                            <?php if (empty($event->pricing_options) && empty($event->goods_services)) : ?>
                                                <input type="number" 
                                                    class="simple-guest-count small-text" 
                                                    value="<?php echo esc_attr($total_guests); ?>"
                                                    data-original="<?php echo esc_attr($total_guests); ?>" 
                                                    min="1">
                                                <!-- <button class="button update-simple-guest-count" style="display: none;" data-registration-id="<?php echo esc_attr($registration->id); ?>">
                                                    <span class="dashicons dashicons-yes"></span>
                                                </button> -->
                                            <?php else : ?>
                                                <?php echo esc_attr($total_guests); ?>
                                            <?php endif; ?>
                                        </td>
                                        <?php if (!empty($event->goods_services) || !empty($event->pricing_options)) : ?>
                                            <td class="total-price collapse column-small center">
                                                <?php echo esc_html(number_format($total_price, 2)); ?>
                                            </td>
                                            <td class="collapse column-small center">
                                                <?php echo esc_html($registration->payment_method); ?>
                                            </td>
                                            <td class="collapse column-small center">
                                                <!-- <?php echo esc_html($registration->payment_status); ?> -->
                                                <select class="change-payment-status status-<?php echo esc_attr($registration->payment_status); ?>" 
                                                        data-registration-id="<?php echo esc_attr($registration->id); ?>" 
                                                        data-nonce="<?php echo wp_create_nonce('update_payment_status'); ?>">
                                                    <option value="pending" <?php selected($registration->payment_status, 'pending'); ?>>Pending</option>
                                                    <option value="paid" <?php selected($registration->payment_status, 'paid'); ?>>Paid</option>
                                                    <option value="failed" <?php selected($registration->payment_status, 'failed'); ?>>Failed</option>
                                                </select>
                                            </td>
                                        <?php endif; ?>

                                        <td class="right">
                                            <a class="button view-registration-details"
                                                    href="#registration-details-modal"
                                                    uk-toggle
                                                    data-registration-id="<?php echo esc_attr($registration->id); ?>" 
                                                    data-nonce="<?php echo wp_create_nonce('view_registration_details'); ?>">
                                                View
                                            </a>
                                            <button class="button update-guest-counts" style="display: none;" data-registration-id="<?php echo esc_attr($registration->id); ?>">
                                                Update
                                            </button>
                                            <button class="button update-simple-guest-count" style="display: none;" data-registration-id="<?php echo esc_attr($registration->id); ?>">
                                                    <span class="dashicons dashicons-yes"></span>
                                            </button>
                                            <a class="button send-email" 
                                                    href="#email-modal" uk-toggle
                                                    data-registration-id="<?php echo esc_attr($registration->id); ?>" 
                                                    data-recipient-email="<?php echo esc_attr($registration->email); ?>" 
                                                    data-event-name="<?php echo esc_attr($event->event_name); ?>">
                                                Email
                                            </a>
                                            <button class="button delete-registration" 
                                                    data-registration-id="<?php echo esc_attr($registration->id); ?>" 
                                                    data-nonce="<?php echo wp_create_nonce('delete_registration'); ?>">
                                                Delete
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="2">Totals</th>
                                    <?php if (!empty($event->pricing_options)) : ?>
                                        <?php foreach ($pricing_options as $index => $option) : ?>
                                            <th class="collapse column-small center total-pricing-option" data-pricing-index="<?php echo esc_attr($index); ?>">0</th>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    <?php if (!empty($event->goods_services)) : ?>
                                        <?php foreach ($goods_services_options as $index => $service) : ?>
                                            <th class="collapse column-small center total-goods-service" data-service-index="<?php echo esc_attr($index); ?>">0</th>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    <th class="collapse column-small center event-total-guests">
                                        <?php echo esc_html($total_guests); ?>
                                    </th>
                                    <?php if (!empty($event->goods_services) || !empty($event->pricing_options)) : ?>
                                        <th class="collapse column-small center event-total-price">0.00</th>
                                        <th></th>
                                        <th></th>
                                    <?php endif; ?>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php else: ?>
                    <p>No registrations for this event.</p>
                <?php endif; ?>


        </div>

    <?php endforeach; ?>

    <!-- Add this at the bottom of the file, before the closing </div> -->
    <div id="email-modal" uk-modal>
        <div class="uk-modal-dialog">
            <button class="uk-modal-close-default" type="button" uk-close></button>
            <div class="uk-modal-header">
                <h2>Send Email to <span id="admin_mail_recipient" class="status-indicator status-failed" style="font-size: inherit;" ></span></h2>
            </div>
            <div class="uk-modal-body">
                <form id="send-email-form">
                    <?php wp_nonce_field('send_email_nonce', 'email_security'); ?>
                    <input type="hidden" name="action" value="send_registration_email">
                    <input type="hidden" name="recipient_email" id="recipient_email">
                    <input type="hidden" name="registration_id" id="registration_id">
                    <input type="hidden" name="event_id" id="event_id">
                    <input type="hidden" name="is_mass_email" id="is_mass_email" value="0">
                    
                    <div class="form-field">
                        <label for="email_subject">Subject:</label>
                        <input type="text" id="email_subject" name="email_subject" class="regular-text" required>
                    </div>
                    
                    <div class="form-field">
                        <label for="email_body">Message:</label>
                        <textarea id="email_body" name="email_body" rows="10" class="large-text" required></textarea>
                    </div>

                    <div class="postbox">
                        <div class="postbox-header">
                            <h2 class="hndle"><?php _e('Available Variables', 'sct-events'); ?></h2>
                        </div>
                        <div class="inside">
                            <div class="placeholder-category">
                                <h4><?php _e('Registration Data', 'sct-events'); ?></h4>
                                <div class="placeholder-list">
                                    <code>{{registration.name}}</code>
                                    <code>{{registration.email}}</code>
                                    <code>{{registration.guest_count}}</code>
                                    <code>{{registration.id}}</code>
                                    <code>{{registration.manage_link}}</code>
                                </div>
                            </div>
                            
                            <div class="placeholder-category">
                                <h4><?php _e('Event Information', 'sct-events'); ?></h4>
                                <div class="placeholder-list">
                                    <code>{{event.name}}</code>
                                    <code>{{event.date}}</code>
                                    <code>{{event.time}}</code>
                                    <code>{{event.description}}</code>
                                    <code>{{event.location_name}}</code>
                                    <code>{{event.location_link}}</code>
                                </div>
                            </div>
                            
                            <div class="placeholder-category">
                                <h4><?php _e('Capacity & Payment', 'sct-events'); ?></h4>
                                <div class="placeholder-list">
                                    <code>{{capacity.total}}</code>
                                    <code>{{capacity.remaining}}</code>
                                    <code>{{payment.total}}</code>
                                    <code>{{payment.status}}</code>
                                </div>
                            </div>
                            
                            <div class="placeholder-category">
                                <h4><?php _e('Website Information', 'sct-events'); ?></h4>
                                <div class="placeholder-list">
                                    <code>{{website.name}}</code>
                                    <code>{{website.url}}</code>
                                    <code>{{date.current}}</code>
                                    <code>{{date.year}}</code>
                                </div>
                            </div>
                            
                            <p class="description">
                                <em><?php _e('Note: Old format like {name} still works for backward compatibility', 'sct-events'); ?></em>
                            </p>
                        </div>
                    </div>
                    
                    <div class="submit-buttons">
                        <button type="button" class="button cancel-email">Cancel</button>
                        <button type="submit" class="button button-primary">Send Email</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <div id="qqqemail-modal" class="email-modal">
        <div class="email-modal-content">
            <div class="modal-header">
                <span class="close-modal">&times;</span>
                <h2>Send Email to <span id="admin_mail_recipient" class="status-indicator status-failed" style="font-size: inherit;" ></span></h2>
            </div>
            
            <form id="send-email-form">
                <?php wp_nonce_field('send_email_nonce', 'email_security'); ?>
                <input type="hidden" name="action" value="send_registration_email">
                <input type="hidden" name="recipient_email" id="recipient_email">
                <input type="hidden" name="registration_id" id="registration_id">
                <input type="hidden" name="event_id" id="event_id">
                <input type="hidden" name="is_mass_email" id="is_mass_email" value="0">
                
                <div class="form-field">
                    <label for="email_subject">Subject:</label>
                    <input type="text" id="email_subject" name="email_subject" class="regular-text" required>
                </div>
                
                <div class="form-field">
                    <label for="email_body">Message:</label>
                    <textarea id="email_body" name="email_body" rows="10" class="large-text" required></textarea>
                </div>

                <div class="form-field placeholder-info">
                    <p><strong>Available placeholders:</strong></p>
                    <p>
                        <code>{name}</code> - Registrant's name<br>
                        <code>{email}</code> - Registrant's email<br>
                        <code>{guest_count}</code> - Number of guests<br>
                        <code>{event_name}</code> - Event name<br>
                        <code>{event_date}</code> - Event date<br>
                        <code>{event_time}</code> - Event time<br>
                        <code>{description}</code> - Event description<br>
                        <code>{location_name}</code> - Event location name<br>
                        <code>{location_link}</code> - Event location link<br>
                        <code>{location_url}</code> - Event location URL<br>
                        <code>{guest_capacity}</code> - Event guest capacity<br>
                        <code>{member_only}</code> - Member-only event<br>
                        <code>{total_price}</code> - Total price<br>
                        <code>{remaining_capacity}</code> - Remaining capacity<br>
                    </p>
                </div>
                
                <div class="submit-buttons">
                    <button type="button" class="button cancel-email">Cancel</button>
                    <button type="submit" class="button button-primary">Send Email</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Registration Modal -->
    <div id="add-registration-modal" uk-modal>
        <div class="uk-modal-dialog">
            <button class="uk-modal-close-default" type="button" uk-close></button>
            <div class="uk-modal-header">
                <h2 class="uk-modal-title">Add Registration</h2>
            </div>
            <div class="uk-modal-body">
                <form id="add-registration-form">
                    <?php wp_nonce_field('update_registration_nonce', 'security'); ?>
                    <input type="hidden" name="action" value="add_registration">
                    <input type="hidden" name="event_id" id="add_registration_event_id">

                    <div class="form-field">
                        <label for="add_registration_name">Name:</label>
                        <input type="text" id="add_registration_name" name="name" required>
                    </div>

                    <div class="form-field">
                        <label for="add_registration_email">Email:</label>
                        <input type="email" id="add_registration_email" name="email" required>
                    </div>

                    <div id="add_registration_guest_count_field" class="form-field">
                        <label for="add_registration_guest_count">Guest Count:</label>
                        <input type="number" id="add_registration_guest_count" name="guest_count" min="0" value="0">
                    </div>

                    <div id="add_registration_pricing_options_container" class="form-field">
                        <!-- Dynamic pricing options will be appended here -->
                    </div>

                    <div id="add_registration_goods_services_container" class="form-field">
                        <div id="goods-services-options">
                            <!-- Dynamic goods/services options will be appended here -->
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="button cancel-registration">Cancel</button>
                        <button type="submit" class="button button-primary">Add Registration</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Winners Selection Modal -->
    <div id="select-winners-modal" uk-modal>
        <div class="uk-modal-dialog">
            <button class="uk-modal-close-default" type="button" uk-close></button>
            <div class="uk-modal-header">
                <h2 class="uk-modal-title">Select Winners</h2>
            </div>
            <div class="uk-modal-body">
                <form id="select-winners-form">
                    <?php wp_nonce_field('select_random_winners_nonce', 'security'); ?>
                    <input type="hidden" name="action" value="select_random_winners">
                    <input type="hidden" name="event_id" id="select_winners_event_id">

                    <div class="form-field">
                        <label for="num_winners">Number of Winners:</label>
                        <input type="number" id="num_winners" name="num_winners" min="1" required>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="button cancel-selection">Cancel</button>
                        <button type="submit" class="button button-primary">Select Winners</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <!-- Registration Details Modal -->
    <div id="registration-details-modal" uk-modal>
        <div class="uk-modal-dialog">
            <button class="uk-modal-close-default" type="button" uk-close></button>
            <div class="uk-modal-header">
                <h2 class="uk-modal-title">Registration Details</h2>
            </div>
            <div class="uk-modal-body">
                <div id="registration-details-content">
                    <!-- Registration details will be dynamically loaded here -->
                </div>
            </div>
            <div class="uk-modal-footer uk-text-right">
                <button class="uk-button uk-button-default uk-modal-close" type="button">Cancel</button>
            </div>
        </div>
    </div>

</div>
