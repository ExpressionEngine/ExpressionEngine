/*
 * jQuery UI Position 1.8.1
 *
 * Copyright (c) 2010 AUTHORS.txt (http://jqueryui.com/about)
 * Dual licensed under the MIT (MIT-LICENSE.txt)
 * and GPL (GPL-LICENSE.txt) licenses.
 *
 * http://docs.jquery.com/UI/Position
 */

(function(d){d.ui=d.ui||{};var l=/left|center|right/,m=/top|center|bottom/,n=d.fn.position,o=d.fn.offset;d.fn.position=function(b){if(!b||!b.of)return n.apply(this,arguments);var b=d.extend({},b),a=d(b.of),c=(b.collision||"flip").split(" "),e=b.offset?b.offset.split(" "):[0,0],f,h,g;b.of.nodeType===9?(f=a.width(),h=a.height(),g={top:0,left:0}):b.of.scrollTo&&b.of.document?(f=a.width(),h=a.height(),g={top:a.scrollTop(),left:a.scrollLeft()}):b.of.preventDefault?(b.at="left top",f=h=0,g={top:b.of.pageY,
left:b.of.pageX}):(f=a.outerWidth(),h=a.outerHeight(),g=a.offset());d.each(["my","at"],function(){var a=(b[this]||"").split(" ");a.length===1&&(a=l.test(a[0])?a.concat(["center"]):m.test(a[0])?["center"].concat(a):["center","center"]);a[0]=l.test(a[0])?a[0]:"center";a[1]=m.test(a[1])?a[1]:"center";b[this]=a});c.length===1&&(c[1]=c[0]);e[0]=parseInt(e[0],10)||0;e.length===1&&(e[1]=e[0]);e[1]=parseInt(e[1],10)||0;b.at[0]==="right"?g.left+=f:b.at[0]==="center"&&(g.left+=f/2);b.at[1]==="bottom"?g.top+=
h:b.at[1]==="center"&&(g.top+=h/2);g.left+=e[0];g.top+=e[1];return this.each(function(){var a=d(this),j=a.outerWidth(),k=a.outerHeight(),i=d.extend({},g);b.my[0]==="right"?i.left-=j:b.my[0]==="center"&&(i.left-=j/2);b.my[1]==="bottom"?i.top-=k:b.my[1]==="center"&&(i.top-=k/2);i.left=parseInt(i.left);i.top=parseInt(i.top);d.each(["left","top"],function(a,g){if(d.ui.position[c[a]])d.ui.position[c[a]][g](i,{targetWidth:f,targetHeight:h,elemWidth:j,elemHeight:k,offset:e,my:b.my,at:b.at})});d.fn.bgiframe&&
a.bgiframe();a.offset(d.extend(i,{using:b.using}))})};d.ui.position={fit:{left:function(b,a){var c=d(window),c=b.left+a.elemWidth-c.width()-c.scrollLeft();b.left=c>0?b.left-c:Math.max(0,b.left)},top:function(b,a){var c=d(window),c=b.top+a.elemHeight-c.height()-c.scrollTop();b.top=c>0?b.top-c:Math.max(0,b.top)}},flip:{left:function(b,a){if(a.at[0]!=="center"){var c=d(window),c=b.left+a.elemWidth-c.width()-c.scrollLeft(),e=a.my[0]==="left"?-a.elemWidth:a.my[0]==="right"?a.elemWidth:0,f=-2*a.offset[0];
b.left+=b.left<0?e+a.targetWidth+f:c>0?e-a.targetWidth+f:0}},top:function(b,a){if(a.at[1]!=="center"){var c=d(window),c=b.top+a.elemHeight-c.height()-c.scrollTop(),e=a.my[1]==="top"?-a.elemHeight:a.my[1]==="bottom"?a.elemHeight:0,f=a.at[1]==="top"?a.targetHeight:-a.targetHeight,h=-2*a.offset[1];b.top+=b.top<0?e+a.targetHeight+h:c>0?e+f+h:0}}}};if(!d.offset.setOffset)d.offset.setOffset=function(b,a){if(/static/.test(d.curCSS(b,"position")))b.style.position="relative";var c=d(b),e=c.offset(),f=parseInt(d.curCSS(b,
"top",!0),10)||0,h=parseInt(d.curCSS(b,"left",!0),10)||0,e={top:a.top-e.top+f,left:a.left-e.left+h};"using"in a?a.using.call(b,e):c.css(e)},d.fn.offset=function(b){var a=this[0];return!a||!a.ownerDocument?null:b?this.each(function(){d.offset.setOffset(this,b)}):o.call(this)}})(jQuery);
