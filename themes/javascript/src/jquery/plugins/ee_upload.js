/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2012, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/*!
 * ExpressionEngine Async Upload Plugin
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://expressionengine.com
 */

/* Usage Notes:
 *
 * Required parameter: url
 *
 * Sent GET data: iFrame id, custom data
 *
 * Expected Response:
 * A script tag that, when run, sets parent.EE_uploads.<iframeid> to the desired response string.
 * This response is automatically passed into the onComplete callback
 *
 * Events / Callbacks:
 * onStart		- called pre-request, return values are used to set request specific options
 * onComplete	- called once a response has been received
 * onFailure	- no implemented
 */

/* Example:
 *
 * $("input[type=file]").ee_upload({
 *     url: 'index.php?S=0&D=new&C=controller&M=upload_file',
 *     onStart: function (el) {
 *         return {additional: 'value'};
 *     },
 *     onComplete: function (response, element, options) {
 *         alert(response);
 *     }
 * });
 *
 * == Backend ==
 *
 * $additional = $this->input->get('additional');
 *
 * echo '<script type="text/javascript">parent.EE_uploads.'.$this->input->get('frame_id').' = "woot!";</script>';
 * exit;
 * 
 */

/*jslint browser: true, onevar: true, undef: true, nomen: true, eqeqeq: true, plusplus: false, bitwise: true, regexp: false, strict: true, newcap: true, immed: true, nomen: false */

/*global $, jQuery, EE, window, document, console, alert */

"use strict";

(function ($) {

	// Exposed Response Object [the alternative is to grab the iFrame and eval (no thanks)]
	window.EE_uploads = window.EE_uploads || {};

	$.fn.ee_upload = function (settings) {
		var options, empty_function;

		options = {
			url: "",
			data: {},
			onStart: function () { 
				return {}; 
			},
			onComplete: empty_function,
			onFailure: empty_function
		};
	
		$.extend(options, settings);

		// Used for default callbacks
		empty_function = function () { /* nothing */ };
	
		// May be more than one field - loop
		return this.each(function () {
			var that, _internal,
				create_hidden_fields,
				bind_events,
				pre_flight,
				move_upload_node,
				fire_upload,
				assemble_url,
				reset_upload,
				upload_complete;
		
			that = $(this);
		
			// Get the process started
			pre_flight = function () {
				// Is this even an upload?
				if (that.attr("type") !== "file") {
					return;
				}
			
				_internal = { data: options.data };
			
				create_hidden_fields();
				bind_events();
			};
		
			// Generate an iFrame and a form with unique ids
			create_hidden_fields = function () {
				var n, iframe, form;
			
				n = Math.floor(Math.random() * 99999);
				iframe = $('<iframe src="about:blank" style="display: none;" id="upload_target_' + n + '" name="upload_target_' + n + '"></iframe>');
				form = $('<form id="upload_form_' + n + '" action="#" method="post" enctype="multipart/form-data" style="display: none;"></form>');
			
				// @confirm This bind is an attempt to remove the 'spinner of death'.  May cause the callback
				// to be called twice - observe.
				iframe.load(function () {
					upload_complete(this);
				});
			
				$(document.body).append(iframe);
				$(document.body).append(form);
			
				_internal.iframe = iframe;
				_internal.form = form;
			};
		
			// Get all of our events ready
			bind_events = function () {
			
				// Bind the iFrame load event
				_internal.iframe.load(function () {
					upload_complete(this);
				});
			
				// Bind the submit event
				_internal.form.submit(function () {
					var iframe_id = _internal.iframe.attr('id');
					$(this).attr("target", iframe_id);
					return true;
				});

				// Bind the upload field change event
				that.change(fire_upload);
			};

			// Moves the upload node to the hidden form and submit
			move_upload_node = function () {
				var placebo, saved, newel;
			
				// We have to move the upload node to our new form.  This gets
				// a little iffy as we want to preserve the event handler, so we need a copy.
				// Newer versions of webkit will kill the file reference if you simply
				// try to grab the clone, so we have this ugly solution.
				placebo = $("<div></div>");
				that.after(placebo);

				saved = that.clone(true);
				newel = that.remove();

				placebo.replaceWith(saved);
				saved.attr("value", "");
			
				_internal.form.append(newel);
				_internal.form.trigger('submit');
			
				// Reset Reference
				that = saved;
			};
		
			// Call onStart, set the form action, and fire
			fire_upload = function () {
				var changed_opts;
				changed_opts = options.onStart(that, options.data) || {};
				$.extend(_internal.data, changed_opts);
			
				_internal.form.attr('action', assemble_url());			
				move_upload_node();
			};
		
			// Creates a full request url
			assemble_url = function () {
				// @todo Do some more validation
				return options.url + '&frame_id=' + _internal.iframe.attr('id') + '&' + jQuery.param(_internal.data);
			};

			// Remove the temp hidden fields and reset the field
			reset_upload = function () {
                _internal.iframe.remove();
                _internal.form.remove();
                pre_flight();
			};
					
			// Make sure loaded really means loaded
			upload_complete = function (frame) {
				var d; 
				 
			    // Event is bound twice - stop the callback after the first fires
			    if ($(_internal.iframe).data('upload_complete')) {
			        return;
			    }
			    
				if (frame.contentDocument) {
					d = frame.contentDocument;
				} else if (frame.contentWindow) {
					d = frame.contentWindow.document;
				} else {
					d = window.frames[_internal.iframe.attr('id')].document;
				}

				if (d.location.href === "about:blank") {
					return;
				}

				// check window.EE_upload
				if (window.EE_uploads[_internal.iframe.attr('id')]) {
					options.onComplete(window.EE_uploads[_internal.iframe.attr('id')], that, options.data);
				}
				else {
					options.onFailure('Connection timed out. Please try again.', that, options.data);
				}
			
			    $(_internal.iframe).data('upload_complete', true);
			    
                // Remove upload and yield
			    setTimeout(function () {
				    reset_upload();
				}, 0);
			};
		
			pre_flight();
		});
	};

})(jQuery);