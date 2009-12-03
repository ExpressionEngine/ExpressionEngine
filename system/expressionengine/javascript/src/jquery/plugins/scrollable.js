/**
 * tools.scrollable 1.1.0 - Scroll your HTML with eye candy.
 * 
 * Copyright (c) 2009 Tero Piirainen
 * http://flowplayer.org/tools/scrollable.html
 *
 * Dual licensed under MIT and GPL 2+ licenses
 * http://www.opensource.org/licenses
 *
 * Launch  : March 2008
 * Date: ${date}
 * Revision: ${revision} 
 */
(function($) { 
		
	// static constructs
	$.tools = $.tools || {};
	
	$.tools.scrollable = {
		version: '1.1.0',
		
		conf: {
			
			// basics
			size: 5,
			vertical: false,
			speed: 400,
			keyboard: true,		
			
			// by default this is the same as size
			keyboardSteps: null, 
			
			// other
			disabledClass: 'disabled',
			hoverClass: null,		
			clickable: true,
			activeClass: 'active', 
			easing: 'swing',
			
			items: '.items',
			item: null,
			
			// navigational elements			
			prev: '.prev',
			next: '.next',
			prevPage: '.prevPage',
			nextPage: '.nextPage', 
			api: false
			
			// CALLBACKS: onBeforeSeek, onSeek, onReload
		} 
	};
				
	// len = amount of instances
	var current, len = 0;		
	
	// constructor
	function Scrollable(root, conf, len) {   
		
		// current instance
		var self = this, 
			 horizontal = !conf.vertical,
			 wrap = root.children(),
			 index = 0,
			 forward;  
		
		
		if (!current) { current = self; }
		
		// generic binding function
		function bind(name, fn) {
			$(self).bind(name, function(e, args)  {
				if (fn && fn.call(this, args.index) === false && args) {
					args.proceed = false;	
				}	
			});	 
			return self;
		}
		
		// bind all callbacks from configuration
		$.each(conf, function(name, fn) {
			if ($.isFunction(fn)) { bind(name, fn); }
		});   

		
		if (wrap.length > 1) { wrap = $(conf.items, root); }
		
		// navi can be anywhere when: single scrollable or single navi element or globalNav = true
		function find(query) {
			var els = $(query);
			return len == 1 || els.length == 1 || conf.globalNav ? els : root.parent().find(query);	
		}

		// to be used by plugins
		root.data("finder", find);
		
		// get handle to navigational elements
		var prev = find(conf.prev),
			 next = find(conf.next),
			 prevPage = find(conf.prevPage),
			 nextPage = find(conf.nextPage);

		
		// methods
		$.extend(self, {
			
			getIndex: function() {
				return index;	
			},
	
			getConf: function() {
				return conf;	
			},
			
			getSize: function() {
				return self.getItems().size();	
			},
	
			getPageAmount: function() {
				return Math.ceil(this.getSize() / conf.size); 	
			},
			
			getPageIndex: function() {
				return Math.ceil(index / conf.size);	
			},

			getNaviButtons: function() {
				return prev.add(next).add(prevPage).add(nextPage);	
			},
			
			getRoot: function() {
				return root;	
			},
			
			getItemWrap: function() {
				return wrap;	
			},
			
			getItems: function() {
				return wrap.children(conf.item);	
			},
			
			getVisibleItems: function() {
				return self.getItems().slice(index, index + conf.size);	
			},
			
			/* all seeking functions depend on this */		
			seekTo: function(i, time, fn) {

				// default speed
				if (time === undefined) { time = conf.speed; }
				
				// function given as second argument
				if ($.isFunction(time)) {
					fn = time;
					time = conf.speed;
				} 
				
				if (i < 0) { i = 0; }				
 
				if (i > self.getSize() - conf.size) { return this.end(); } 				

				var item = self.getItems().eq(i);					
				if (!item.length) { return self; }				
				
				// onBeforeSeek
				var p = {index: i, proceed: true};
				$(self).trigger("onBeforeSeek", p);				
				if (!p.proceed) { return self; }
									
				function callback() {
					if (fn) { fn.call(self); }
					$(self).trigger("onSeek", p);	
				}
				
				if (horizontal) {
					wrap.animate({left: -item.position().left}, time, conf.easing, callback);					
				} else {
					wrap.animate({top: -item.position().top}, time, conf.easing, callback);							
				}
				
				current = self;
				index = i;					
				return self; 
			},			
			
				
			move: function(offset, time, fn) {
				forward = offset > 0;
				return this.seekTo(index + offset, time, fn);
			},
			
			next: function(time, fn) {
				return this.move(1, time, fn);	
			},
			
			prev: function(time, fn) {
				return this.move(-1, time, fn);	
			},
			
			movePage: function(offset, time, fn) {
				forward = offset > 0;
				var steps = conf.size * offset;
				
				var i = index % conf.size;
				if (i > 0) {
				 	steps += (offset > 0 ? -i : conf.size - i);
				}
				
				return this.move(steps, time, fn);		
			},
			
			prevPage: function(time, fn) {
				return this.movePage(-1, time, fn);
			},  
	
			nextPage: function(time, fn) {
				return this.movePage(1, time, fn);
			},			
			
			setPage: function(page, time, fn) {
				return this.seekTo(page * conf.size, time, fn);
			},			
			
			begin: function(time, fn) {
				return this.seekTo(0, time, fn);	
			},
			
			end: function(time, fn) {
				var to = this.getSize() - conf.size;
				return to > 0 ? this.seekTo(to, time, fn) : self;	
			},
			
			reload: function() {				
				$(self).trigger("onReload", {});
				return self;
			},

			// callback functions
			onBeforeSeek: function(fn) {
				return bind("onBeforeSeek", fn); 		
			},
			
			onSeek: function(fn) {
				return bind("onSeek", fn); 		
			},
			
			onReload: function(fn) {
				return bind("onReload", fn); 		
			},
			
			focus: function() {
				current = self;
				return self;
			},
			
			click: function(i) {
				
				var item = self.getItems().eq(i), 
					 klass = conf.activeClass,
					 size = conf.size;			
				
				// check that i is sane
				if (i < 0 || i >= self.getSize()) { return self; }
				
				// size == 1							
				if (size == 1) {
					if (i === 0 || i == self.getSize() -1)  { 
						forward = (forward === undefined) ? true : !forward;	 
					}
					return forward === false  ? self.prev() : self.next(); 
				} 
				
				// size == 2
				if (size == 2) {
					if (i == index) { i--; }
					self.getItems().removeClass(klass);
					item.addClass(klass);					
					return self.seekTo(i, time, fn);
				}				
		
				if (!item.hasClass(klass)) {				
					self.getItems().removeClass(klass);
					item.addClass(klass);
					var delta = Math.floor(size / 2);
					var to = i - delta;
		
					// next to last item must work
					if (to > self.getSize() - size) { 
						to = self.getSize() - size; 
					}
		
					if (to !== i) {
						return self.seekTo(to);		
					}
				}
				
				return self;
			}   
			
		});
			
		// prev button		
		prev.addClass(conf.disabledClass).click(function() {
			self.prev(); 
		});
		

		// next button
		next.click(function() { 
			self.next(); 
		});
		
		// prev page button
		nextPage.click(function() { 
			self.nextPage(); 
		});
		

		// next page button
		prevPage.addClass(conf.disabledClass).click(function() { 
			self.prevPage(); 
		});		

		
		self.onSeek(function(i) {
			// prev buttons disabled flag
			if (i === 0) {
				prev.add(prevPage).addClass(conf.disabledClass);					
			} else {
				prev.add(prevPage).removeClass(conf.disabledClass);
			}
			
			// next buttons disabled flag
			if (i >= self.getSize() - conf.size) {
				next.add(nextPage).addClass(conf.disabledClass);
			} else {
				next.add(nextPage).removeClass(conf.disabledClass);
			}				
		});
		
		
		// hover
		var hc = conf.hoverClass, keyId = "keydown." + Math.random().toString().substring(10); 
			
		self.onReload(function() { 

			// hovering
			if (hc) {
				self.getItems().hover(function()  {
					$(this).addClass(hc);		
				}, function() {
					$(this).removeClass(hc);	
				});						
			}
			
			// clickable
			if (conf.clickable) {
				self.getItems().each(function(i) {
					$(this).unbind("click.scrollable").bind("click.scrollable", function(e) {
						if ($(e.target).is("a")) { return; }	
						return self.click(i);
					});
				});
			}				

			// keyboard			
			if (conf.keyboard) {
				$(document).unbind(keyId); // ADDED INTO LIBRARY

				// keyboard works on one instance at the time. thus we need to unbind first
				$(document).bind(keyId, function(evt) {

					// do nothing with CTRL / ALT buttons
					if (evt.altKey || evt.ctrlKey) { return; }
					
					// do nothing for unstatic and unfocused instances
					if (conf.keyboard != 'static' && current != self) { return; }

					var s = conf.keyboardSteps;				
										
					if (horizontal && (evt.keyCode == 37 || evt.keyCode == 39)) {
						self.move(evt.keyCode == 37 ? -s : s);
						return evt.preventDefault();
					}	
					
					if (!horizontal && (evt.keyCode == 38 || evt.keyCode == 40)) {
						self.move(evt.keyCode == 38 ? -s : s);
						return evt.preventDefault();
					}
					
					return true;
					
				});
				
			} else  {
				$(document).unbind(keyId);	
			}				

		});
		
		self.reload(); 
		
	} 

		
	// jQuery plugin implementation
	$.fn.scrollable = function(conf) { 
			
		// already constructed --> return API
		var el = this.eq(typeof conf == 'number' ? conf : 0).data("scrollable");
		if (el) { return el; }		 
 
		var opts = $.extend({}, $.tools.scrollable.conf);
		$.extend(opts, conf);
		
		opts.keyboardSteps = opts.keyboardSteps || opts.size;
		
		len += this.length;
		
		this.each(function() {			
			el = new Scrollable($(this), opts);
			$(this).data("scrollable", el);	
		});
		
		return opts.api ? el: this; 
		
	};
			
	
})(jQuery);
