<script type="text/javascript" charset="utf-8">
	/**
	 * Ajax Queue Plugin
	 * 
	 * Homepage: http://jquery.com/plugins/project/ajaxqueue
	 * Documentation: http://docs.jquery.com/AjaxQueue
	 */
	
	/**
	 * Changed to work with jQuery 1.3
	 * Automatic dequeuing only works for effect queues in 1.3
	 */

	/*
	 * Queued Ajax requests.
	 * A new Ajax request won't be started until the previous queued 
	 * request has finished.
	 */
	jQuery.ajaxQueue = function(o){
		var _old = o.complete;
		o.complete = function(){
			if ( _old ) _old.apply( this, arguments );
			jQuery([ jQuery.ajaxQueue ]).dequeue( "ajax" );
		};

		jQuery([ jQuery.ajaxQueue ]).queue("ajax", function(){
			jQuery.ajax( o );
		});

		if ( jQuery([ jQuery.ajaxQueue ]).queue("ajax").length == 1 ) {
			jQuery([ jQuery.ajaxQueue ]).dequeue("ajax");
		}
	};

</script>
<script type="text/javascript" charset="utf-8">
	
	var process_url = "<?=$process_url?>",
		state_url = "<?=$state_url?>",
		end_url = "<?=$end_url?>",
		status_container = "<?=$progress_container?>",
		interval,
		last_state;
	
	$(document).ready(function() {
		
		status_container = $(status_container);
		status_container.text('Starting next step...');
		
		interval_func = function() {
			jQuery.ajaxQueue({
				url: state_url,
				success: function(res) {
					
					if (res != last_state)
					{
						status_container.html(res);
						last_state = res;
					}
				}
			});
		}
		
		// Start listening for state changes
		interval = setInterval(function() { interval_func(); }, 200);
		
		// Run whatever needs to run
		$.ajax({
			url: process_url,
			success: function(res) {
				clearInterval(interval);
				window.location.href = end_url;
			},
			error: function(xhr, status, exception) {
				clearInterval(interval);
				if (xhr.responseText) {
					document.open();
					document.write(xhr.responseText);
					document.close();
				}
				else {
					alert("The update script failed without returning an error. Please contact tech-support.");
					console.log(xhr, status, exception);
				}
			}
		});			
	});
	
</script>