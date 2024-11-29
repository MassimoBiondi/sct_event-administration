jQuery(document).ready(function($) {
    // Show registration form when register button is clicked
    $('.register-button').on('click', function() {
        var eventId = $(this).data('event-id');
        $('#event-id').val(eventId);
        $('#event-registration-form').show();
    });

    // Add real-time validation for guest count
    $('#guests').on('input', function() {
        var input = $(this);
        var value = parseInt(input.val());
        var max = parseInt(input.attr('max'));
        
        if (value > max) {
            input.val(max);
        }
    });

    // Handle registration form submission
    $('#event-registration-form').on('submit', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var submitButton = form.find('button[type="submit"]');
        
        // Disable submit button and show loading state
        submitButton.prop('disabled', true).text('Submitting...');
        
        $.ajax({
            url: eventPublic.ajaxurl,
            type: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function(response) {
                console.log('Registration response:', response);
                if (response.success) {
                    form.html('<div class="registration-success">' + 
                             '<h3>Thank you for registering!</h3>' +
                             '<p>' + response.data.message + '</p></div>');
                } else {
                    alert('Error: ' + (response.data ? response.data.message : 'Unknown error'));
                    submitButton.prop('disabled', false).text('Submit Registration');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', {
                    status: status,
                    error: error,
                    response: xhr.responseText
                });
                alert('Error submitting registration. Please try again.');
                submitButton.prop('disabled', false).text('Submit Registration');
            }
        });
    });

});


