/*
 * jQuery UI Position 1.8.1
 *
 * Copyright (c) 2010 AUTHORS.txt (http://jqueryui.com/about)
 * Dual licensed under the MIT (MIT-LICENSE.txt)
 * and GPL (GPL-LICENSE.txt) licenses.
 *
 * http://docs.jquery.com/UI/Position
 */

(function(d){d.ui=d.ui||{};var m=/left|center|right/,n=/top|center|bottom/,p=d.fn.position,q=d.fn.offset;d.fn.position=function(a){if(!a||!a.of)return p.apply(this,arguments);a=d.extend({},a);var b=d(a.of),c=(a.collision||"flip").split(" "),e=a.offset?a.offset.split(" "):[0,0],g,h,i;if(a.of.nodeType===9){g=b.width();h=b.height();i={top:0,left:0}}else if(a.of.scrollTo&&a.of.document){g=b.width();h=b.height();i={top:b.scrollTop(),left:b.scrollLeft()}}else if(a.of.preventDefault){a.at="left top";g=h=
0;i={top:a.of.pageY,left:a.of.pageX}}else{g=b.outerWidth();h=b.outerHeight();i=b.offset()}d.each(["my","at"],function(){var f=(a[this]||"").split(" ");if(f.length===1)f=m.test(f[0])?f.concat(["center"]):n.test(f[0])?["center"].concat(f):["center","center"];f[0]=m.test(f[0])?f[0]:"center";f[1]=n.test(f[1])?f[1]:"center";a[this]=f});if(c.length===1)c[1]=c[0];e[0]=parseInt(e[0],10)||0;if(e.length===1)e[1]=e[0];e[1]=parseInt(e[1],10)||0;if(a.at[0]==="right")i.left+=g;else if(a.at[0]==="center")i.left+=
g/2;if(a.at[1]==="bottom")i.top+=h;else if(a.at[1]==="center")i.top+=h/2;i.left+=e[0];i.top+=e[1];return this.each(function(){var f=d(this),k=f.outerWidth(),l=f.outerHeight(),j=d.extend({},i);if(a.my[0]==="right")j.left-=k;else if(a.my[0]==="center")j.left-=k/2;if(a.my[1]==="bottom")j.top-=l;else if(a.my[1]==="center")j.top-=l/2;j.left=parseInt(j.left);j.top=parseInt(j.top);d.each(["left","top"],function(o,r){d.ui.position[c[o]]&&d.ui.position[c[o]][r](j,{targetWidth:g,targetHeight:h,elemWidth:k,
elemHeight:l,offset:e,my:a.my,at:a.at})});d.fn.bgiframe&&f.bgiframe();f.offset(d.extend(j,{using:a.using}))})};d.ui.position={fit:{left:function(a,b){var c=d(window);c=a.left+b.elemWidth-c.width()-c.scrollLeft();a.left=c>0?a.left-c:Math.max(0,a.left)},top:function(a,b){var c=d(window);c=a.top+b.elemHeight-c.height()-c.scrollTop();a.top=c>0?a.top-c:Math.max(0,a.top)}},flip:{left:function(a,b){if(b.at[0]!=="center"){var c=d(window);c=a.left+b.elemWidth-c.width()-c.scrollLeft();var e=b.my[0]==="left"?
-b.elemWidth:b.my[0]==="right"?b.elemWidth:0,g=-2*b.offset[0];a.left+=a.left<0?e+b.targetWidth+g:c>0?e-b.targetWidth+g:0}},top:function(a,b){if(b.at[1]!=="center"){var c=d(window);c=a.top+b.elemHeight-c.height()-c.scrollTop();var e=b.my[1]==="top"?-b.elemHeight:b.my[1]==="bottom"?b.elemHeight:0,g=b.at[1]==="top"?b.targetHeight:-b.targetHeight,h=-2*b.offset[1];a.top+=a.top<0?e+b.targetHeight+h:c>0?e+g+h:0}}}};if(!d.offset.setOffset){d.offset.setOffset=function(a,b){if(/static/.test(d.curCSS(a,"position")))a.style.position=
"relative";var c=d(a),e=c.offset(),g=parseInt(d.curCSS(a,"top",true),10)||0,h=parseInt(d.curCSS(a,"left",true),10)||0;e={top:b.top-e.top+g,left:b.left-e.left+h};"using"in b?b.using.call(a,e):c.css(e)};d.fn.offset=function(a){var b=this[0];if(!b||!b.ownerDocument)return null;if(a)return this.each(function(){d.offset.setOffset(this,a)});return q.call(this)}}})(jQuery);
