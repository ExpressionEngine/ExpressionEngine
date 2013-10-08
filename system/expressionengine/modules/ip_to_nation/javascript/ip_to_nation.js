var Ip2n_cp = {

	update: function() {
		var steps = EE.ip2n.steps,
			that = this;

		this.progress_p = $('#update_form').find('input').closest('p');

		$('#update_form').submit(function(e) {
			e.preventDefault();
			that.progress_p.addClass('notice');
			that.progress_p.text(EE.ip2n.lang.ip_db_updating);
			that._progress(steps);
			return false;
		})
	},

	_progress: function(steps)
	{
		var that = this;

		$.getJSON(EE.ip2n.base_url + '&method=' + steps.shift(), function(data) {
			if (data.success) {
				that.progress_p.text(data.success);

				if (steps.length) {
					return that._progress(steps);
				}

				return;
			}
			
			msg = data.error || '';
			
			that.progress_p.text(EE.ip2n.lang.ip_db_failed + '  ' + msg);
			
		
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