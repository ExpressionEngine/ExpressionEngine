/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

(function($) {

"use strict";

$(document).ready(function() {


    $('input[type=password]').each(function() {
        var passwordInput = $(this);
        var passwordInputContainer = passwordInput.closest('fieldset'),
            eyeOpen = EE.PATH_CP_GBL_IMG + 'eye-open.svg',
            eyeClosed = EE.PATH_CP_GBL_IMG + 'eye-closed.svg',
            eyeImg = '<img src="' + eyeOpen + '" class="eye js-show-password" alt="'+EE.lang.password_icon+'"/>', 
            eyeIsOpen = false

        $(passwordInputContainer).css({'position': 'relative'})
        if (passwordInput.closest('.field-control').length) {
            passwordInput.closest('.field-control').css('position', 'relative');
        }
        $(eyeImg).insertAfter(passwordInput)

        $(passwordInputContainer).find('.js-show-password').on('click', function () {
            passwordInput.attr('type', (passwordInput.attr('type') === 'password' ? 'text' : 'password'));
            $(this).attr('src', eyeIsOpen ? eyeOpen : eyeClosed)
            eyeIsOpen = !eyeIsOpen
        });
    })

    

    // Check password strength indicator
    function passwordStrengthIndicator(field) {

        var form = field.parents('form'),
                action = form.attr('action'),
                data = form.serialize();

            if (typeof(EE.cp.validatePasswordUrl) != 'undefined' && $(field).parent('.field-control').length) {
                $(field).parent('.field-control').css('position', 'relative');

                $.ajax({
                    type: 'POST',
                    url: EE.cp.validatePasswordUrl,
                    dataType: 'json',
                    data: data+'&ee_fv_field='+field.attr('name'),
                    success: function (result) {
                        if (result['rank'] == 0) {
                            $('.rank-wrap').remove();
                            return;
                        } else {
                            var rank_text = result['rank_text'].toLowerCase();
                            var rank = result['rank'];
                            var classList = 'status-tag '+rank_text;
                            if (!$('.rank-wrap').length) {
                                $(field).after('<div class="rank-wrap"><p class="'+classList+'"><span class="rank_text">'+rank_text+'</span></p></div>');
                            } else {
                                $('.rank-wrap > p').attr('class', classList);
                                $('.rank-wrap .rank_text').text(rank_text);
                            }
                        }
                    },
                    error: function(err) {
                        console.log('err', err);
                    }
                })
            }
    }

    var passwordTimeout = null

    // Typing into the password field if it is not installer page
    $('body').not('.installer-page').on('keyup', 'input[name="password"]:not([autocomplete="current-password"]), input[name="new_password"]', function() {
        var field = $(this);
        var val = $(this).val();
        clearTimeout(passwordTimeout)
        passwordTimeout = setTimeout(function() {
            if(val == 0) {
                if ($('.rank-wrap').length) {
                    $('.rank-wrap').remove();
                }
            } else {
                passwordStrengthIndicator(field);
            }

            passwordTimeout = null
        }, 1000);
    });

});

})(jQuery);
