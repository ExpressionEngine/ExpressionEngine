/*!
 * jQuery UI Effects Fade @VERSION
 *
 * Copyright 2012, AUTHORS.txt (http://jqueryui.com/about)
 * Dual licensed under the MIT or GPL Version 2 licenses.
 * http://jquery.org/license
 *
 * http://docs.jquery.com/UI/Effects/Fade
 *
 * Depends:
 *	jquery.effects.core.js
 */
!function(e){e.effects.fade=function(t){return this.queue(function(){var n=e(this),i=e.effects.setMode(n,t.options.mode||"hide");n.animate({opacity:i},{queue:!1,duration:t.duration,easing:t.options.easing,complete:function(){t.callback&&t.callback.apply(this,arguments),n.dequeue()}})})}}(jQuery);