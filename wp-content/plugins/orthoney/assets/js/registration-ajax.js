jQuery(document).ready(function($) {
    jQuery('.toggle-password').click(function() {
        var input = jQuery(this).closest ('div').find('input');
        var type = input.attr('type') === 'password' ? 'text' : 'password';
        input.attr('type', type);
        jQuery(this).toggleClass('fa-eye-slash');
    });
});



jQuery(document).ready(function($) {
    
    function checkPasswordStrength() {
        var password = $('#password').val();
        var strengthMeter = $('#password_strength');
        var hintMessage = $('.woocommerce-password-hint');

        var strength = wp.passwordStrength.meter(password, [], password);
        var strengthText = '';

        switch (strength) {
            case 0:
                strengthText = 'Very Weak - Please enter a stronger password.';
                strengthMeter.removeClass().addClass('woocommerce-password-strength bad').css('color', 'red');
                hintMessage.show();
                break;
            case 1:
                strengthText = 'Weak - Please enter a stronger password.';
                strengthMeter.removeClass().addClass('woocommerce-password-strength bad').css('color', 'orange');
                hintMessage.show();
                break;
            case 2:
                strengthText = 'Medium';
                strengthMeter.removeClass().addClass('woocommerce-password-strength medium').css('color', 'yellow');
                hintMessage.hide();
                break;
            case 3:
                strengthText = 'Strong';
                strengthMeter.removeClass().addClass('woocommerce-password-strength strong').css('color', 'green');
                hintMessage.hide();
                break;
            case 4:
                strengthText = 'Very Strong';
                strengthMeter.removeClass().addClass('woocommerce-password-strength very-strong').css('color', 'blue');
                hintMessage.hide();
                break;
        }

        strengthMeter.text(strengthText);
    }

    $('#reg_password').on('input', checkPasswordStrength);




    function checkPasswordStrength(password) {
        var strongPasswordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;
        return strongPasswordRegex.test(password);
    }

    function highlightInput(input, isValid) {
        if (isValid) {
            input.removeClass('input-error');
        } else {
            input.addClass('input-error');
        }
    }

    $('#reg_email, #confirm_email').on('input', function() {
        var email = $('#reg_email').val();
        var confirmEmail = $('#confirm_email').val();

        if (email !== confirmEmail && confirmEmail !== '') {
            $('.email-error').show();
            highlightInput($('#reg_email'), false);
            highlightInput($('#confirm_email'), false);
        } else {
            $('.email-error').hide();
            highlightInput($('#reg_email'), true);
            highlightInput($('#confirm_email'), true);
        }
    });

    $('#reg_password, #confirm_password').on('input', function() {
        var password = $('#reg_password').val();
        var confirmPassword = $('#confirm_password').val();

        if (!checkPasswordStrength(password)) {
            $('.password-strength-error').show();
            highlightInput($('#reg_password'), false);
        } else {
            $('.password-strength-error').hide();
            highlightInput($('#reg_password'), true);
        }

        if (password !== confirmPassword && confirmPassword !== '') {
            $('.password-error').show();
            highlightInput($('#confirm_password'), false);
        } else {
            $('.password-error').hide();
            highlightInput($('#confirm_password'), true);
        }
    });

    $('#orthoney-registration-form').on('submit', function(e) {
        e.preventDefault();

        var email = $('#reg_email').val();
        var confirmEmail = $('#confirm_email').val();
        var password = $('#reg_password').val();
        var confirmPassword = $('#confirm_password').val();
        var isValid = true;

        if (email !== confirmEmail) {
            $('#registration-response').html('<div class="woocommerce-error">' + orthoney_ajax.email_mismatch + '</div>');
            highlightInput($('#reg_email'), false);
            highlightInput($('#confirm_email'), false);
            isValid = false;
        } else {
            highlightInput($('#reg_email'), true);
            highlightInput($('#confirm_email'), true);
        }

        if (password !== confirmPassword) {
            $('#registration-response').html('<div class="woocommerce-error">' + orthoney_ajax.password_mismatch + '</div>');
            highlightInput($('#reg_password'), false);
            highlightInput($('#confirm_password'), false);
            isValid = false;
        } else {
            highlightInput($('#reg_password'), true);
            highlightInput($('#confirm_password'), true);
        }

        if (!checkPasswordStrength(password)) {
            $('#registration-response').html('<div class="woocommerce-error">Your password is too weak. Use at least 8 characters, one uppercase, one lowercase, one number, and one special character.</div>');
            highlightInput($('#reg_password'), false);
            isValid = false;
        }

        if (!isValid) {
            return false;
        }

        $('#orthoney-register-button').prop('disabled', true).text('Processing...');

        var formData = $(this).serialize();
        formData += '&action=orthoney_register_user';

        $.ajax({
            type: 'POST',
            url: orthoney_ajax.ajax_url,
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#registration-response').html('<div class="woocommerce-message">' + response.message + '</div>');
                    $('#orthoney-registration-form')[0].reset();
                } else {
                    $('#registration-response').html('<div class="woocommerce-error">' + response.message + '</div>');
                }
            },
            error: function() {
                $('#registration-response').html('<div class="woocommerce-error">Something went wrong. Please try again.</div>');
            },
            complete: function() {
                $('#orthoney-register-button').prop('disabled', false).text('Register');
            }
        });

        return false;
    });
});
