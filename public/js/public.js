jQuery(document).ready(function($) {
    // Show registration form when register button is clicked
    $('.register-button').on('click', function() {
        var eventId = $(this).data('event-id');
        $('#event-id').val(eventId);
        $('#event-registration-form').show();
    });

    // Calculate total price dynamically
    function calculateTotalPrice() {
        let totalPrice = 0;

        $('.guest-count').each(function () {
            const count = parseInt($(this).val(), 10) || 0;
            const price = parseFloat($(this).siblings('input[name$="[price]"]').val()) || 0;
            totalPrice += count * price;
        });
        format = $('#total_price').data('format');
        // currency = $('#total_price').data('currency');
        $('#total_price').val(totalPrice.toFixed(format));
    }

    // Bind the calculateTotalPrice function to input changes
    $(document).on('input', '.guest-count', calculateTotalPrice);

    // Handle form submission
    $('#event-registration-form').on('submit', function (e) {
        e.preventDefault();

        const form = $(this);
        const submitButton = form.find('button[type="submit"]');
        const originalButtonText = submitButton.text();

        submitButton.prop('disabled', true).text('Submitting...');

        $.ajax({
            url: eventPublic.ajaxurl,
            type: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    form.html('<div class="registration-success">' +
                        '<h3>Thank you for registering!</h3>' +
                        '<p>' + response.data.message + '</p></div>');
                } else {
                    alert('Error: ' + (response.data ? response.data.message : 'Unknown error'));
                    submitButton.prop('disabled', false).text(originalButtonText);
                }
            },
            error: function (xhr, status, error) {
                alert('Error submitting registration. Please try again.');
                submitButton.prop('disabled', false).text(originalButtonText);
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


