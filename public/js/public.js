jQuery(document).ready(function($) {
    // Show registration form when register button is clicked
    $('.register-button').on('click', function() {
        var eventId = $(this).data('event-id');
        $('#event-id').val(eventId);
        $('#event-registration-form').show();
    });

    // Calculate total price
    function calculateTotalPrice() {
        var memberPrice = parseFloat($('#member_price').val()) || 0;
        var nonMemberPrice = parseFloat($('#non_member_price').val()) || 0;
        var memberGuests = parseInt($('#member_guests').val()) || 0;
        var nonMemberGuests = parseInt($('#non_member_guests').val()) || 0;
        var priceType = $('#price_type').val();
        var currencySymbol = eventPublic.currencySymbol;
        var currencyFormat = eventPublic.currencyFormat !== undefined && eventPublic.currencyFormat !== '' ? parseInt(eventPublic.currencyFormat) : 2;

        var totalPrice = 0;

        if (priceType === 'both') {
            totalPrice = (memberPrice * memberGuests) + (nonMemberPrice * nonMemberGuests);
        } else if (priceType === 'member_only') {
            totalPrice = memberPrice * (memberGuests + nonMemberGuests);
        } else if (priceType === 'non_member_only') {
            totalPrice = nonMemberPrice * (memberGuests + nonMemberGuests);
        }

        $('#total_price').val(currencySymbol + totalPrice.toFixed(currencyFormat));
    }

    // Bind the calculateTotalPrice function to relevant input changes
    $('#member_price, #non_member_price, #member_guests, #non_member_guests, #price_type').on('input change', calculateTotalPrice);

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

    // Handle delete reservation button click
    $('.delete-reservation').on('click', function(e) {
        e.preventDefault();

        if (!confirm('Are you sure you want to delete this reservation?')) {
            return;
        }

        var button = $(this);
        var reservationId = button.data('reservation-id');
        var uniqueId = button.data('unique-id');
        var nonce = button.data('nonce');

        $.ajax({
            url: eventPublic.ajaxurl,
            type: 'POST',
            data: {
                action: 'delete_reservation',
                reservation_id: reservationId,
                unique_id: uniqueId,
                security: nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('Reservation deleted successfully.');
                    location.reload();
                } else {
                    alert('Failed to delete reservation: ' + response.data.message);
                }
            },
            error: function(xhr, status, error) {
                alert('Error occurred while deleting reservation. Please try again.');
                console.error('AJAX Error:', status, error);
            }
        });
    });

    // Initial calculation
    calculateTotalPrice();
});


