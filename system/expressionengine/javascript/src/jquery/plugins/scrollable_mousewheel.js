/**
 * jQuery TOOLS plugin :: scrollable.mousewheel 1.0.0
 * 
 * Copyright (c) 2009 Tero Piirainen
 * http://flowplayer.org/tools/scrollable.html#mousewheel
 *
 * Dual licensed under MIT and GPL 2+ licenses
 * http://www.opensource.org/licenses
 *
 * Launch  : September 2009
 * Date: ${date}
 * Revision: ${revision} 
 *
 * 
 * jquery.event.wheel.js - rev 1 
 * Copyright (c) 2008, Three Dub Media (http://threedubmedia.com)
 * Liscensed under the MIT License (MIT-LICENSE.txt)
 * http://www.opensource.org/licenses/mit-license.php
 * Created: 2008-07-01 | Updated: 2008-07-14
 */
(function($) {
		
	$.fn.wheel = function( fn ){
		return this[ fn ? "bind" : "trigger" ]( "wheel", fn );
	};

	// special event config
	$.event.special.wheel = {
		setup: function(){
			$.event.add( this, wheelEvents, wheelHandler, {} );
		},
		teardown: function(){
			$.event.remove( this, wheelEvents, wheelHandler );
		}
	};

	// events to bind ( browser sniffed... )
	var wheelEvents = !$.browser.mozilla ? "mousewheel" : // IE, opera, safari
		"DOMMouseScroll"+( $.browser.version<"1.9" ? " mousemove" : "" ); // firefox

	// shared event handler
	function wheelHandler( event ) {
		
		switch ( event.type ){
			
			// FF2 has incorrect event positions
			case "mousemove": 
				return $.extend( event.data, { // store the correct properties
					clientX: event.clientX, clientY: event.clientY,
					pageX: event.pageX, pageY: event.pageY
				});
				
			// firefox	
			case "DOMMouseScroll": 
				$.extend( event, event.data ); // fix event properties in FF2
				event.delta = -event.detail / 3; // normalize delta
				break;
				
			// IE, opera, safari	
			case "mousewheel":				
				event.delta = event.wheelDelta / 120;
				break;
		}
		
		event.type = "wheel"; // hijack the event	
		return $.event.handle.call( this, event, event.delta );
	}
	
	
	// version number
	var t = $.tools.scrollable; 
	t.plugins = t.plugins || {};
	t.plugins.mousewheel = {	
		version: '1.0.0',
		conf: { 
			api: false,
			speed: 50
		} 
	}; 
	
	// scrollable mousewheel implementation
	$.fn.mousewheel = function(opts) {

		var conf = $.extend({}, t.plugins.mousewheel.conf), ret;
		if (typeof opts == 'number') { opts = {speed: opts}; }
		$.extend(conf, opts);
		
		this.each(function() {		

			var api = $(this).scrollable();
			if (api) { ret = api; }
			
			api.getRoot().wheel(function(e, delta)  { 
				api.move(delta < 0 ? 1 : -1, conf.speed || 50);
				return false;
			});
		});
		
		return conf.api ? ret : this;
	};
	
})(jQuery); 

