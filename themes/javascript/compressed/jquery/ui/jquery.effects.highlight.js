/*!
 * jQuery UI Effects Highlight @VERSION
 *
 * Copyright 2012, AUTHORS.txt (http://jqueryui.com/about)
 * Dual licensed under the MIT or GPL Version 2 licenses.
 * http://jquery.org/license
 *
 * http://docs.jquery.com/UI/Effects/Highlight
 *
 * Depends:
 *	jquery.effects.core.js
 */

(function(b,g){b.effects.highlight=function(c){return this.queue(function(){var a=b(this),e=["backgroundImage","backgroundColor","opacity"],d=b.effects.setMode(a,c.options.mode||"show"),f={backgroundColor:a.css("backgroundColor")};"hide"==d&&(f.opacity=0);b.effects.save(a,e);a.show().css({backgroundImage:"none",backgroundColor:c.options.color||"#ffff99"}).animate(f,{queue:!1,duration:c.duration,easing:c.options.easing,complete:function(){"hide"==d&&a.hide();b.effects.restore(a,e);"show"==d&&!b.support.opacity&&
this.style.removeAttribute("filter");c.callback&&c.callback.apply(this,arguments);a.dequeue()}})})}})(jQuery);
