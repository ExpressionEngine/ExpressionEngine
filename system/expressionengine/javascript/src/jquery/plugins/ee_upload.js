/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2010, EllisLab, Inc.
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
 * @author		ExpressionEngine Dev Team
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
 *     onStart: function(el) {
 *         return {additional: 'value'};
 *     },
 *     onComplete: function(response, element, options) {
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

(function($) {

	// Exposed Response Object [the alternative is to grab the iFrame and eval (no thanks)]
	window.EE_uploads = window.EE_uploads || {};

	$.fn.ee_upload = function(settings) {
		var options;

		options = {
			url: "",
			data: {},
			onStart: function() { return {}; },
			onComplete: empty_function,
			onFailure: empty_function
		};
	
		$.extend(options, settings);
	
		// May be more than one field - loop
		return this.each(function() {
			var that, _internal;
		
			that = $(this);
		
			// Get the process started
			function pre_flight() {
				// Is this even an upload?
				if (that.attr("type") != "file") {
					return;
				}
			
				_internal = { data: options.data };
			
				create_hidden_fields();
				bind_events();
			}
		
			// Generate an iFrame and a form with unique ids
			function create_hidden_fields() {
				var n, iframe, form;
			
				n = Math.floor(Math.random() * 99999);
				iframe = $('<iframe src="about:blank" style="display: none;" id="upload_target_'+n+'" name="upload_target_'+n+'"></iframe>');
				form = $('<form id="upload_form_'+n+'" action="#" method="post" enctype="multipart/form-data" style="display: none;"></form>');
			
				// @confirm This bind is an attempt to remove the 'spinner of death'.  May cause the callback
				// to be called twice - observe.
				iframe.load(function() {
					upload_complete(this);
				});
			
				$(document.body).append(iframe);
				$(document.body).append(form);
			
				_internal.iframe = iframe;
				_internal.form = form;
			}
		
			// Get all of our events ready
			function bind_events() {
			
				// Bind the iFrame load event
				_internal.iframe.load(function() {
					upload_complete(this);
				});
			
				// Bind the submit event
				_internal.form.submit(function() {
					var iframe_id = _internal.iframe.attr('id');
					$(this).attr("target", iframe_id);
					return true;
				});

				// Bind the upload field change event
				that.change(fire_upload);
			}
		
			// Call onStart, set the form action, and fire
			function fire_upload() {
				var changed_opts;
				changed_opts = options.onStart(that, options.data) || {};
				$.extend(_internal.data, changed_opts);
			
				_internal.form.attr('action', assemble_url());			
				move_upload_node();
			}
		
			// Creates a full request url
			function assemble_url() {
				// @todo Do some more validation
				return options.url+'&frame_id='+_internal.iframe.attr('id')+'&'+jQuery.param(_internal.data);
			}
		
			// Moves the upload node to the hidden form and submit
			function move_upload_node() {
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
			}
		
			// Make sure loaded really means loaded
			function upload_complete(frame) {
			    // Event is bound twice - stop the callback after the first fires
			    if (jQuery.data(_internal.iframe, 'upload_complete')) {
			        return;
			    }
			    
				if (frame.contentDocument) {
					var d = frame.contentDocument;
				}
				else if (frame.contentWindow) {
					var d = frame.contentWindow.document;
				}
				else {
					var d = window.frames[id].document;
				}

				if (d.location.href == "about:blank") {
					return;
				}

				// check window.EE_upload
				if (window.EE_uploads[_internal.iframe.attr('id')]) {
					options.onComplete(window.EE_uploads[_internal.iframe.attr('id')], that, options.data);
				}
				else {
					options.onFailure('Connection timed out. Please try again.', that, options.data);
				}
			
			    jQuery.data(_internal.iframe, 'upload_complete', true);
			    
                // Remove upload and yield
			    setTimeout(function() {
				    reset_upload();
				}, 0);
			}
		
			// Remove the temp hidden fields and reset the field
			function reset_upload() {
                _internal.iframe.remove();
                _internal.form.remove();
                pre_flight();
			}
		
			pre_flight();
		});
	
		// Used for default callbacks
		var empty_function = function() { /* nothing */ };
	}

})(jQuery);