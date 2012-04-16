/*!
 * jQuery resize event - v1.1 - 3/14/2010
 * http://benalman.com/projects/jquery-resize-plugin/
 * 
 * Copyright (c) 2010 "Cowboy" Ben Alman
 * Dual licensed under the MIT and GPL licenses.
 * http://benalman.com/about/license/
 */

(function(b,m,k){function g(){h=m[e](function(){c.each(function(){var a=b(this),c=a.width(),d=a.height(),j=b.data(this,i);if(c!==j.w||d!==j.h)a.trigger(f,[j.w=c,j.h=d])});g()},d[l])}var c=b([]),d=b.resize=b.extend(b.resize,{}),h,e="setTimeout",f="resize",i=f+"-special-event",l="delay";d[l]=250;d.throttleWindow=!0;b.event.special[f]={setup:function(){if(!d.throttleWindow&&this[e])return!1;var a=b(this);c=c.add(a);b.data(this,i,{w:a.width(),h:a.height()});c.length===1&&g()},teardown:function(){if(!d.throttleWindow&&
this[e])return!1;var a=b(this);c=c.not(a);a.removeData(i);c.length||clearTimeout(h)},add:function(a){function c(a,d,e){var g=b(this),h=b.data(this,i);h.w=d!==k?d:g.width();h.h=e!==k?e:g.height();f.apply(this,arguments)}if(!d.throttleWindow&&this[e])return!1;var f;if(b.isFunction(a))return f=a,c;else f=a.handler,a.handler=c}}})(jQuery,this);
