jQuery(document).ready(function($) {
    // Handle event form submission
    $('#add-event-form').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: eventAdmin.ajaxurl,
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    // alert(response.data.message);
                    window.location.href = 'admin.php?page=event-admin';
                } else {
                    alert('Error: ' + response.data.message);
                }
            }
        });
    });

    // Handle event deletion
    // Event deletion handler
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
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    // Remove the row from the table
                    button.closest('tr').fadeOut(400, function() {
                        $(this).remove();
                    });
                    //alert(response);
                } else {
                    // location.reload();
                    alert(response.data.message || 'Error deleting event');
                }
            },
            error: function() {
                // location.reload();
                alert('Error deleting event');
            }
        });
    });


    // // Show/hide update button when guest count changes
    // $('.guest-count').on('input', function() {
    //     var input = $(this);
    //     var originalValue = input.data('original');
    //     var updateButton = input.siblings('.update-guest-count');

    //     // Disable all other .guest-count inputs
    //     $('.guest-count').not(this).prop('disabled', true);
        
    //     if (input.val() != originalValue) {
    //         updateButton.show();
    //     } else {
    //         updateButton.hide();
    //     }
    // });

    // // Optional: Re-enable all inputs when focus is lost
    // $('.guest-count').on('blur', function() {
    //     $('.guest-count').prop('disabled', false);
    //     var input = $(this);
    //     var originalValue = input.data('original');
    //     var updateButton = input.siblings('.update-guest-count');
        
    //     // Reset value to original
    //     input.val(originalValue);
    //     // Hide update button
    //     updateButton.hide();
    //     // Re-enable all inputs
    //     $('.guest-count').prop('disabled', false);
    // });

    // $('.guest-count').on('focus', function() {
    //     var input = $(this);
    //     var originalValue = input.data('original');
    //     var updateButton = input.siblings('.update-guest-count');
        
    //     // Disable all other .guest-count inputs
    //     $('.guest-count').not(this).prop('disabled', true);
        
    //     if (input.val() != originalValue) {
    //         updateButton.show();
    //     } else {
    //         updateButton.hide();
    //     }
    // });
    
    $('.guest-count').on('input', function() {
        var input = $(this);
        var originalValue = input.data('original');
        var updateButton = input.siblings('.update-guest-count');

        // Reset all other guest-count inputs except this one
        $('.guest-count').not(this).each(function() {
            var otherInput = $(this);
            otherInput.val(otherInput.data('original'));
            otherInput.siblings('.update-guest-count').hide();
        });
        
        if (input.val() != originalValue) {
            updateButton.show();
        } else {
            updateButton.hide();
        }
    });


    // Handle guest count updates
    $('.update-guest-count').on('click', function() {
        var button = $(this);
        var input = button.siblings('.guest-count');
        var registrationId = button.closest('tr').data('registration-id');
        var newGuestCount = input.val();
        
        $.ajax({
            url: eventAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'update_registration',
                registration_id: registrationId,
                guest_count: newGuestCount,
                security: eventAdmin.updateNonce // Add the nonce
            },
            beforeSend: function() {
                button.prop('disabled', true).text('Updating...');
            },
            success: function(response) {
                if (response.success) {
                    input.data('original', newGuestCount);
                    button.hide();
                    // Show success message
                    var successMessage = $('<span class="success-message" style="color: green; margin-left: 10px;">Updated!');
                    button.after(successMessage);
                    setTimeout(function() {
                        successMessage.fadeOut(function() {
                            $(this).remove();
                        });
                    }, 2000);
                    
                    // Refresh the page to update totals
                    location.reload();
                } else {
                    alert('Error: ' + (response.data ? response.data.message : 'Unknown error'));
                    input.val(input.data('original'));
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                alert('Error updating guest count: ' + error);
                input.val(input.data('original'));
            },
            complete: function() {
                button.prop('disabled', false).text('Update');
            }
        });
    });

    // Handle registration deletion
    $('.delete-registration').on('click', function(e) {
        e.preventDefault();
        
        if (!confirm('Are you sure you want to delete this registration?')) {
            return;
        }

        var button = $(this);
        var registrationId = button.data('registration-id');
        var nonce = button.data('nonce');
        // console.log('Nonce value:', nonce); 

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'delete_registration',
                registration_id: registrationId,
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    // Remove the row from the table
                    button.closest('tr').fadeOut(400, function() {
                        $(this).remove();
                    });
                } else {
                    alert(response.data.message || 'Error deleting registration');
                }
            },
            error: function() {
                alert('Error deleting registration');
            }
        });
    });

     // Email modal functionality
     var modal = $('#email-modal');
    
     // Open modal when send email button is clicked
     $('.send-email').on('click', function(e) {
         e.preventDefault();
         var button = $(this);
         var row = button.closest('tr');
         var registrationId = row.data('registration-id');
         var recipientEmail = button.data('email');
         var name = row.find('td:first').text();
         
         $('#recipient_email').val(recipientEmail);
         $('#registration_id').val(registrationId);
         $('#is_mass_email').val('0');

         
         // Pre-fill subject with event name
         var eventName = button.closest('.event-registrations').find('h2').text().split(' - ')[0];
         $('#email_subject').val('Regarding: ' + eventName);
         
         modal.show();
     });

    // Open modal for mass email
    $('.mail-to-all').on('click', function(e) {
        e.preventDefault();
        var button = $(this);
        var eventId = button.data('event-id');
        var eventName = button.data('event-name');
        
        $('#event_id').val(eventId);
        $('#is_mass_email').val('1');
        $('#email_subject').val('Regarding: ' + eventName);
        
        modal.show();
    });
     
     // Close modal when X or Cancel is clicked
     $('.close-modal, .cancel-email').on('click', function(e) {
         e.preventDefault();
         closeModal();
     });
     
     // Close modal when clicking outside
     modal.on('click', function(e) {
         // Check if the click was on the modal background (not the content)
         if ($(e.target).is(modal)) {
            //  closeModal();
         }
     });
 
     // Prevent modal from closing when clicking inside the modal content
     $('.email-modal-content').on('click', function(e) {
         e.stopPropagation();
     });
     
     // Function to close modal
     function closeModal() {
        modal.hide();
        if ($('#send-email-form').length) {
            $('#send-email-form')[0].reset();
            // Clear all hidden fields too
            $('#recipient_email').val('');
            $('#registration_id').val('');
            $('#event_id').val('');
            $('#is_mass_email').val('');    
        }
     }
     
     // Handle email form submission
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
                    alert(response.data.message);
                    closeModal();
                    // modal.hide();
                    // form[0].reset();
                } else {
                    alert('Error: ' + (response.data ? response.data.message : 'Unknown error'));
                }
            },
            error: function(xhr, status, error) {
                alert('Error sending email: ' + error);
            },
            complete: function() {
                submitButton.prop('disabled', false).text(originalButtonText);
            }
        });
    });
    
    // Handle retry email button click
    $('.retry-email').on('click', function(e) {
        e.preventDefault();
        
        const button = $(this);
        const email = button.data('email');
        const eventId = button.data('event-id');
        const nonce = button.data('nonce');
        
        // Disable button and show loading state
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
                    // Show success message
                    alert('Email sent successfully!');
                    // Optionally refresh the page or update UI
                    location.reload();
                } else {
                    alert('Failed to send email: ' + response.data.message);
                }
            },
            error: function(xhr, status, error) {
                alert('Error occurred while sending email. Please try again.');
                console.error('AJAX Error:', status, error);
            },
            complete: function() {
                // Re-enable button and restore text
                button.prop('disabled', false).text('Retry');
            }
        });
    });
    
    $('.view-email-content').click(function() {
        const subject = $(this).data('subject');
        const content = $(this).data('content');
        
        $('#modal-email-subject').text(subject);
        $('#email-content').text(content);
        $('#email-content-modal').show();
    });

    // View recipients handler
    $('.view-recipients').click(function() {
        const recipientsData = $(this).data('recipients').split('|');
        const subject = $(this).data('subject');
        const eventId = $(this).data('event-id');
        const wp_nonce = $(this).data('nonce'); // Make sure this is set in your PHP
        
        $('#modal-recipients-title').text('Recipients for: ' + subject);
        
        let recipientsList = '<ul>';
        recipientsData.forEach(function(recipientData) {
            const [email, status] = recipientData.split(':');
            const statusClass = status === 'failed' ? 'failed-status' : 'success-status';
            const statusIcon = status === 'failed' ? 
                '<span class="dashicons dashicons-warning"></span>': 
                '<span class="dashicons dashicons-yes-alt"></span>';
            
            recipientsList += `
                <li class="${statusClass}">
                    ${statusIcon}
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

    // Close recipients modal
    $(document).on('click', '.close-modal, .modal', function(event) {
        if (event.target === this) {
            $('#recipients-modal, #email-content-modal').hide();
        }
    });

    // Helper function to show notices in the modal
    function showNotice(message, type) {
        const noticeHtml = `
            <div class="notice notice-${type} is-dismissible inline-notice">
                <p>${message}</p>
            </div>`;
        
        $('.modal-notice').remove(); // Remove any existing notices
        $('#recipients-list').before(noticeHtml);
        
        // Auto dismiss after 3 seconds
        setTimeout(function() {
            $('.modal-notice').fadeOut(function() {
                $(this).remove();
            });
        }, 3000);
    }

    // Retry single email handler
    $(document).on('click', '.retry-single-email', function(e) {
        e.preventDefault();
        const button = $(this);
        const email = button.data('email');
        const eventId = button.data('event-id');
        const nonce = button.data('nonce');

        // Add spinning animation to button
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
                    // Update the list item to show success
                    const listItem = button.closest('li');
                    listItem.removeClass('failed-status').addClass('success-status');
                    listItem.find('.dashicons-warning').removeClass('dashicons-warning').addClass('dashicons-yes-alt');
                    listItem.find('.status-label').text('Sent');
                    button.remove();

                    // Show success message
                    showNotice('Email resent successfully', 'success');
                } else {
                    button.prop('disabled', false)
                        .find('.dashicons-update')
                        .removeClass('spinning');
                    showNotice(response.data.message || 'Failed to resend email', 'error');
                }
            },
            error: function() {
                button.prop('disabled', false)
                    .find('.dashicons-update')
                    .removeClass('spinning');
                showNotice('Server error occurred', 'error');
            }
        });
    });


}); // End Ready Function
