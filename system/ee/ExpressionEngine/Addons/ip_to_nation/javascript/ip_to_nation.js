/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

var Ip2n_cp = {

	update: function() {
		var steps = EE.ip2n.steps,
			that = this;

		this.button = $('a.button.button--secondary.action');

		this.button.on('click', function(e) {
			e.preventDefault();
			that.button.text(EE.ip2n.lang.ip_db_updating);
			that.button.addClass('work');
			that._progress(steps);
			return false;
		})
	},

	_progress: function(steps)
	{
		var that = this;

		$.getJSON(EE.ip2n.base_url + '&method=' + steps.shift(), function(data) {
			if (data.success) {
				that.button.text(data.success);

				if (steps.length) {
					return that._progress(steps);
				}

				window.location = EE.ip2n.base_url;
				return;
			}

			msg = data.error || '';

			that.button.removeClass('work');
			that.button.text(EE.ip2n.lang.ip_db_failed + '  ' + msg);


		});
	}

};

// run_script is set in the controller
if (EE.ip2n && EE.ip2n.run_script) {
	setTimeout(function() {
		var script = EE.ip2n.run_script;
		Ip2n_cp[script]();
	}, 100);
}
