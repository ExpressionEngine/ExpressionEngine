/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

$(document).ready(function () {

    $('body').on('click', '[name=verify]', function(e) {

        e.preventDefault();

        EE.cp.ModalForm.openForm({
            url: EE.emailSettings.verifyUrl,
            //iframe: true,
            postData: $(this).parents('form').serialize(),
            load: function (modal) {
                //
            },
            success: function(result) {
                //
            }
        })
    });
});