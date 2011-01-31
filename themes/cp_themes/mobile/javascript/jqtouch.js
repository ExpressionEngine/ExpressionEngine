/*

            _/    _/_/    _/_/_/_/_/                              _/       
               _/    _/      _/      _/_/    _/    _/    _/_/_/  _/_/_/    
          _/  _/  _/_/      _/    _/    _/  _/    _/  _/        _/    _/   
         _/  _/    _/      _/    _/    _/  _/    _/  _/        _/    _/    
        _/    _/_/  _/    _/      _/_/      _/_/_/    _/_/_/  _/    _/     
       _/                                                                  
    _/

    Created by David Kaneda <http://www.davidkaneda.com>
    Documentation and issue tracking on Google Code <http://code.google.com/p/jqtouch/>
    
    Special thanks to Jonathan Stark <http://jonathanstark.com/>
    and pinch/zoom <http://www.pinchzoom.com/>
    
    (c) 2009 by jQTouch project members.
    See LICENSE.txt for license.
    
*/

(function($) {
    $.jQTouch = function(options) {
        var $body, $head=$('head'), hist=[], newPageCount=0, jQTSettings={}, dumbLoop, currentPage;
        init(options);
        function init(options) {
            var defaults = {
                addGlossToIcon: true,
                backSelector: '.back, .cancel, .goback',
                fixedViewport: true,
                flipSelector: '.flip',
                formSelector: 'form',
                fullScreen: true,
                fullScreenClass: 'fullscreen',
                icon: null,
                initializeTouch: 'a, .touch', 
                slideInSelector: 'ul li a',
                slideUpSelector: '.slideup',
                startupScreen: null,
                statusBar: 'default', // other options: black-translucent, black
                submitSelector: '.submit'
            };

            jQTSettings = $.extend({}, defaults, options)

            var hairextensions;

            // Preload images
            if (jQTSettings.preloadImages) {
                for (var i = jQTSettings.preloadImages.length - 1; i >= 0; i--){
                    (new Image()).src = jQTSettings.preloadImages[i];
                };
            }

            // Set icon
            if (jQTSettings.icon) {
                var precomposed = (jQTSettings.addGlossToIcon) ? '' : '-precomposed';
                hairextensions += '<link rel="apple-touch-icon' + precomposed + '" href="' + jQTSettings.icon + '" />';
            }

            // Set startup screen
            if (jQTSettings.startupScreen) {
                hairextensions += '<link rel="apple-touch-startup-image" href="' + jQTSettings.startupScreen + '" />';
            }

            // Set viewport
            if (jQTSettings.fixedViewport) {
                hairextensions += '<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0;"/>';
            }

            // Set full-screen
            if (jQTSettings.fullScreen) {
                hairextensions += '<meta name="apple-mobile-web-app-capable" content="yes" />';
                if (jQTSettings.statusBar) {
                    hairextensions += '<meta name="apple-mobile-web-app-status-bar-style" content="' + jQTSettings.statusBar + '" />';
                }
            }

            if (hairextensions) $head.append(hairextensions);

            // Create an array of the "next page" selectors
            // TODO: DRY
            var liveSelectors = [];
            if (jQTSettings.backSelector) liveSelectors.push(jQTSettings.backSelector);
            if (jQTSettings.flipSelector) liveSelectors.push(jQTSettings.flipSelector);
            if (jQTSettings.slideInSelector) liveSelectors.push(jQTSettings.slideInSelector);
            if (jQTSettings.slideUpSelector) liveSelectors.push(jQTSettings.slideUpSelector);
            if (liveSelectors.length > 0) {
                $(liveSelectors.join(', ')).live('click',function liveClick(){

                    // Grab the clicked element
                    var $el = $(this);

                    // Add active class no matter what
                    $el.addClass('active');

                    // User clicked an external link
                    if ($el.attr('target') == '_blank') {
                        return true;
                    } 
                    
                    // User clicked a back button
                    if ($el.is(jQTSettings.backSelector)) {
                        $.fn.unselect($el);
                        goBack();
                        return false;
                    }
                    
                    // Set transition
                    var transition = 'slideInOut';
                    if ($el.is(jQTSettings.flipSelector)) transition = 'flip';
                    if ($el.is(jQTSettings.slideUpSelector)) transition = 'slideUp';

                    // Branch on internal or external href
                    var hash = $el.attr('hash');
                    
                    if (hash && hash!='#') {
                        showPage($(hash), transition);
                    } else if ($el.attr('target') != '_blank') {
                        $el.addClass('loading');
                        showPageByHref($el.attr('href'), null, null, null, transition, function(){ $el.removeClass('loading'); setTimeout($.fn.unselect, 250, $el) });
                    }
                    else
                    {
                        $el.unselect();
                    }
                    return false;
                    
                });
            }
            // Initialize on document load:
            $(document).ready(function(){
                
                // TODO: Find best way to customize and make event live...
                $body = $('body');
                $body.bind('orientationchange', updateOrientation).trigger('orientationchange');
                if (jQTSettings.fullScreenClass && window.navigator.standalone == true) {
                    $body.addClass(jQTSettings.fullScreenClass + ' ' + jQTSettings.statusBar);
                }
                
                if (jQTSettings.initializeTouch) $(jQTSettings.initializeTouch).addTouchHandlers();
                $(jQTSettings.formSelector).submit(submitForm);
                
                if (jQTSettings.submitSelector)
                    $(jQTSettings.submitSelector).click(submitParentForm);

                // Make sure exactly one child of body has "current" class
                if ($('body > .current').length == 0) {
                    currentPage = $('body > *:first');
                } else {
                    currentPage = $('body > .current:first');
                    $('body > .current').removeClass('current');
                }
                
                // Go to the top of the "current" page
                $(currentPage).addClass('current');
                location.hash = $(currentPage).attr('id');
                addPageToHistory(currentPage);
                window.scrollTo(0, 0);
                dumbLoopStart();
                
            });
        }
        function addPageToHistory(page, transition) {

            // Grab some info
            var pageId = page.attr('id');

            // Prepend info to page history
            hist.unshift({
                page: page, 
                transition: transition, 
                id: pageId
            });

        }
        function animatePages(fromPage, toPage, transition, backwards) {

            // Error check for target page
            if(toPage.length == 0){
                $.fn.unselect();
                console.log('Target element is missing.');
                return false;
            }

            // Make sure we are scrolled up to hide location bar
            window.scrollTo(0, 0);
            
            // Define callback to run after animation completes
            var callback = function(event){
                currentPage = toPage;
                fromPage.removeClass('current');
                toPage.trigger('pageTransitionEnd', { direction: 'in' });
    	        fromPage.trigger('pageTransitionEnd', { direction: 'out' });
                location.hash = $(currentPage).attr('id');
                $.fn.unselect();
    	        dumbLoopStart();
            }
            
            fromPage.trigger('pageTransitionStart', { direction: 'out' });
            toPage.trigger('pageTransitionStart', { direction: 'in' });

            // Branch on type transition
            if (transition == 'flip'){
                toPage.flip({backwards: backwards});
                fromPage.flip({backwards: backwards, callback: callback});
            } else if (transition == 'slideUp') {
                if (backwards) {
                    toPage.addClass('current');
                    fromPage.slideUpDown({backwards: backwards, callback: callback});
                } else {
                    toPage.css('z-index', Number(fromPage.css('z-index'))+1); // Make sure incoming page is in front of from page (only matters for slide up because from page doesn't move)
                    toPage.slideUpDown({backwards: backwards, callback: callback});
                }
            } else {
                toPage.slideInOut({backwards: backwards, callback: callback});
                fromPage.slideInOut({backwards: backwards});
            }
            
            return true;
        }
        function dumbLoopStart() {
            dumbLoop = setInterval(function(){
                if (location.hash == '') {
                    location.hash = $(currentPage).attr('id');
                }
                if(location.hash != '#' + $(currentPage).attr('id')) {
                    try {
                        for (var i=1; i < hist.length; i++) {
                            if(location.hash == '#' + hist[i].id) {
                                foundBack = true;
                                dumbLoopStop();
                                goBack(i);
                            }
                        }
                    } catch(e) {
                        
                    }
                }
            }, 250);
        }
        function dumbLoopStop() {
            clearInterval(dumbLoop);
        }
        function goBack(numberOfPages) {

            // Init the param
            var numberOfPages = numberOfPages || 1;

            // Grab the current page for the "from" info
            var transition = hist[0].transition;
            var fromPage = hist[0].page;

            // Remove all pages in front of the target page
            hist.splice(0, numberOfPages);

            // Grab the target page
            var toPage = hist[0].page;

            // Make the transition
            animatePages(fromPage, toPage, transition, true);
        }
        function insertPages(nodes, transition) {

            var targetPage;
            nodes.each(function(index, node){
                if (!$(this).attr('id')) {
                    $(this).attr('id', (++newPageCount));
                }
                $(this).appendTo($body);
                if ($(this).hasClass('current') || !targetPage ) {
                    targetPage = $(this);
                }
            });
            if (targetPage) {
                showPage(targetPage, transition);
            }
        }
        function showPage(toPage, transition) {
            var fromPage = hist[0].page;
            
            if (animatePages(fromPage, toPage, transition)) addPageToHistory(toPage, transition);
        }
        function showPageByHref(href, data, method, replace, transition, cb) {
            if (href != '#')
            {
                $.ajax({
                    url: href,
                    data: data,
                    type: method || 'GET',
                    success: function (data, textStatus) {
                        insertPages($(data), transition);

                        if (cb) {
                            cb(true);
                        }
                    },
                    error: function (data) {
                        $.fn.unselect();
                        console.log('AJAX error');
                        
                        if (cb) {
                            cb(false);
                        }
                    }
                });
            }
            else
            {
                $.fn.unselect();
            }
        }
        function submitParentForm(){
            $(this).parent('form').submit();
        }

		/**
		 * Removed return false; so pages in the control panel will redirect to 
		 * the proper pages.
		 */
        function submitForm() {
            showPageByHref($(this).attr('action') || "POST", $(this).serialize(), $(this).attr('method'));
            // return false;
        }
        function updateOrientation() {
            var newOrientation = window.innerWidth < window.innerHeight ? 'profile' : 'landscape';
            $body.removeClass('profile landscape').addClass(newOrientation).trigger('turn', {orientation: newOrientation});
            scrollTo(0, 0);
        }
    }
    $.fn.flip = function(options) {
        return this.each(function(){
            var defaults = {
                direction : 'toggle',
                backwards: false,
                callback: null
            };

            var settings = $.extend({}, defaults, options);

            var dir = ((settings.direction == 'toggle' && $(this).hasClass('current')) || settings.direction == 'out') ? 1 : -1;
            
            if (dir == -1) $(this).addClass('current');
            
            $(this).css({
                '-webkit-transform': 'scale(' + ((dir==1)? '1' : '.8' ) + ') rotateY(' + ((dir == 1) ? '0' : (!settings.backwards ? '-' : '') + '180') + 'deg)'
            }).transition({'-webkit-transform': 'scale(' + ((dir == 1) ? '.8' : '1' ) + ') rotateY(' + ((dir == 1) ? (settings.backwards ? '-' : '') + '180' : '0') + 'deg)'}, {callback: settings.callback, speed: '800ms'});
        })
    }
    $.fn.slideInOut = function(options) {
        var defaults = {
            direction : 'toggle',
            backwards: false,
            callback: null
        };
        var settings = $.extend({}, defaults, options);
        return this.each(function(){
            var dir = ((settings.direction == 'toggle' && $(this).hasClass('current')) || settings.direction == 'out') ? 1 : -1;                
            if (dir == -1){
                $(this).addClass('current')
                    .find('h1, .button')
                        .css('opacity', 0)
                        .transition({'opacity': 1})
                        .end()
                    .css({'-webkit-transform': 'translate3d(' + (settings.backwards ? -1 : 1) * window.innerWidth + 'px, 0, 0)'})
                    .transition({'-webkit-transform': 'translate3d(0, 0, 0)'}, {callback: settings.callback})
            } else {
                $(this)
                    .find('h1, .button')
                        .transition( {'opacity': 0} )
                        .end()
                    .transition(
                        {'-webkit-transform': 'translate3d(' + ((settings.backwards ? 1 : -1) * dir * window.innerWidth) + 'px, 0, 0)'}, { callback: settings.callback});
            }
        })
    }
    $.fn.slideUpDown = function(options) {
        var defaults = {
            direction : 'toggle',
            backwards: false,
            callback: null
        };

        var settings = $.extend({}, defaults, options);
        
        return this.each(function(){

            var dir = ((settings.direction == 'toggle' && $(this).hasClass('current')) || settings.direction == 'out') ? 1 : -1;                

            if (dir == -1){
                $(this).addClass('current')
                    .css({'-webkit-transform': 'translate3d(0, ' + (settings.backwards ? -1 : 1) * window.innerHeight + 'px, 0)'})
                    .transition({'-webkit-transform': 'translate3d(0, 0, 0)'}, {callback: settings.callback})
                        .find('h1, .button')
                        .css('opacity', 0)
                        .transition({'opacity': 1});
            } else {
                $(this)
                    .transition(
                        {'-webkit-transform': 'translate3d(0, ' + window.innerHeight + 'px, 0)'}, {callback: settings.callback})
                    .find('h1, .button')
                        .transition( {'opacity': 0});
            }

        })
    }
    $.fn.transition = function(css, options) {
        var $el = $(this);
        var defaults = {
            speed : '300ms',
            callback: null,
            ease: 'ease-in-out',
        };
        var settings = $.extend({}, defaults, options);
        if(settings.speed === 0) { // differentiate 0 from null
            $el.css(css);
            window.setTimeout(callback, 0);
        } else {
            if ($.browser.safari)
            {
                var s = [];
                for(var i in css) {
                    s.push(i);
                }
                $el.css({
                    webkitTransitionProperty: s.join(", "), 
                    webkitTransitionDuration: settings.speed, 
                    webkitTransitionTimingFunction: settings.ease
                });
                if (settings.callback) {
                    $el.one('webkitTransitionEnd', settings.callback);
                }
                setTimeout(function(el){ el.css(css) }, 0, $el);

            }
            else
            {
                $el.animate(css, settings.speed, settings.callback);
            }
            return this;
        }
    }
    $.fn.unselect = function(obj) {
        if (obj) {
            obj.removeClass('active');
        } else {
            $('.active').removeClass('active');
        }
    }
})(jQuery);



// jQTouch Events handler

(function($) {
    
    var jQTouchHandler = {
        
        currentTouch : {},

        handleStart : function(e){
            
            jQTouchHandler.currentTouch = {
                startX : event.changedTouches[0].clientX,
                startY : event.changedTouches[0].clientY,
                startTime : (new Date).getTime(),
                deltaX : 0,
                deltaY : 0,
                deltaT : 0
            };

            $(this).addClass('active').bind('touchmove touchend', jQTouchHandler.handle);
            return true;
        },
        
        handle : function(e){
            var touches = event.changedTouches,
            first = touches[0] || null,
            type = '';

            switch(event.type)
            {
                case 'touchmove':
                    jQTouchHandler.currentTouch.deltaX = first.pageX - jQTouchHandler.currentTouch.startX;
                    jQTouchHandler.currentTouch.deltaY = first.pageY - jQTouchHandler.currentTouch.startY;
                    jQTouchHandler.currentTouch.deltaT = (new Date).getTime() - jQTouchHandler.currentTouch.startTime;
                    
                    if (Math.abs(jQTouchHandler.currentTouch.deltaX) > Math.abs(jQTouchHandler.currentTouch.deltaY) && (jQTouchHandler.currentTouch.deltaX > 35 || jQTouchHandler.currentTouch.deltaX < -35) && jQTouchHandler.currentTouch.deltaT < 1000)
                    {
                        $(this).trigger('swipe', {direction: (jQTouchHandler.currentTouch.deltaX < 0) ? 'left' : 'right'}).unbind('touchmove touchend');
                    }
                    
                    type = 'mousemove';
                break;

                case 'touchend':
                    if (!jQTouchHandler.currentTouch.deltaY && !jQTouchHandler.currentTouch.deltaX)
                    {
                        type = 'mouseup';
                        // TODO: Remove this selected!
                        $(this).trigger('tap');
                    }
                    else
                    {
                        $(this).removeClass('active');
                    }
                    $(this).unbind('touchmove touchend');
                    delete currentTouch;
                break;
            }
            if (type != '' && first)
            {
                $(this).trigger(type);
                // return false;
            }
        }
    }

    $.fn.addTouchHandlers = function()
    {
        return this.each(function(i, el){
            $(el).bind('touchstart', jQTouchHandler.handleStart);  
        });
    }
})(jQuery);