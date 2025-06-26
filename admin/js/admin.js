jQuery(document).ready(function($) {

    function showAdminNotice(message, type) {

        const allowedTypes = ['success', 'error', 'warning', 'info'];
        type = allowedTypes.includes(type) ? type : 'info';

        var notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p>');

        var insertPoint = $('.wp-header-end');
        if (insertPoint.length === 0) {
            insertPoint = $('#wpbody-content .wrap').first(); 
            if (insertPoint.length > 0) {
                insertPoint.prepend(notice);
            } else {

                $('body').prepend(notice);
            }
        } else {
             insertPoint.after(notice);
        }

        if (typeof notice !== 'undefined' && notice.length > 0 && typeof notice.on === 'function') {
            notice.on('click', '.notice-dismiss', function() {
                $(this).closest('.notice').fadeOut(function() {
                    $(this).remove();
                });
            });
        }

        setTimeout(function() {
            if (notice.closest('body').length > 0) { 
                 notice.fadeOut(function() {
                    $(this).remove();
                });
            }
        }, 5000); 
    }

    $('#copy-event-button').on('click', function () {
        $('#copy-event-modal').show();
        $('#copy-event-modal').css('display', 'block'); 
    });

    $(document).on('change', '#previous-events-dropdown', function () {
        console.log('Selected value:', $(this).val()); 
        selectedEventId = $(this).val();

    });

    $(document).on('click', '#confirm-copy-event', function () {

        selectedEventName = $('#previous-events-dropdown').val();
        newEventDate = $('#new-event-date').val();

        console.log('Selected Event Name:', selectedEventName);
        console.log('New Event Date:', newEventDate);

        if (!selectedEventName || !newEventDate) {

            showAdminNotice('Please select an event and provide a new date.', 'warning');
            return;
        }

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'copy_event_by_name',
                event_name: selectedEventName,
                new_event_date: newEventDate,
                security: eventAdmin.copy_event_nonce
            },
            success: function (response) {
                if (response.success) {

                    showAdminNotice('Event copied successfully.', 'success');
                    location.reload();
                } else {

                    showAdminNotice(response.data.message || 'Failed to copy event.', 'error');
                }
            },
            error: function () {

                showAdminNotice('An error occurred while copying the event.', 'error');
            }
        });
    });

    $(window).on('click', function (e) {
        if ($(e.target).hasClass('modal')) {
            $(e.target).hide();
            clearModalFields(); 
        }
    });

    function clearModalFields() {
        $('#previous-events-dropdown').val('');
        $('#new-event-date').val('');
    }

    let mediaUploader;

    $('#upload-thumbnail-button').on('click', function (e) {
        e.preventDefault();
        console.log('Button clicked'); 

        if (mediaUploader) {
            mediaUploader.open();
            return;
        }

        mediaUploader = wp.media({
            title: 'Select Event Thumbnail',
            button: {
                text: 'Use this image'
            },
            multiple: false
        });

        mediaUploader.on('select', function () {
            const attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#thumbnail_url').val(attachment.url);
            $('#thumbnail-preview').html('<img src="' + attachment.url + '" style="max-width: 100%; height: auto;">');
        });

        mediaUploader.open();
    });

    $('#add-event-form').on('submit', function(e) {
        e.preventDefault();

        $.ajax({
            url: eventAdmin.ajaxurl,
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {

                    window.location.href = 'admin.php?page=event-admin&event_added=1'; 
                } else {

                    showAdminNotice('Error: ' + (response.data.message || 'Could not add event.'), 'error');
                }
            },
            error: function(xhr, status, error) {
                 showAdminNotice('An error occurred: ' + error, 'error');
            }
        });
    });

    $('.delete-event').on('click', function(e) {
        e.preventDefault();

        if (!confirm('Are you sure you want to delete this event? This will also delete all associated registrations.')) {
            return;
        }

        var button = $(this);
        var eventId = button.data('event-id');
        var nonce = button.data('nonce');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'delete_event',
                event_id: eventId,
                security: nonce
            },
            success: function(response) {
                if (response.success) {
                    showAdminNotice('Event deleted successfully', 'success');
                    button.closest('tr').fadeOut(400, function() {
                        $(this).remove();
                    });
                } else {
                    showAdminNotice(response.data.message || 'Error deleting event', 'error');
                }
            },
            error: function() {
                showAdminNotice('Error deleting event', 'error');
            }
        });
    });

    $('.copy-url').on('click', function() {
        var button = $(this);
        var url = button.data('url');

        var tempInput = $('<input>');
        $('body').append(tempInput);
        tempInput.val(url).select();
        var success = false;
        try {
            success = document.execCommand('copy');
        } catch (err) {
            console.error('Copy command failed:', err);
        }
        tempInput.remove();

        if (success) {
            showAdminNotice('URL <strong>'+ url +'</strong> copied to clipboard.', 'success');
        } else {
             showAdminNotice('Failed to copy URL. Please copy it manually.', 'warning');

             window.prompt("Copy to clipboard: Ctrl+C, Enter", url);
        }
    });

    $(document).on('click', '.copy-previous-event', function (e) {
        e.preventDefault();

        const eventId = $(this).data('event-id');
        const nonce = $(this).data('nonce');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'fetch_previous_events',
                event_id: eventId,
                security: nonce
            },
            success: function (response) {
                if (response.success) {

                    const modalContent = $('#event-details-modal .modal-body #event-details');
                    modalContent.html(response.data.html);
                    $('#event-details-modal').show();
                } else {

                    showAdminNotice(response.data.message || 'Error fetching previous events.', 'error');
                }
            },
            error: function () {

                showAdminNotice('Error fetching previous events.', 'error');
            }
        });
    });

    $(document).on('click', '.event-details', function(e) {
        e.preventDefault();
        var event = $(this).data('event');
        var modal = $('#event-details-modal');
        var details = `
            <p><strong>Name:</strong> ${event.event_name}</p>
            <p><strong>Date:</strong> ${event.event_date}</p>
            <p><strong>Time:</strong> ${event.event_time}</p>
            <p><strong>Location:</strong> ${event.location_name}</p>
            <p><strong>Location Link:</strong> <a href="${event.location_link}" target="_blank">${event.location_link}</a></p>
            <p><strong>Description:</strong> ${event.description}</p>
            <p><strong>Capacity:</strong> ${event.guest_capacity}</p>
            <p><strong>Registered:</strong> ${event.registered}</p>
            <p><strong>Available:</strong> ${event.available}</p>
            <p><strong>Max Guests per Registration:</strong> ${event.max_guests_per_registration}</p>
            <p><strong>Organizer:</strong> ${event.admin_email}</p>
            <p><strong>Member Only:</strong> ${event.member_only ? 'Yes' : 'No'}</p>
        `;

    $('#event-details').html(details);
    modal.show();
    });

    $('.placeholder-info code').each(function() {
        $(this)
            .css('cursor', 'pointer')
            .attr('title', 'Click to insert')
            .on('click', function() {
                var placeholder = $(this).text() + ' ';
                var editorId = 'confirmation_template';

                if (typeof tinymce !== 'undefined' && tinymce.get(editorId) && !tinymce.get(editorId).isHidden()) {

                    tinymce.get(editorId).execCommand('mceInsertContent', false, placeholder);
                } else {

                    var $textarea = $('#' + editorId);
                    var startPos = $textarea[0].selectionStart;
                    var endPos = $textarea[0].selectionEnd;
                    var currentContent = $textarea.val();
                    var newContent = currentContent.substring(0, startPos) +
                        placeholder +
                        currentContent.substring(endPos);
                    $textarea.val(newContent);
                    $textarea.focus();
                    $textarea[0].setSelectionRange(startPos + placeholder.length, startPos + placeholder.length);
                }
            });
    });

    $('.simple-guest-count').on('input', function() {
        var input = $(this);
        var originalValue = input.data('original');
        var row = input.closest('tr'); 
        var updateButton = row.find('.update-simple-guest-count'); 

        console.log('Update button found:', updateButton.length > 0);
        console.log('Original value:', originalValue, 'Current value:', input.val());

        if (input.val() != originalValue) {
            updateButton.show();
        } else {
            updateButton.hide();
        }
    });

    $('.pricing-option-guest-count, .goods-service-count').on('input', function () {
        const input = $(this);
        const row = input.closest('tr'); 
        const originalValue = input.data('original');
        const updateButton = row.find('.update-guest-counts'); 

        console.log('Update button found:', updateButton.length > 0);
        console.log('Original value:', originalValue, 'Current value:', input.val());

        if (input.val() != originalValue) {
            updateButton.show();
        } else {
            updateButton.hide();
        }

        updateRowTotalPrice(row);
    });

    $(document).on('input', '.guest-count', function () {
        let totalPrice = 0;

        $('.guest-count').each(function () {
            const count = parseInt($(this).val(), 10) || 0;
            const price = parseFloat($(this).siblings('input[name$="[price]"]').val()) || 0;
            totalPrice += count * price;
        });

        $('#total_price').val(totalPrice.toFixed(2));
    });

    $('.member-guest-count').on('input', function() {
        var input = $(this);
        var originalValue = input.data('original');
        var updateButton = input.siblings('.update-member-guest-count');

        if (input.val() != originalValue) {
            updateButton.show();
        } else {
            updateButton.hide();
        }
    });

    $(document).on('click', '.delete-registration', function (e) {
        e.preventDefault();

        if (!confirm('Are you sure you want to delete this registration?')) {
            return;
        }

        const button = $(this);
        const registrationId = button.data('registration-id');
        const nonce = button.data('nonce');

        $.ajax({
            url: eventAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'delete_registration',
                registration_id: registrationId,
                security: nonce
            },
            success: function (response) {
                if (response.success) {

                    showAdminNotice('Registration deleted successfully.', 'success');
                    button.closest('tr').fadeOut(400, function () {
                        $(this).remove();
                    });
                    calculateTotals(button.closest('.wp-list-table'));
                } else {

                    showAdminNotice(response.data.message || 'Error deleting registration.', 'error');
                }
            },
            error: function (response) {
                showAdminNotice('Error deleting registration.', 'error');
            }
        });
    });

     var modal = $('#email-modal');

     $('.send-email').on('click', function(e) {
         e.preventDefault();
         var button = $(this);
         var row = button.closest('tr');
         var registrationId = row.data('registration-id');
         var recipientEmail = button.data('recipient-email');
         var name = row.find('td:first').text().trim(); 

         $('#send-email-form #recipient_email').val(recipientEmail);
         $('#send-email-form #registration_id').val(registrationId);
         $('#send-email-form #is_mass_email').val('0');
         $('#send-email-form #event_id').val(''); 

         $('#email-modal #admin_mail_recipient').text(name || recipientEmail); 

         var eventName = button.closest('.event-registrations').find('h2').text().split(' - ')[0];
         $('#send-email-form #email_subject').val('Regarding: ' + eventName);
         $('#send-email-form #email_body').val('');

         modal.show();
     });

     UIkit.util.on('div[uk-modal]', 'beforeshow', function (e) {
        const modalId = $(this).attr('id'); 
        if (modalId === 'email-modal') {
            console.log('Preparing Email Modal...');
            $('#send-email-form  #email_body').html(''); 

        }

    });

    $('.mail-to-all').on('click', function(e) {
        e.preventDefault();
        var button = $(this);
        var eventId = button.data('event-id');
        var eventName = button.data('event-name');

        $('#send-email-form #event_id').val(eventId);
        $('#send-email-form #is_mass_email').val('1');
        $('#send-email-form #recipient_email').val(''); 
        $('#send-email-form #registration_id').val('');

        $('#email-modal #admin_mail_recipient').text('All Registrants'); 

        $('#send-email-form #email_subject').val('Regarding: ' + eventName);
        $('#send-email-form #email_body').val('');

    });

     $('.email-modal-content').on('click', function(e) {
         e.stopPropagation();
     });

     function closeModal() {
        modal.hide();
        var form = $('#send-email-form');
        if (form.length) {
            form[0].reset();

            $('#send-email-form #recipient_email').val('');
            $('#send-email-form #registration_id').val('');
            $('#send-email-form #event_id').val('');
            $('#send-email-form #is_mass_email').val('');
            $('#send-email-form #admin_mail_recipient').text(''); 
        }
     }

     $('#send-email-form').on('submit', function(e) {
        e.preventDefault();

        var form = $(this);
        var submitButton = form.find('button[type="submit"]');
        var originalButtonText = submitButton.text();

        submitButton.prop('disabled', true).text('Sending...');

        $.ajax({
            url: eventAdmin.ajaxurl,
            type: 'POST',
            data: form.serialize(),
            success: function(response) {
                if (response.success) {

                    showAdminNotice(response.data.message || 'Email sent successfully.', 'success');
                    closeModal();
                } else {

                    showAdminNotice('Error: ' + (response.data ? response.data.message : 'Unknown error'), 'error');
                }
            },
            error: function(xhr, status, error) {

                showAdminNotice('Error sending email: ' + error, 'error');
            },
            complete: function() {
                submitButton.prop('disabled', false).text(originalButtonText);
            }
        });
    });

    $('.retry-email').on('click', function(e) {
        e.preventDefault();

        const button = $(this);
        const email = button.data('email');
        const eventId = button.data('event-id');
        const nonce = button.data('nonce');

        button.prop('disabled', true).text('Sending...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'sct_retry_single_email',
                email: email,
                event_id: eventId,
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {

                    showAdminNotice('Email sent successfully!', 'success');

                    location.reload(); 
                } else {

                    showAdminNotice('Failed to send email: ' + (response.data.message || 'Unknown error'), 'error');
                }
            },
            error: function(xhr, status, error) {

                showAdminNotice('Error occurred while sending email. Please try again.', 'error');
                console.error('AJAX Error:', status, error);
            },
            complete: function() {

                button.prop('disabled', false).text('Retry');
            }
        });
    });

    $('.view-email-content').click(function() {
        const subject = $(this).data('subject');
        const content = $(this).data('content'); 

        $('#modal-email-subject').text(subject);

        if (/<[a-z][\s\S]*>/i.test(content)) {
             $('#email-content').html(content); 
        } else {
            $('#email-content').text(content); 
        }
        $('#email-content-modal').show();
    });

    $('.view-recipients').click(function() {
        const recipientsDataRaw = $(this).data('recipients');
        if (!recipientsDataRaw) {
            console.error('No recipients data found');
            return;
        }

        let recipients;
        try {
            recipients = JSON.parse(recipientsDataRaw);
        } catch (e) {
            console.error('Failed to parse recipients data:', e);
            showAdminNotice('Failed to load recipient data', 'error');
            return;
        }

        $('#recipients-list').empty();

        if (Array.isArray(recipients) && recipients.length > 0) {
            recipients.forEach(function(recipient) {
                $('#recipients-list').append(`<li>${recipient.email || recipient}</li>`);
            });
        } else {
            $('#recipients-list').append('<li>No recipients found</li>');
        }

        $('#recipients-modal').show();  const recipientsData = recipientsDataRaw.split('|');
        const subject = $(this).data('subject');
        const eventId = $(this).data('event-id');
        const wp_nonce = $(this).data('nonce'); 

        $('#modal-recipients-title').text('Recipients for: ' + subject);

        let recipientsList = '<ul>';
        recipientsData.forEach(function(recipientData) {
            if (!recipientData) return; 
            const [email, status] = recipientData.split(':');
            const statusClass = status === 'failed' ? 'failed-status' : 'success-status';
            const statusIcon = status === 'failed' ?
                '<span class="dashicons dashicons-warning" style="color: #d63638;"></span>': 
                '<span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>'; 

            recipientsList += `
                <li class="${statusClass}">
                    <span class="recipient-email">${email}</span>
                    <span class="status-label">${status === 'failed' ? 'Failed' : 'Sent'}</span>
                    ${status === 'failed' ? `
                        <button class="button button-small retry-single-email"
                                data-email="${email}"
                                data-event-id="${eventId}"
                                data-nonce="${wp_nonce}">
                            <span class="dashicons dashicons-update"></span>
                            Retry
                        </button>
                    ` : ''}
                </li>`;
        });
        recipientsList += '</ul>';

        $('#recipients-list').html(recipientsList);
        $('#recipients-modal').show();
    });

    $(document).on('click', '.modal', function(event) {

        if (event.target === this) {
            $(this).hide();
        }
    });

    $(document).on('click', '.modal-content, .email-modal-content', function(event) {
        event.stopPropagation();
    });

    function showNoticeInModal(message, type, modalSelector) {
        const noticeHtml = `
            <div class="notice notice-${type} is-dismissible inline-notice modal-notice">
                <p>${message}</p>
                <button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>
            </div>`;

        $(modalSelector + ' .modal-notice').remove();

        var noticeTarget = $(modalSelector + ' .modal-body'); 
        if (noticeTarget.length === 0) {
            noticeTarget = $(modalSelector + ' .modal-content'); 
        }
        noticeTarget.prepend(noticeHtml);

        $(modalSelector + ' .notice-dismiss').on('click', function() {
            $(this).closest('.modal-notice').fadeOut(function() {
                $(this).remove();
            });
        });

        setTimeout(function() {
            $(modalSelector + ' .modal-notice').fadeOut(function() {
                $(this).remove();
            });
        }, 3000);
    }

    $(document).on('click', '.retry-single-email', function(e) {
        e.preventDefault();
        const button = $(this);
        const email = button.data('email');
        const eventId = button.data('event-id');
        const nonce = button.data('nonce');

        button.prop('disabled', true)
            .find('.dashicons-update')
            .addClass('spinning');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'sct_retry_single_email',
                email: email,
                event_id: eventId,
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {

                    const listItem = button.closest('li');
                    listItem.removeClass('failed-status').addClass('success-status');
                    listItem.find('.dashicons-warning').removeClass('dashicons-warning dashicons-no').addClass('dashicons-yes-alt').css('color', '#46b450');
                    listItem.find('.status-label').text('Sent');
                    button.remove(); 

                    showNoticeInModal('Email resent successfully to ' + email, 'success', '#recipients-modal');
                } else {
                    button.prop('disabled', false)
                        .find('.dashicons-update')
                        .removeClass('spinning');
                    showNoticeInModal(response.data.message || 'Failed to resend email', 'error', '#recipients-modal');
                }
            },
            error: function() {
                button.prop('disabled', false)
                    .find('.dashicons-update')
                    .removeClass('spinning');
                showNoticeInModal('Server error occurred while retrying email.', 'error', '#recipients-modal');
            }
        });
    });

    function calculateTotals(table) {
        let totalGuests = 0;
        let totalPrice = 0;

        table.find('.total-pricing-option').text(0);
        table.find('.total-goods-service').text(0);

        table.find('tbody tr').each(function () {
            const row = $(this);
            let rowGuestCount = 0;

            row.find('.pricing-option-guest-count').each(function () {
                const count = parseInt($(this).val(), 10) || 0;
                rowGuestCount += count;

                const index = $(this).data('pricing-index');
                const columnTotalCell = table.find(`.total-pricing-option[data-pricing-index="${index}"]`);
                const currentTotal = parseInt(columnTotalCell.text(), 10) || 0;
                columnTotalCell.text(currentTotal + count);
            });

            row.find('.goods-service-count').each(function () {
                const count = parseInt($(this).val(), 10) || 0;
                rowGuestCount += count;

                const index = $(this).data('service-index');
                const columnTotalCell = table.find(`.total-goods-service[data-service-index="${index}"]`);
                const currentTotal = parseInt(columnTotalCell.text(), 10) || 0;
                columnTotalCell.text(currentTotal + count);
            });

            if (rowGuestCount === 0) {
                rowGuestCount = parseInt(row.find('.total-guests').text(), 10) || 0;
            }

            totalGuests += rowGuestCount;
        });

        table.find('.event-total-guests').text(totalGuests);
    }

    $('.wp-list-table.registrations-table').each(function() { 
        calculateTotals($(this));
    });

    $(document).on('change input', '.member-guest-count, .non-member-guest-count, .children-guest-count, .pricing-option-guest-count, .simple-guest-count', function() {
        var table = $(this).closest('.wp-list-table');
        calculateTotals(table);
    });

    if ($('body').hasClass('sct_event-administration_page_event-registrations') ||
        $('body').hasClass('sct_event-administration_page_event-past') ||
        $('body').hasClass('sct_event-administration_page_event-admin')) { 

    }

    $('#accordion').accordion({
        collapsible: true,
        active: false,
        heightStyle: "content"
    });

    $(document).on('click', '.remove-pricing-option', function () {
        $(this).closest('.pricing-option').remove();

        $('#pricing-options-container .pricing-option').each(function (index) {
            $(this).find('input[name^="pricing_options"]').each(function () {
                const name = $(this).attr('name');

                const updatedName = name.replace(/\[\d+\]/, `[${index}]`);
                $(this).attr('name', updatedName);
            });
        });
    });


    

    $(document).on('click', '.update-simple-guest-count', function() {
        var button = $(this);
        var row = button.closest('tr');
        var input = row.find('.simple-guest-count');

        var registrationId = button.data('registration-id');
        var newGuestCount = parseInt(input.val(), 10);

        if (isNaN(newGuestCount) || newGuestCount < 1) {
            showAdminNotice('Please enter a valid number (1 or more).', 'warning');
            input.val(input.data('original')); 
            button.hide();
            return;
        }

        $.ajax({
            url: eventAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'update_registration_guest_count',
                registration_id: registrationId,
                guest_count: newGuestCount,
                security: eventAdmin.update_registration_nonce
            },
            beforeSend: function() {
                button.prop('disabled', true);
            },
            success: function(response) {
                if (response.success) {
                    input.data('original', newGuestCount); 
                    button.hide(); 

                    var successIcon = $('<span class="dashicons dashicons-yes" style="color: green; margin-left: 5px;"></span>');
                    button.after(successIcon);
                    setTimeout(function() {
                        successIcon.fadeOut(function() { $(this).remove(); });
                    }, 2000);

                    calculateTotals(button.closest('.wp-list-table'));
                } else {
                    showAdminNotice('Error: ' + (response.data ? response.data.message : 'Unknown error'), 'error');
                    input.val(input.data('original')); 
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                showAdminNotice('Error updating guest count: ' + error, 'error');
                input.val(input.data('original')); 
            },
            complete: function() {
                button.prop('disabled', false);
                if (input.val() == input.data('original')) {
                    button.hide();
                }
            }
        });
    });

    $('.add-registration').on('click', function () {
        const button = $(this);
        const eventId = button.data('event-id');
        const pricingOptions = button.data('pricing-options'); 
        const goodsServices = button.data('goods-services'); 
        const modal = $('#add-registration-modal');
        const form = modal.find('#add-registration-form');

        form[0].reset();
        form.find('#add_registration_pricing_options_container').empty(); 
        form.find('#add_registration_goods_services_container').empty(); 
        form.find('#add_registration_guest_count_field').hide(); 

        form.find('input[name="event_id"]').val(eventId);

        const pricingContainer = form.find('#add_registration_pricing_options_container');
        const goodsServicesContainer = form.find('#add_registration_goods_services_container');
        const basicGuestCountField = form.find('#add_registration_guest_count_field');

        if (pricingOptions && pricingOptions.length > 0) {
            pricingOptions.forEach((option, index) => {
                pricingContainer.append(`
                    <div class="pricing-option">
                        <label>${option.name} (${option.price}):</label>
                        <input type="number" name="guest_details[${index}][count]" min="0" value="0">
                        <input type="hidden" name="guest_details[${index}][name]" value="${option.name}">
                        <input type="hidden" name="guest_details[${index}][price]" value="${option.price}">
                    </div>
                `);
            });
            pricingContainer.show();
            basicGuestCountField.hide();
        } else {

            pricingContainer.hide();
            basicGuestCountField.show();
        }

        if (goodsServices && goodsServices.length > 0) {
            goodsServices.forEach((service, index) => {
                goodsServicesContainer.append(`
                    <div class="goods-service-option">
                        <label>${service.name} (${service.price}):</label>
                        <input type="number" name="goods_services[${index}][count]" min="0" value="0">
                        <input type="hidden" name="goods_services[${index}][name]" value="${service.name}">
                        <input type="hidden" name="goods_services[${index}][price]" value="${service.price}">
                    </div>
                `);
            });
        }

        modal.show();
    });

    $(document).on('submit', '#add-registration-form', function (e) {
        e.preventDefault(); 

        const form = $(this);
        const submitButton = form.find('button[type="submit"]');
        const originalButtonText = submitButton.text();

        submitButton.prop('disabled', true).text('Adding...');

        $.ajax({
            url: eventAdmin.ajaxurl, 
            type: 'POST',
            data: form.serialize() + '&security=' + eventAdmin.update_registration_nonce, 
            success: function (response) {
                if (response.success) {
                    showAdminNotice('Registration added successfully.', 'success');
                    $('#add-registration-modal').hide(); 
                    location.reload(); 
                } else {
                    showAdminNotice('Error: ' + (response.data ? response.data.message : 'Unknown error'), 'error');
                }
            },
            error: function (xhr, status, error) {
                showAdminNotice('Error adding registration: ' + error, 'error');
            },
            complete: function () {
                submitButton.prop('disabled', false).text(originalButtonText);
            }
        });
    });

    $(document).on('click', '.select-winners', function() {
        var eventId = $(this).data('event-id');
        $('#select_winners_event_id').val(eventId);
        $('#select-winners-modal').show();
    });

    $('#select-winners-form').on('submit', function(e) {
        e.preventDefault();

        var form = $(this);
        var submitButton = form.find('button[type="submit"]');
        var originalButtonText = submitButton.text();

        submitButton.prop('disabled', true).text('Selecting...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: form.serialize() + '&security=' + eventAdmin.select_winners_nonce, 
            success: function(response) {
                if (response.success) {
                    showAdminNotice('Winners selected successfully. Refreshing...', 'success');
                    $('#select-winners-modal').hide();
                    location.reload();
                } else {
                    showAdminNotice('Error: ' + (response.data.message || 'Could not select winners.'), 'error');
                }
            },
            error: function(xhr, status, error) {
                showAdminNotice('Error selecting winners: ' + error, 'error');
            },
            complete: function() {
                submitButton.prop('disabled', false).text(originalButtonText);
            }
        });
    });

    $('#select-winners-modal .close-modal, #select-winners-modal .cancel-selection').on('click', function() {
        $(this).closest('.modal').hide(); 
    });

    $('#export-custom-tables').on('click', function(e) {
        e.preventDefault();
        var button = $(this);
        button.prop('disabled', true).text('Exporting...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'export_custom_tables',
                security: eventAdmin.export_nonce 
            },
            success: function(response) {
                if (response.success && response.data.url) {

                    var downloadLink = document.createElement('a');
                    downloadLink.href = response.data.url;
                    downloadLink.download = response.data.filename || 'custom_tables_export.sql'; 
                    document.body.appendChild(downloadLink);
                    downloadLink.click();
                    document.body.removeChild(downloadLink);
                    showAdminNotice('Export file generated successfully.', 'success');
                } else {

                    showAdminNotice('Failed to export tables: ' + (response.data.message || 'Unknown error'), 'error');
                }
            },
            error: function(xhr, status, error) {

                showAdminNotice('Error occurred while exporting tables. Please try again.', 'error');
                console.error('AJAX Error:', status, error);
            },
            complete: function() {
                button.prop('disabled', false).text('Export Custom Tables');
            }
        });
    });

    $('#event-form').on('submit', function (e) {
        e.preventDefault();

        const form = $(this);
        const submitButton = form.find('input[type="submit"], button[type="submit"]'); 
        const originalButtonText = submitButton.val() || submitButton.text(); 

        submitButton.prop('disabled', true).val('Saving...').text('Saving...'); 

        $.ajax({
            url: eventAdmin.ajaxurl, 
            type: 'POST',
            data: form.serialize(), 
            success: function (response) {
                if (response.success) {
                    showAdminNotice('Event saved successfully.', 'success');

                } else {
                    showAdminNotice('Error: ' + (response.data ? response.data.message : 'Unknown error'), 'error');
                }
            },
            error: function (xhr, status, error) {
                showAdminNotice('Error saving event: ' + error, 'error');
            },
            complete: function () {
                submitButton.prop('disabled', false).val(originalButtonText).text(originalButtonText); 
            }
        });
    });

    $(document).on('click', '.update-guest-counts', function () {
        const button = $(this);
        const row = button.closest('tr');
        const registrationId = row.data('registration-id'); 
        const nonce = eventAdmin.update_registration_nonce; 

        let data = {
            action: 'update_registration_guest_counts',
            registration_id: registrationId,
            security: nonce,
            guest_details: [],
            goods_services: []
        };

        const pricingOptionInputs = row.find('.pricing-option-guest-count');
        pricingOptionInputs.each(function () {
            const input = $(this);
            const index = input.data('pricing-index');
            const count = parseInt(input.val(), 10) || 0;
            const name = input.closest('td').data('name'); 
            const price = parseFloat(input.closest('td').data('price')) || 0;

            data.guest_details.push({
                count: count,
                name: name,
                price: price
            });
        });

        const goodsServiceInputs = row.find('.goods-service-count');
        goodsServiceInputs.each(function () {
            const input = $(this);
            const index = input.data('service-index');
            const count = parseInt(input.val(), 10) || 0;
            const name = input.closest('td').data('name'); 
            const price = parseFloat(input.closest('td').data('price')) || 0;

            data.goods_services.push({
                count: count,
                name: name,
                price: price
            });
        });

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: data,
            beforeSend: function () {
                button.prop('disabled', true).text('Updating...');
            },
            success: function (response) {
                if (response.success) {

                    pricingOptionInputs.each(function () {
                        $(this).data('original', $(this).val());
                    });
                    goodsServiceInputs.each(function () {
                        $(this).data('original', $(this).val());
                    });

                    button.hide(); 
                    showAdminNotice('Guest counts updated successfully.', 'success');
                } else {
                    showAdminNotice('Error: ' + (response.data ? response.data.message : 'Unknown error'), 'error');
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error:', status, error);
                showAdminNotice('Error updating guest counts. Please try again.', 'error');
            },
            complete: function () {
                button.prop('disabled', false).text('Update');
            }
        });
    });

    function formatCurrency(amount, currencySymbol, currencyFormat) {
        const decimalSeparator = '.';
        const thousandSeparator = ',';

        let formattedAmount = parseFloat(amount).toFixed(currencyFormat);
        let parts = formattedAmount.split('.');
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, thousandSeparator);
        formattedAmount = parts.join(decimalSeparator);

        return currencySymbol + formattedAmount;
    }

    function updateRowTotalPrice(row) {
        let totalPrice = 0;

        const currencySymbol = $('#currency-symbol').data('currency-symbol') || '';
        const currencyFormat = $('#currency-format').data('currency-format') === 0 ? 0 : parseInt($('#currency-format').data('currency-format'), 10) || 2;

        row.find('.pricing-option-guest-count').each(function () {
            const count = parseInt($(this).val(), 10) || 0;
            const price = parseFloat($(this).closest('td').data('price')) || 0;
            totalPrice += count * price;
        });

        row.find('.goods-service-count').each(function () {
            const count = parseInt($(this).val(), 10) || 0;
            const price = parseFloat($(this).closest('td').data('price')) || 0;
            totalPrice += count * price;
        });

        row.find('.total-price').text(formatCurrency(totalPrice, currencySymbol, currencyFormat));
    }

    $('.wp-list-table tbody tr').each(function () {
        updateRowTotalPrice($(this));
    });

    $('.pricing-option-guest-count, .goods-service-count').on('input', function () {
        const row = $(this).closest('tr');
        const table = row.closest('.wp-list-table'); 
        updateRowTotalPrice(row); 
        updateTableTotals(table); 
    });

    $(document).on('click', '.remove-goods-service-option', function () {
        $(this).closest('.goods-service-option').remove();
    });

    $('.wp-list-table tbody tr').each(function () {
        updateRowTotalPrice($(this));
    });

    function updateTableTotals(table) {
        let totalGuests = 0;
        let totalPrice = 0;
        const currencySymbol = $('#currency-symbol').data('currency-symbol') || '$';
        const currencyFormat = $('#currency-format').data('currency-format') === 0 ? 0 : parseInt($('#currency-format').data('currency-format'), 10) || 2;

        table.find('.total-pricing-option').text(0);
        table.find('.total-goods-service').text(0);

        if (table.find('.simple-guest-count').length != 0) {
            table.find('.simple-guest-count').each(function () {
                totalGuests += parseInt($(this).val());
            });
        }

        table.find('.pricing-option-guest-count').each(function () {
            const input = $(this);
            const row = input.closest('tr');
            const index = input.data('pricing-index');
            const count = parseInt(input.val(), 10) || 0;

            const columnTotalCell = table.find(`.total-pricing-option[data-pricing-index="${index}"]`);
            const currentTotal = parseInt(columnTotalCell.text(), 10) || 0;
            columnTotalCell.text(currentTotal + count);

            totalGuests += count;
        });

        table.find('.goods-service-count').each(function () {
            const input = $(this);
            const row = input.closest('tr');
            const index = input.data('service-index');
            const count = parseInt(input.val(), 10) || 0;

            const columnTotalCell = table.find(`.total-goods-service[data-service-index="${index}"]`);
            const currentTotal = parseInt(columnTotalCell.text(), 10) || 0;
            columnTotalCell.text(currentTotal + count);

        });

        table.find('tbody tr').each(function () {
            const row = $(this);
            const rowTotalPrice = parseFloat(row.find('.total-price').text().replace(/[^0-9.-]+/g, '')) || 0;
            totalPrice += rowTotalPrice;
        });

        table.find('.event-total-guests').text(totalGuests);
        table.find('.event-total-price').text(formatCurrency(totalPrice, currencySymbol, currencyFormat));
    }

    $(document).on('input', '.pricing-option-guest-count, .goods-service-count, .simple-guest-count', function () {
        const row = $(this).closest('tr');
        updateRowTotalPrice(row); 
        updateTableTotals(row.closest('.wp-list-table')); 
    });

    $(document).ready(function () {
        $('.wp-list-table').each(function () {
            const table = $(this);
            table.find('tbody tr').each(function () {
                updateRowTotalPrice($(this)); 
            });
            updateTableTotals(table); 
        });
    });

    $('#add-payment-method').on('click', function () {
        const index = $('#payment-methods-list .payment-method').length;
        $('#payment-methods-list').append(`
            <div class="payment-method">
                <select name="payment_methods[${index}][type]" required>
                    <option value="online">Online</option>
                    <option value="transfer">Transfer</option>
                    <option value="cash">Cash</option>
                </select>
                <input type="text" name="payment_methods[${index}][description]" placeholder="Description" required>
                <input type="url" name="payment_methods[${index}][link]" placeholder="Payment Link (for online)">
                <textarea name="payment_methods[${index}][transfer_details]" placeholder="Transfer Details (for transfer)" rows="5" required></textarea>
                <button type="button" class="remove-payment-method button">Remove</button>
            </div>
        `);
    });

    $(document).on('click', '.remove-payment-method', function () {
        $(this).closest('.payment-method').remove();
    });

    function togglePaymentMethods() {
        const pricingOptionsCount = $('#pricing-options-container .pricing-option').length;
        const goodsServicesCount = $('#goods-services-container .goods-service-option').length;

        if (pricingOptionsCount > 0 || goodsServicesCount > 0) {
            $('#payment-methods-container').show();
        } else {
            $('#payment-methods-container').hide();
            clearPaymentMethods(); 
        }
    }

    function clearPaymentMethods() {
        $('#payment-methods-list').empty(); 
    }

    $(document).on('click', '#add-pricing-option', function () {
        const index = $('#pricing-options-container .pricing-option').length;
        $('#pricing-options-container').append(`
            <div class="pricing-option">
                <input type="text" name="pricing_options[${index}][name]" placeholder="Category Name" required>
                <input type="number" name="pricing_options[${index}][price]" placeholder="Price (e.g., 10.50)" step="0.01" min="0" required>
                <button type="button" class="remove-pricing-option button">Remove</button>
            </div>
        `);
        togglePaymentMethods(); 
    });

    $(document).on('click', '.remove-pricing-option', function () {
        $(this).closest('.pricing-option').remove();
        reindexPricingOptions(); 
        togglePaymentMethods(); 
    });

    $(document).on('click', '#add-goods-service-option', function () {
        const index = $('#goods-services-container .goods-service-option').length;
        $('#goods-services-container').append(`
            <div class="goods-service-option">
                <input type="text" name="goods_services[${index}][name]" placeholder="Item Name" required>
                <input type="number" name="goods_services[${index}][price]" placeholder="Price (e.g., 10.50)" step="0.01" min="0" required>
                <input type="number" name="goods_services[${index}][limit]" placeholder="Limit (0 for unlimited)" min="0">
                <button type="button" class="remove-goods-service-option button">Remove</button>
            </div>
        `);
        togglePaymentMethods(); 
    });

    $(document).on('click', '.remove-goods-service-option', function () {
        $(this).closest('.goods-service-option').remove();
        reindexGoodsServices(); 
        togglePaymentMethods(); 
    });

    function reindexPricingOptions() {
        $('#pricing-options-container .pricing-option').each(function (index) {
            $(this).find('input[name^="pricing_options"]').each(function () {
                const name = $(this).attr('name');
                const updatedName = name.replace(/\[\d+\]/, `[${index}]`);
                $(this).attr('name', updatedName);
            });
        });
    }

    function reindexGoodsServices() {
        $('#goods-services-container .goods-service-option').each(function (index) {
            $(this).find('input[name^="goods_services"]').each(function () {
                const name = $(this).attr('name');
                const updatedName = name.replace(/\[\d+\]/, `[${index}]`);
                $(this).attr('name', updatedName);
            });
        });
    }

    togglePaymentMethods();

    $(document).on('change', '.change-payment-status', function () {
        const select = $(this);
        const registrationId = select.data('registration-id');
        const nonce = select.data('nonce');
        const newStatus = select.val();

        select.prop('disabled', true);

        $.ajax({
            url: eventAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'update_payment_status',
                registration_id: registrationId,
                payment_status: newStatus,
                security: nonce
            },
            success: function (response) {
                if (response.success) {
                    showAdminNotice('Payment status updated successfully.', 'success');
                } else {
                    showAdminNotice(response.data.message || 'Error updating payment status.', 'error');
                }
            },
            error: function () {
                showAdminNotice('Error updating payment status.', 'error');
            },
            complete: function () {

                select.prop('disabled', false);
            }
        });
    });

    $(document).on('click', '.view-registration-details', function () {
        const button = $(this);
        const registrationId = button.data('registration-id');
        const nonce = button.data('nonce');
        const modal = $('#registration-details-modal');
        const content = $('#registration-details-content');

        content.html('<p>Loading...</p>');

        modal.show();

        $.ajax({
            url: eventAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'view_registration_details',
                registration_id: registrationId,
                security: nonce
            },
            success: function (response) {
                if (response.success) {
                    content.html(response.data.html);
                } else {
                    content.html('<p>Error loading registration details.</p>');
                }
            },
            error: function () {
                content.html('<p>Error loading registration details.</p>');
            }
        });
    });

    $('#accordion').accordion({
        collapsible: true,
        active: false,
        heightStyle: "content"
    });

    $(document).on('click', '.remove-pricing-option', function () {
        $(this).closest('.pricing-option').remove();

        $('#pricing-options-container .pricing-option').each(function (index) {
            $(this).find('input[name^="pricing_options"]').each(function () {
                const name = $(this).attr('name');

                const updatedName = name.replace(/\[\d+\]/, `[${index}]`);
                $(this).attr('name', updatedName);
            });
        });
    });


    $(document).on('click', '.update-simple-guest-count', function() {
        var button = $(this);
        var row = button.closest('tr');
        var input = row.find('.simple-guest-count');

        var registrationId = button.data('registration-id');
        var newGuestCount = parseInt(input.val(), 10);

        if (isNaN(newGuestCount) || newGuestCount < 1) {
            showAdminNotice('Please enter a valid number (1 or more).', 'warning');
            input.val(input.data('original')); 
            button.hide();
            return;
        }

        $.ajax({
            url: eventAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'update_registration_guest_count',
                registration_id: registrationId,
                guest_count: newGuestCount,
                security: eventAdmin.update_registration_nonce
            },
            beforeSend: function() {
                button.prop('disabled', true);
            },
            success: function(response) {
                if (response.success) {
                    input.data('original', newGuestCount); 
                    button.hide(); 

                    var successIcon = $('<span class="dashicons dashicons-yes" style="color: green; margin-left: 5px;"></span>');
                    button.after(successIcon);
                    setTimeout(function() {
                        successIcon.fadeOut(function() { $(this).remove(); });
                    }, 2000);

                    calculateTotals(button.closest('.wp-list-table'));
                } else {
                    showAdminNotice('Error: ' + (response.data ? response.data.message : 'Unknown error'), 'error');
                    input.val(input.data('original')); 
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                showAdminNotice('Error updating guest count: ' + error, 'error');
                input.val(input.data('original')); 
            },
            complete: function() {
                button.prop('disabled', false);
                if (input.val() == input.data('original')) {
                    button.hide();
                }
            }
        });
    });

    $('.add-registration').on('click', function () {
        const button = $(this);
        const eventId = button.data('event-id');
        const pricingOptions = button.data('pricing-options'); 
        const goodsServices = button.data('goods-services'); 
        const modal = $('#add-registration-modal');
        const form = modal.find('#add-registration-form');

        form[0].reset();
        form.find('#add_registration_pricing_options_container').empty(); 
        form.find('#add_registration_goods_services_container').empty(); 
        form.find('#add_registration_guest_count_field').hide(); 

        form.find('input[name="event_id"]').val(eventId);

        const pricingContainer = form.find('#add_registration_pricing_options_container');
        const goodsServicesContainer = form.find('#add_registration_goods_services_container');
        const basicGuestCountField = form.find('#add_registration_guest_count_field');

        if (pricingOptions && pricingOptions.length > 0) {
            pricingOptions.forEach((option, index) => {
                pricingContainer.append(`
                    <div class="pricing-option">
                        <label>${option.name} (${option.price}):</label>
                        <input type="number" name="guest_details[${index}][count]" min="0" value="0">
                        <input type="hidden" name="guest_details[${index}][name]" value="${option.name}">
                        <input type="hidden" name="guest_details[${index}][price]" value="${option.price}">
                    </div>
                `);
            });
            pricingContainer.show();
            basicGuestCountField.hide();
        } else {

            pricingContainer.hide();
            basicGuestCountField.show();
        }

        if (goodsServices && goodsServices.length > 0) {
            goodsServices.forEach((service, index) => {
                goodsServicesContainer.append(`
                    <div class="goods-service-option">
                        <label>${service.name} (${service.price}):</label>
                        <input type="number" name="goods_services[${index}][count]" min="0" value="0">
                        <input type="hidden" name="goods_services[${index}][name]" value="${service.name}">
                        <input type="hidden" name="goods_services[${index}][price]" value="${service.price}">
                    </div>
                `);
            });
        }

        modal.show();
    });

    $(document).on('submit', '#add-registration-form', function (e) {
        e.preventDefault(); 

        const form = $(this);
        const submitButton = form.find('button[type="submit"]');
        const originalButtonText = submitButton.text();

        submitButton.prop('disabled', true).text('Adding...');

        $.ajax({
            url: eventAdmin.ajaxurl, 
            type: 'POST',
            data: form.serialize() + '&security=' + eventAdmin.update_registration_nonce, 
            success: function (response) {
                if (response.success) {
                    showAdminNotice('Registration added successfully.', 'success');
                    $('#add-registration-modal').hide(); 
                    location.reload(); 
                } else {
                    showAdminNotice('Error: ' + (response.data ? response.data.message : 'Unknown error'), 'error');
                }
            },
            error: function (xhr, status, error) {
                showAdminNotice('Error adding registration: ' + error, 'error');
            },
            complete: function () {
                submitButton.prop('disabled', false).text(originalButtonText);
            }
        });
    });

    $(document).on('click', '#add-registration-modal .cancel-registration', function() {
        $(this).closest('.modal').hide();
    });

    $(document).on('click', '.select-winners', function() {
        var eventId = $(this).data('event-id');
        $('#select_winners_event_id').val(eventId);
        $('#select-winners-modal').show();
    });

    $('#select-winners-form').on('submit', function(e) {
        e.preventDefault();

        var form = $(this);
        var submitButton = form.find('button[type="submit"]');
        var originalButtonText = submitButton.text();

        submitButton.prop('disabled', true).text('Selecting...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: form.serialize() + '&security=' + eventAdmin.select_winners_nonce, 
            success: function(response) {
                if (response.success) {
                    showAdminNotice('Winners selected successfully. Refreshing...', 'success');
                    $('#select-winners-modal').hide();
                    location.reload();
                } else {
                    showAdminNotice('Error: ' + (response.data.message || 'Could not select winners.'), 'error');
                }
            },
            error: function(xhr, status, error) {
                showAdminNotice('Error selecting winners: ' + error, 'error');
            },
            complete: function() {
                submitButton.prop('disabled', false).text(originalButtonText);
            }
        });
    });

    $('#select-winners-modal .close-modal, #select-winners-modal .cancel-selection').on('click', function() {
        $(this).closest('.modal').hide(); 
    });

    $('#export-custom-tables').on('click', function(e) {
        e.preventDefault();
        var button = $(this);
        button.prop('disabled', true).text('Exporting...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'export_custom_tables',
                security: eventAdmin.export_nonce 
            },
            success: function(response) {
                if (response.success && response.data.url) {

                    var downloadLink = document.createElement('a');
                    downloadLink.href = response.data.url;
                    downloadLink.download = response.data.filename || 'custom_tables_export.sql'; 
                    document.body.appendChild(downloadLink);
                    downloadLink.click();
                    document.body.removeChild(downloadLink);
                    showAdminNotice('Export file generated successfully.', 'success');
                } else {

                    showAdminNotice('Failed to export tables: ' + (response.data.message || 'Unknown error'), 'error');
                }
            },
            error: function(xhr, status, error) {

                showAdminNotice('Error occurred while exporting tables. Please try again.', 'error');
                console.error('AJAX Error:', status, error);
            },
            complete: function() {
                button.prop('disabled', false).text('Export Custom Tables');
            }
        });
    });

    $('#event-form').on('submit', function (e) {
        e.preventDefault();

        const form = $(this);
        const submitButton = form.find('input[type="submit"], button[type="submit"]'); 
        const originalButtonText = submitButton.val() || submitButton.text(); 

        submitButton.prop('disabled', true).val('Saving...').text('Saving...'); 

        $.ajax({
            url: eventAdmin.ajaxurl, 
            type: 'POST',
            data: form.serialize(), 
            success: function (response) {
                if (response.success) {
                    showAdminNotice('Event saved successfully.', 'success');

                } else {
                    showAdminNotice('Error: ' + (response.data ? response.data.message : 'Unknown error'), 'error');
                }
            },
            error: function (xhr, status, error) {
                showAdminNotice('Error saving event: ' + error, 'error');
            },
            complete: function () {
                submitButton.prop('disabled', false).val(originalButtonText).text(originalButtonText); 
            }
        });
    });

    $(document).on('click', '.update-guest-counts', function () {
        const button = $(this);
        const row = button.closest('tr');
        const registrationId = row.data('registration-id'); 
        const nonce = eventAdmin.update_registration_nonce; 

        let data = {
            action: 'update_registration_guest_counts',
            registration_id: registrationId,
            security: nonce,
            guest_details: [],
            goods_services: []
        };

        const pricingOptionInputs = row.find('.pricing-option-guest-count');
        pricingOptionInputs.each(function () {
            const input = $(this);
            const index = input.data('pricing-index');
            const count = parseInt(input.val(), 10) || 0;
            const name = input.closest('td').data('name'); 
            const price = parseFloat(input.closest('td').data('price')) || 0;

            data.guest_details.push({
                count: count,
                name: name,
                price: price
            });
        });

        const goodsServiceInputs = row.find('.goods-service-count');
        goodsServiceInputs.each(function () {
            const input = $(this);
            const index = input.data('service-index');
            const count = parseInt(input.val(), 10) || 0;
            const name = input.closest('td').data('name'); 
            const price = parseFloat(input.closest('td').data('price')) || 0;

            data.goods_services.push({
                count: count,
                name: name,
                price: price
            });
        });

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: data,
            beforeSend: function () {
                button.prop('disabled', true).text('Updating...');
            },
            success: function (response) {
                if (response.success) {

                    pricingOptionInputs.each(function () {
                        $(this).data('original', $(this).val());
                    });
                    goodsServiceInputs.each(function () {
                        $(this).data('original', $(this).val());
                    });

                    button.hide(); 
                    showAdminNotice('Guest counts updated successfully.', 'success');
                } else {
                    showAdminNotice('Error: ' + (response.data ? response.data.message : 'Unknown error'), 'error');
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error:', status, error);
                showAdminNotice('Error updating guest counts. Please try again.', 'error');
            },
            complete: function () {
                button.prop('disabled', false).text('Update');
            }
        });
    });





    $('.wp-list-table tbody tr').each(function () {
        updateRowTotalPrice($(this));
    });

    $('.pricing-option-guest-count, .goods-service-count').on('input', function () {
        const row = $(this).closest('tr');
        const table = row.closest('.wp-list-table'); 
        updateRowTotalPrice(row); 
        updateTableTotals(table); 
    });

    $(document).on('click', '.remove-goods-service-option', function () {
        $(this).closest('.goods-service-option').remove();
    });

    $(document).on('click', '.close-modal', function () {
        $(this).closest('.email-modal').hide();
    });

    $(document).on('click', '.cancel-registration', function () {
        $('#add-registration-modal').hide();
    });

    $('.wp-list-table tbody tr').each(function () {
        updateRowTotalPrice($(this));
    });


    $(document).on('input', '.pricing-option-guest-count, .goods-service-count, .simple-guest-count', function () {
        const row = $(this).closest('tr');
        updateRowTotalPrice(row); 
        updateTableTotals(row.closest('.wp-list-table')); 
    });

    $(document).ready(function () {
        $('.wp-list-table').each(function () {
            const table = $(this);
            table.find('tbody tr').each(function () {
                updateRowTotalPrice($(this)); 
            });
            updateTableTotals(table); 
        });
    });

    $('#add-payment-method').on('click', function () {
        const index = $('#payment-methods-list .payment-method').length;
        $('#payment-methods-list').append(`
            <div class="payment-method">
                <select name="payment_methods[${index}][type]" required>
                    <option value="online">Online</option>
                    <option value="transfer">Transfer</option>
                    <option value="cash">Cash</option>
                </select>
                <input type="text" name="payment_methods[${index}][description]" placeholder="Description" required>
                <input type="url" name="payment_methods[${index}][link]" placeholder="Payment Link (for online)">
                <textarea name="payment_methods[${index}][transfer_details]" placeholder="Transfer Details (for transfer)" rows="5" required></textarea>
                <button type="button" class="remove-payment-method button">Remove</button>
            </div>
        `);
    });

    $(document).on('click', '.remove-payment-method', function () {
        $(this).closest('.payment-method').remove();
    });





    $(document).on('click', '#add-pricing-option', function () {
        const index = $('#pricing-options-container .pricing-option').length;
        $('#pricing-options-container').append(`
            <div class="pricing-option">
                <input type="text" name="pricing_options[${index}][name]" placeholder="Category Name" required>
                <input type="number" name="pricing_options[${index}][price]" placeholder="Price (e.g., 10.50)" step="0.01" min="0" required>
                <button type="button" class="remove-pricing-option button">Remove</button>
            </div>
        `);
        togglePaymentMethods(); 
    });

    $(document).on('click', '.remove-pricing-option', function () {
        $(this).closest('.pricing-option').remove();
        reindexPricingOptions(); 
        togglePaymentMethods(); 
    });

    $(document).on('click', '#add-goods-service-option', function () {
        const index = $('#goods-services-container .goods-service-option').length;
        $('#goods-services-container').append(`
            <div class="goods-service-option">
                <input type="text" name="goods_services[${index}][name]" placeholder="Item Name" required>
                <input type="number" name="goods_services[${index}][price]" placeholder="Price (e.g., 10.50)" step="0.01" min="0" required>
                <input type="number" name="goods_services[${index}][limit]" placeholder="Limit (0 for unlimited)" min="0">
                <button type="button" class="remove-goods-service-option button">Remove</button>
            </div>
        `);
        togglePaymentMethods(); 
    });

    $(document).on('click', '.remove-goods-service-option', function () {
        $(this).closest('.goods-service-option').remove();
        reindexGoodsServices(); 
        togglePaymentMethods(); 
    });


    togglePaymentMethods();

    $(document).on('change', '.change-payment-status', function () {
        const select = $(this);
        const registrationId = select.data('registration-id');
        const nonce = select.data('nonce');
        const newStatus = select.val();

        select.prop('disabled', true);

        $.ajax({
            url: eventAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'update_payment_status',
                registration_id: registrationId,
                payment_status: newStatus,
                security: nonce
            },
            success: function (response) {
                if (response.success) {
                    showAdminNotice('Payment status updated successfully.', 'success');
                } else {
                    showAdminNotice(response.data.message || 'Error updating payment status.', 'error');
                }
            },
            error: function () {
                showAdminNotice('Error updating payment status.', 'error');
            },
            complete: function () {

                select.prop('disabled', false);
            }
        });
    });



    $(document).on('click', '.close-modal', function () {
        $(this).closest('.modal').hide();
    });

    $(document).on('click', '.button.close-modal', function () {
        $(this).closest('.modal').hide();
    });

    $(document).on('click', '.modal', function (e) {
        if ($(e.target).is('.modal')) {
            e.stopPropagation(); 
        }
    });


    $('#accordion').accordion({
        collapsible: true,
        active: false,
        heightStyle: "content"
    });

    $(document).on('click', '.remove-pricing-option', function () {
        $(this).closest('.pricing-option').remove();

        $('#pricing-options-container .pricing-option').each(function (index) {
            $(this).find('input[name^="pricing_options"]').each(function () {
                const name = $(this).attr('name');

                const updatedName = name.replace(/\[\d+\]/, `[${index}]`);
                $(this).attr('name', updatedName);
            });
        });
    });

    

    $(document).on('click', '.update-simple-guest-count', function() {
        var button = $(this);
        var row = button.closest('tr');
        var input = row.find('.simple-guest-count');

        var registrationId = button.data('registration-id');
        var newGuestCount = parseInt(input.val(), 10);

        if (isNaN(newGuestCount) || newGuestCount < 1) {
            showAdminNotice('Please enter a valid number (1 or more).', 'warning');
            input.val(input.data('original')); 
            button.hide();
            return;
        }

        $.ajax({
            url: eventAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'update_registration_guest_count',
                registration_id: registrationId,
                guest_count: newGuestCount,
                security: eventAdmin.update_registration_nonce
            },
            beforeSend: function() {
                button.prop('disabled', true);
            },
            success: function(response) {
                if (response.success) {
                    input.data('original', newGuestCount); 
                    button.hide(); 

                    var successIcon = $('<span class="dashicons dashicons-yes" style="color: green; margin-left: 5px;"></span>');
                    button.after(successIcon);
                    setTimeout(function() {
                        successIcon.fadeOut(function() { $(this).remove(); });
                    }, 2000);

                    calculateTotals(button.closest('.wp-list-table'));
                } else {
                    showAdminNotice('Error: ' + (response.data ? response.data.message : 'Unknown error'), 'error');
                    input.val(input.data('original')); 
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                showAdminNotice('Error updating guest count: ' + error, 'error');
                input.val(input.data('original')); 
            },
            complete: function() {
                button.prop('disabled', false);
                if (input.val() == input.data('original')) {
                    button.hide();
                }
            }
        });
    });

    $('.add-registration').on('click', function () {
        const button = $(this);
        const eventId = button.data('event-id');
        const pricingOptions = button.data('pricing-options'); 
        const goodsServices = button.data('goods-services'); 
        const modal = $('#add-registration-modal');
        const form = modal.find('#add-registration-form');

        form[0].reset();
        form.find('#add_registration_pricing_options_container').empty(); 
        form.find('#add_registration_goods_services_container').empty(); 
        form.find('#add_registration_guest_count_field').hide(); 

        form.find('input[name="event_id"]').val(eventId);

        const pricingContainer = form.find('#add_registration_pricing_options_container');
        const goodsServicesContainer = form.find('#add_registration_goods_services_container');
        const basicGuestCountField = form.find('#add_registration_guest_count_field');

        if (pricingOptions && pricingOptions.length > 0) {
            pricingOptions.forEach((option, index) => {
                pricingContainer.append(`
                    <div class="pricing-option">
                        <label>${option.name} (${option.price}):</label>
                        <input type="number" name="guest_details[${index}][count]" min="0" value="0">
                        <input type="hidden" name="guest_details[${index}][name]" value="${option.name}">
                        <input type="hidden" name="guest_details[${index}][price]" value="${option.price}">
                    </div>
                `);
            });
            pricingContainer.show();
            basicGuestCountField.hide();
        } else {

            pricingContainer.hide();
            basicGuestCountField.show();
        }

        if (goodsServices && goodsServices.length > 0) {
            goodsServices.forEach((service, index) => {
                goodsServicesContainer.append(`
                    <div class="goods-service-option">
                        <label>${service.name} (${service.price}):</label>
                        <input type="number" name="goods_services[${index}][count]" min="0" value="0">
                        <input type="hidden" name="goods_services[${index}][name]" value="${service.name}">
                        <input type="hidden" name="goods_services[${index}][price]" value="${service.price}">
                    </div>
                `);
            });
        }

        modal.show();
    });

    $(document).on('submit', '#add-registration-form', function (e) {
        e.preventDefault(); 

        const form = $(this);
        const submitButton = form.find('button[type="submit"]');
        const originalButtonText = submitButton.text();

        submitButton.prop('disabled', true).text('Adding...');

        $.ajax({
            url: eventAdmin.ajaxurl, 
            type: 'POST',
            data: form.serialize() + '&security=' + eventAdmin.update_registration_nonce, 
            success: function (response) {
                if (response.success) {
                    showAdminNotice('Registration added successfully.', 'success');
                    $('#add-registration-modal').hide(); 
                    location.reload(); 
                } else {
                    showAdminNotice('Error: ' + (response.data ? response.data.message : 'Unknown error'), 'error');
                }
            },
            error: function (xhr, status, error) {
                showAdminNotice('Error adding registration: ' + error, 'error');
            },
            complete: function () {
                submitButton.prop('disabled', false).text(originalButtonText);
            }
        });
    });

    $(document).on('click', '#add-registration-modal .cancel-registration', function() {
        $(this).closest('.modal').hide();
    });

    $(document).on('click', '.select-winners', function() {
        var eventId = $(this).data('event-id');
        $('#select_winners_event_id').val(eventId);
        $('#select-winners-modal').show();
    });

    $('#select-winners-form').on('submit', function(e) {
        e.preventDefault();

        var form = $(this);
        var submitButton = form.find('button[type="submit"]');
        var originalButtonText = submitButton.text();

        submitButton.prop('disabled', true).text('Selecting...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: form.serialize() + '&security=' + eventAdmin.select_winners_nonce, 
            success: function(response) {
                if (response.success) {
                    showAdminNotice('Winners selected successfully. Refreshing...', 'success');
                    $('#select-winners-modal').hide();
                    location.reload();
                } else {
                    showAdminNotice('Error: ' + (response.data.message || 'Could not select winners.'), 'error');
                }
            },
            error: function(xhr, status, error) {
                showAdminNotice('Error selecting winners: ' + error, 'error');
            },
            complete: function() {
                submitButton.prop('disabled', false).text(originalButtonText);
            }
        });
    });

    $('#select-winners-modal .close-modal, #select-winners-modal .cancel-selection').on('click', function() {
        $(this).closest('.modal').hide(); 
    });

    $('#export-custom-tables').on('click', function(e) {
        e.preventDefault();
        var button = $(this);
        button.prop('disabled', true).text('Exporting...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'export_custom_tables',
                security: eventAdmin.export_nonce 
            },
            success: function(response) {
                if (response.success && response.data.url) {

                    var downloadLink = document.createElement('a');
                    downloadLink.href = response.data.url;
                    downloadLink.download = response.data.filename || 'custom_tables_export.sql'; 
                    document.body.appendChild(downloadLink);
                    downloadLink.click();
                    document.body.removeChild(downloadLink);
                    showAdminNotice('Export file generated successfully.', 'success');
                } else {

                    showAdminNotice('Failed to export tables: ' + (response.data.message || 'Unknown error'), 'error');
                }
            },
            error: function(xhr, status, error) {

                showAdminNotice('Error occurred while exporting tables. Please try again.', 'error');
                console.error('AJAX Error:', status, error);
            },
            complete: function() {
                button.prop('disabled', false).text('Export Custom Tables');
            }
        });
    });

    $('#event-form').on('submit', function (e) {
        e.preventDefault();

        const form = $(this);
        const submitButton = form.find('input[type="submit"], button[type="submit"]'); 
        const originalButtonText = submitButton.val() || submitButton.text(); 

        submitButton.prop('disabled', true).val('Saving...').text('Saving...'); 

        $.ajax({
            url: eventAdmin.ajaxurl, 
            type: 'POST',
            data: form.serialize(), 
            success: function (response) {
                if (response.success) {
                    showAdminNotice('Event saved successfully.', 'success');

                } else {
                    showAdminNotice('Error: ' + (response.data ? response.data.message : 'Unknown error'), 'error');
                }
            },
            error: function (xhr, status, error) {
                showAdminNotice('Error saving event: ' + error, 'error');
            },
            complete: function () {
                submitButton.prop('disabled', false).val(originalButtonText).text(originalButtonText); 
            }
        });
    });

    $(document).on('click', '.update-guest-counts', function () {
        const button = $(this);
        const row = button.closest('tr');
        const registrationId = row.data('registration-id'); 
        const nonce = eventAdmin.update_registration_nonce; 

        let data = {
            action: 'update_registration_guest_counts',
            registration_id: registrationId,
            security: nonce,
            guest_details: [],
            goods_services: []
        };

        const pricingOptionInputs = row.find('.pricing-option-guest-count');
        pricingOptionInputs.each(function () {
            const input = $(this);
            const index = input.data('pricing-index');
            const count = parseInt(input.val(), 10) || 0;
            const name = input.closest('td').data('name'); 
            const price = parseFloat(input.closest('td').data('price')) || 0;

            data.guest_details.push({
                count: count,
                name: name,
                price: price
            });
        });

        const goodsServiceInputs = row.find('.goods-service-count');
        goodsServiceInputs.each(function () {
            const input = $(this);
            const index = input.data('service-index');
            const count = parseInt(input.val(), 10) || 0;
            const name = input.closest('td').data('name'); 
            const price = parseFloat(input.closest('td').data('price')) || 0;

            data.goods_services.push({
                count: count,
                name: name,
                price: price
            });
        });

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: data,
            beforeSend: function () {
                button.prop('disabled', true).text('Updating...');
            },
            success: function (response) {
                if (response.success) {

                    pricingOptionInputs.each(function () {
                        $(this).data('original', $(this).val());
                    });
                    goodsServiceInputs.each(function () {
                        $(this).data('original', $(this).val());
                    });

                    button.hide(); 
                    showAdminNotice('Guest counts updated successfully.', 'success');
                } else {
                    showAdminNotice('Error: ' + (response.data ? response.data.message : 'Unknown error'), 'error');
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error:', status, error);
                showAdminNotice('Error updating guest counts. Please try again.', 'error');
            },
            complete: function () {
                button.prop('disabled', false).text('Update');
            }
        });
    });





    $('.wp-list-table tbody tr').each(function () {
        updateRowTotalPrice($(this));
    });

    $('.pricing-option-guest-count, .goods-service-count').on('input', function () {
        const row = $(this).closest('tr');
        const table = row.closest('.wp-list-table'); 
        updateRowTotalPrice(row); 
        updateTableTotals(table); 
    });

    $(document).on('click', '.remove-goods-service-option', function () {
        $(this).closest('.goods-service-option').remove();
    });

    $(document).on('click', '.close-modal', function () {
        $(this).closest('.email-modal').hide();
    });

    $(document).on('click', '.cancel-registration', function () {
        $('#add-registration-modal').hide();
    });

    $('.wp-list-table tbody tr').each(function () {
        updateRowTotalPrice($(this));
    });


    $(document).on('input', '.pricing-option-guest-count, .goods-service-count, .simple-guest-count', function () {
        const row = $(this).closest('tr');
        updateRowTotalPrice(row); 
        updateTableTotals(row.closest('.wp-list-table')); 
    });

    $(document).ready(function () {
        $('.wp-list-table').each(function () {
            const table = $(this);
            table.find('tbody tr').each(function () {
                updateRowTotalPrice($(this)); 
            });
            updateTableTotals(table); 
        });
    });

    $('#add-payment-method').on('click', function () {
        const index = $('#payment-methods-list .payment-method').length;
        $('#payment-methods-list').append(`
            <div class="payment-method">
                <select name="payment_methods[${index}][type]" required>
                    <option value="online">Online</option>
                    <option value="transfer">Transfer</option>
                    <option value="cash">Cash</option>
                </select>
                <input type="text" name="payment_methods[${index}][description]" placeholder="Description" required>
                <input type="url" name="payment_methods[${index}][link]" placeholder="Payment Link (for online)">
                <textarea name="payment_methods[${index}][transfer_details]" placeholder="Transfer Details (for transfer)" rows="5" required></textarea>
                <button type="button" class="remove-payment-method button">Remove</button>
            </div>
        `);
    });

    $(document).on('click', '.remove-payment-method', function () {
        $(this).closest('.payment-method').remove();
    });


    $(document).on('click', '#add-pricing-option', function () {
        const index = $('#pricing-options-container .pricing-option').length;
        $('#pricing-options-container').append(`
            <div class="pricing-option">
                <input type="text" name="pricing_options[${index}][name]" placeholder="Category Name" required>
                <input type="number" name="pricing_options[${index}][price]" placeholder="Price (e.g., 10.50)" step="0.01" min="0" required>
                <button type="button" class="remove-pricing-option button">Remove</button>
            </div>
        `);
        togglePaymentMethods(); 
    });

    $(document).on('click', '.remove-pricing-option', function () {
        $(this).closest('.pricing-option').remove();
        reindexPricingOptions(); 
        togglePaymentMethods(); 
    });

    $(document).on('click', '#add-goods-service-option', function () {
        const index = $('#goods-services-container .goods-service-option').length;
        $('#goods-services-container').append(`
            <div class="goods-service-option">
                <input type="text" name="goods_services[${index}][name]" placeholder="Item Name" required>
                <input type="number" name="goods_services[${index}][price]" placeholder="Price (e.g., 10.50)" step="0.01" min="0" required>
                <input type="number" name="goods_services[${index}][limit]" placeholder="Limit (0 for unlimited)" min="0">
                <button type="button" class="remove-goods-service-option button">Remove</button>
            </div>
        `);
        togglePaymentMethods(); 
    });

    $(document).on('click', '.remove-goods-service-option', function () {
        $(this).closest('.goods-service-option').remove();
        reindexGoodsServices(); 
        togglePaymentMethods(); 
    });



    togglePaymentMethods();

    $(document).on('change', '.change-payment-status', function () {
        const select = $(this);
        const registrationId = select.data('registration-id');
        const nonce = select.data('nonce');
        const newStatus = select.val();

        select.prop('disabled', true);

        $.ajax({
            url: eventAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'update_payment_status',
                registration_id: registrationId,
                payment_status: newStatus,
                security: nonce
            },
            success: function (response) {
                if (response.success) {
                    showAdminNotice('Payment status updated successfully.', 'success');
                } else {
                    showAdminNotice(response.data.message || 'Error updating payment status.', 'error');
                }
            },
            error: function () {
                showAdminNotice('Error updating payment status.', 'error');
            },
            complete: function () {

                select.prop('disabled', false);
            }
        });
    });



    $(document).on('click', '.close-modal', function () {
        $(this).closest('.modal').hide();
    });

    $(document).on('click', '.button.close-modal', function () {
        $(this).closest('.modal').hide();
    });

    $(document).on('click', '.modal', function (e) {
        if ($(e.target).is('.modal')) {
            e.stopPropagation(); 
        }
    });

    $(document).on('click', '#email-modal .placeholder-info code', function() {
        var placeholder = $(this).text() + ' ';
        var $emailBody = $('#email-modal #email_body');

        var textarea = $emailBody[0];
        if (textarea) {
            var startPos = textarea.selectionStart;
            var endPos = textarea.selectionEnd;
            var currentContent = $emailBody.val();
            var newContent = currentContent.substring(0, startPos) +
                             placeholder +
                             currentContent.substring(endPos);
            $emailBody.val(newContent);
            var newCursorPos = startPos + placeholder.length;
            textarea.setSelectionRange(newCursorPos, newCursorPos);
            $emailBody.focus();
        }
    });


    $('#accordion').accordion({
        collapsible: true,
        active: false,
        heightStyle: "content"
    });

    $(document).on('click', '.remove-pricing-option', function () {
        $(this).closest('.pricing-option').remove();

        $('#pricing-options-container .pricing-option').each(function (index) {
            $(this).find('input[name^="pricing_options"]').each(function () {
                const name = $(this).attr('name');

                const updatedName = name.replace(/\[\d+\]/, `[${index}]`);
                $(this).attr('name', updatedName);
            });
        });
    });

    

    $(document).on('click', '.update-simple-guest-count', function() {
        var button = $(this);
        var row = button.closest('tr');
        var input = row.find('.simple-guest-count');

        var registrationId = button.data('registration-id');
        var newGuestCount = parseInt(input.val(), 10);

        if (isNaN(newGuestCount) || newGuestCount < 1) {
            showAdminNotice('Please enter a valid number (1 or more).', 'warning');
            input.val(input.data('original')); 
            button.hide();
            return;
        }

        $.ajax({
            url: eventAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'update_registration_guest_count',
                registration_id: registrationId,
                guest_count: newGuestCount,
                security: eventAdmin.update_registration_nonce
            },
            beforeSend: function() {
                button.prop('disabled', true);
            },
            success: function(response) {
                if (response.success) {
                    input.data('original', newGuestCount); 
                    button.hide(); 

                    var successIcon = $('<span class="dashicons dashicons-yes" style="color: green; margin-left: 5px;"></span>');
                    button.after(successIcon);
                    setTimeout(function() {
                        successIcon.fadeOut(function() { $(this).remove(); });
                    }, 2000);

                    calculateTotals(button.closest('.wp-list-table'));
                } else {
                    showAdminNotice('Error: ' + (response.data ? response.data.message : 'Unknown error'), 'error');
                    input.val(input.data('original')); 
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                showAdminNotice('Error updating guest count: ' + error, 'error');
                input.val(input.data('original')); 
            },
            complete: function() {
                button.prop('disabled', false);
                if (input.val() == input.data('original')) {
                    button.hide();
                }
            }
        });
    });

    $('.add-registration').on('click', function () {
        const button = $(this);
        const eventId = button.data('event-id');
        const pricingOptions = button.data('pricing-options'); 
        const goodsServices = button.data('goods-services'); 
        const modal = $('#add-registration-modal');
        const form = modal.find('#add-registration-form');

        form[0].reset();
        form.find('#add_registration_pricing_options_container').empty(); 
        form.find('#add_registration_goods_services_container').empty(); 
        form.find('#add_registration_guest_count_field').hide(); 

        form.find('input[name="event_id"]').val(eventId);

        const pricingContainer = form.find('#add_registration_pricing_options_container');
        const goodsServicesContainer = form.find('#add_registration_goods_services_container');
        const basicGuestCountField = form.find('#add_registration_guest_count_field');

        if (pricingOptions && pricingOptions.length > 0) {
            pricingOptions.forEach((option, index) => {
                pricingContainer.append(`
                    <div class="pricing-option">
                        <label>${option.name} (${option.price}):</label>
                        <input type="number" name="guest_details[${index}][count]" min="0" value="0">
                        <input type="hidden" name="guest_details[${index}][name]" value="${option.name}">
                        <input type="hidden" name="guest_details[${index}][price]" value="${option.price}">
                    </div>
                `);
            });
            pricingContainer.show();
            basicGuestCountField.hide();
        } else {

            pricingContainer.hide();
            basicGuestCountField.show();
        }

        if (goodsServices && goodsServices.length > 0) {
            goodsServices.forEach((service, index) => {
                goodsServicesContainer.append(`
                    <div class="goods-service-option">
                        <label>${service.name} (${service.price}):</label>
                        <input type="number" name="goods_services[${index}][count]" min="0" value="0">
                        <input type="hidden" name="goods_services[${index}][name]" value="${service.name}">
                        <input type="hidden" name="goods_services[${index}][price]" value="${service.price}">
                    </div>
                `);
            });
        }

        modal.show();
    });

    $(document).on('submit', '#add-registration-form', function (e) {
        e.preventDefault(); 

        const form = $(this);
        const submitButton = form.find('button[type="submit"]');
        const originalButtonText = submitButton.text();

        submitButton.prop('disabled', true).text('Adding...');

        $.ajax({
            url: eventAdmin.ajaxurl, 
            type: 'POST',
            data: form.serialize() + '&security=' + eventAdmin.update_registration_nonce, 
            success: function (response) {
                if (response.success) {
                    showAdminNotice('Registration added successfully.', 'success');
                    $('#add-registration-modal').hide(); 
                    location.reload(); 
                } else {
                    showAdminNotice('Error: ' + (response.data ? response.data.message : 'Unknown error'), 'error');
                }
            },
            error: function (xhr, status, error) {
                showAdminNotice('Error adding registration: ' + error, 'error');
            },
            complete: function () {
                submitButton.prop('disabled', false).text(originalButtonText);
            }
        });
    });

    $(document).on('click', '#add-registration-modal .cancel-registration', function() {
        $(this).closest('.modal').hide();
    });

    $(document).on('click', '.select-winners', function() {
        var eventId = $(this).data('event-id');
        $('#select_winners_event_id').val(eventId);
        $('#select-winners-modal').show();
    });

    $('#select-winners-form').on('submit', function(e) {
        e.preventDefault();

        var form = $(this);
        var submitButton = form.find('button[type="submit"]');
        var originalButtonText = submitButton.text();

        submitButton.prop('disabled', true).text('Selecting...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: form.serialize() + '&security=' + eventAdmin.select_winners_nonce, 
            success: function(response) {
                if (response.success) {
                    showAdminNotice('Winners selected successfully. Refreshing...', 'success');
                    $('#select-winners-modal').hide();
                    location.reload();
                } else {
                    showAdminNotice('Error: ' + (response.data.message || 'Could not select winners.'), 'error');
                }
            },
            error: function(xhr, status, error) {
                showAdminNotice('Error selecting winners: ' + error, 'error');
            },
            complete: function() {
                submitButton.prop('disabled', false).text(originalButtonText);
            }
        });
    });

    $('#select-winners-modal .close-modal, #select-winners-modal .cancel-selection').on('click', function() {
        $(this).closest('.modal').hide(); 
    });

    $('#export-custom-tables').on('click', function(e) {
        e.preventDefault();
        var button = $(this);
        button.prop('disabled', true).text('Exporting...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'export_custom_tables',
                security: eventAdmin.export_nonce 
            },
            success: function(response) {
                if (response.success && response.data.url) {

                    var downloadLink = document.createElement('a');
                    downloadLink.href = response.data.url;
                    downloadLink.download = response.data.filename || 'custom_tables_export.sql'; 
                    document.body.appendChild(downloadLink);
                    downloadLink.click();
                    document.body.removeChild(downloadLink);
                    showAdminNotice('Export file generated successfully.', 'success');
                } else {

                    showAdminNotice('Failed to export tables: ' + (response.data.message || 'Unknown error'), 'error');
                }
            },
            error: function(xhr, status, error) {

                showAdminNotice('Error occurred while exporting tables. Please try again.', 'error');
                console.error('AJAX Error:', status, error);
            },
            complete: function() {
                button.prop('disabled', false).text('Export Custom Tables');
            }
        });
    });

    $('#event-form').on('submit', function (e) {
        e.preventDefault();

        const form = $(this);
        const submitButton = form.find('input[type="submit"], button[type="submit"]'); 
        const originalButtonText = submitButton.val() || submitButton.text(); 

        submitButton.prop('disabled', true).val('Saving...').text('Saving...'); 

        $.ajax({
            url: eventAdmin.ajaxurl, 
            type: 'POST',
            data: form.serialize(), 
            success: function (response) {
                if (response.success) {
                    showAdminNotice('Event saved successfully.', 'success');

                } else {
                    showAdminNotice('Error: ' + (response.data ? response.data.message : 'Unknown error'), 'error');
                }
            },
            error: function (xhr, status, error) {
                showAdminNotice('Error saving event: ' + error, 'error');
            },
            complete: function () {
                submitButton.prop('disabled', false).val(originalButtonText).text(originalButtonText); 
            }
        });
    });

    $(document).on('click', '.update-guest-counts', function () {
        const button = $(this);
        const row = button.closest('tr');
        const registrationId = row.data('registration-id'); 
        const nonce = eventAdmin.update_registration_nonce; 

        let data = {
            action: 'update_registration_guest_counts',
            registration_id: registrationId,
            security: nonce,
            guest_details: [],
            goods_services: []
        };

        const pricingOptionInputs = row.find('.pricing-option-guest-count');
        pricingOptionInputs.each(function () {
            const input = $(this);
            const index = input.data('pricing-index');
            const count = parseInt(input.val(), 10) || 0;
            const name = input.closest('td').data('name'); 
            const price = parseFloat(input.closest('td').data('price')) || 0;

            data.guest_details.push({
                count: count,
                name: name,
                price: price
            });
        });

        const goodsServiceInputs = row.find('.goods-service-count');
        goodsServiceInputs.each(function () {
            const input = $(this);
            const index = input.data('service-index');
            const count = parseInt(input.val(), 10) || 0;
            const name = input.closest('td').data('name'); 
            const price = parseFloat(input.closest('td').data('price')) || 0;

            data.goods_services.push({
                count: count,
                name: name,
                price: price
            });
        });

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: data,
            beforeSend: function () {
                button.prop('disabled', true).text('Updating...');
            },
            success: function (response) {
                if (response.success) {

                    pricingOptionInputs.each(function () {
                        $(this).data('original', $(this).val());
                    });
                    goodsServiceInputs.each(function () {
                        $(this).data('original', $(this).val());
                    });

                    button.hide(); 
                    showAdminNotice('Guest counts updated successfully.', 'success');
                } else {
                    showAdminNotice('Error: ' + (response.data ? response.data.message : 'Unknown error'), 'error');
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error:', status, error);
                showAdminNotice('Error updating guest counts. Please try again.', 'error');
            },
            complete: function () {
                button.prop('disabled', false).text('Update');
            }
        });
    });




    $('.wp-list-table tbody tr').each(function () {
        updateRowTotalPrice($(this));
    });

    $('.pricing-option-guest-count, .goods-service-count').on('input', function () {
        const row = $(this).closest('tr');
        const table = row.closest('.wp-list-table'); 
        updateRowTotalPrice(row); 
        updateTableTotals(table); 
    });

    $(document).on('click', '.remove-goods-service-option', function () {
        $(this).closest('.goods-service-option').remove();
    });

    $(document).on('click', '.close-modal', function () {
        $(this).closest('.email-modal').hide();
    });

    $(document).on('click', '.cancel-registration', function () {
        $('#add-registration-modal').hide();
    });

    $('.wp-list-table tbody tr').each(function () {
        updateRowTotalPrice($(this));
    });


    $(document).on('input', '.pricing-option-guest-count, .goods-service-count, .simple-guest-count', function () {
        const row = $(this).closest('tr');
        updateRowTotalPrice(row); 
        updateTableTotals(row.closest('.wp-list-table')); 
    });

    $(document).ready(function () {
        $('.wp-list-table').each(function () {
            const table = $(this);
            table.find('tbody tr').each(function () {
                updateRowTotalPrice($(this)); 
            });
            updateTableTotals(table); 
        });
    });
});