/*!
 * jQuery corner plugin: simple corner rounding
 * Examples and documentation at: http://jquery.malsup.com/corner/
 * version 2.01 (08-SEP-2009)
 * Dual licensed under the MIT and GPL licenses:
 * http://www.opensource.org/licenses/mit-license.php
 * http://www.gnu.org/licenses/gpl.html
 * 
 * EE Changes:
 * Changed uncorner to only apply to direct descendants
 * Small code changes to improve compressability
 */

(function(a){function j(a){a=parseInt(a).toString(16);return a.length<2?"0"+a:a}function t(f){for(;f&&f.nodeName.toLowerCase()!="html";f=f.parentNode){var d=a.css(f,"backgroundColor");if(d!="rgba(0, 0, 0, 0)"){if(d.indexOf("rgb")>=0)return f=d.match(/\d+/g),"#"+j(f[0])+j(f[1])+j(f[2]);if(d&&d!="transparent")return d}}return"#ffffff"}function u(a,d,h){switch(a){case "round":return Math.round(h*(1-Math.cos(Math.asin(d/h))))}}var h=a.browser.mozilla&&/gecko/i.test(navigator.userAgent),p=a.browser.safari&&
a.browser.version>=3,q=a.browser.msie&&function(){var a=document.createElement("div");try{a.style.setExpression("width","0+0")}catch(d){return!1}return!0}();a.fn.corner=function(f){if(this.length==0){if(!a.isReady&&this.selector){var d=this.selector,j=this.context;a(function(){a(d,j).corner(f)})}return this}return this.each(function(){var e=a(this),b=(f||e.attr(r.metaAttr)||"").toLowerCase(),d=/keep/.test(b),i=(b.match(/cc:(#[0-9a-f]+)/)||[])[1],g=(b.match(/sc:(#[0-9a-f]+)/)||[])[1],k=parseInt((b.match(/(\d+)px/)||
[])[1])||10,j=(b.match(/round|bevel|notch|bite|cool|sharp|slide|jut|curl|tear|fray|wicked|sculpt|long|dog3|dog2|dog/)||["round"])[0],s={T:0,B:1},b={TL:/top|tl|left/.test(b),TR:/top|tr|right/.test(b),BL:/bottom|bl|left/.test(b),BR:/bottom|br|right/.test(b)};!b.TL&&!b.TR&&!b.BL&&!b.BR&&(b={TL:1,TR:1,BL:1,BR:1});if(r.useNative&&j=="round"&&(h||p)&&!i&&!g)b.TL&&e.css(h?"-moz-border-radius-topleft":"-webkit-border-top-left-radius",k+"px"),b.TR&&e.css(h?"-moz-border-radius-topright":"-webkit-border-top-right-radius",
k+"px"),b.BL&&e.css(h?"-moz-border-radius-bottomleft":"-webkit-border-bottom-left-radius",k+"px"),b.BR&&e.css(h?"-moz-border-radius-bottomright":"-webkit-border-bottom-right-radius",k+"px");else{e=document.createElement("div");e.style.overflow="hidden";e.style.height="1px";e.style.backgroundColor=g||"transparent";e.style.borderStyle="solid";g={T:parseInt(a.css(this,"paddingTop"))||0,R:parseInt(a.css(this,"paddingRight"))||0,B:parseInt(a.css(this,"paddingBottom"))||0,L:parseInt(a.css(this,"paddingLeft"))||
0};if(typeof this.style.zoom!=void 0)this.style.zoom=1;if(!d)this.style.border="none";e.style.borderColor=i||t(this.parentNode);var d=a.curCSS(this,"height"),m;for(m in s)if((i=s[m])&&(b.BL||b.BR)||!i&&(b.TL||b.TR)){e.style.borderStyle="none "+(b[m+"R"]?"solid":"none")+" none "+(b[m+"L"]?"solid":"none");var l=document.createElement("div");a(l).addClass("jquery-corner");var c=l.style;if(this.tagName!="INPUT"){i?this.appendChild(l):this.insertBefore(l,this.firstChild);if(i&&d!="auto"){if(a.css(this,
"position")=="static")this.style.position="relative";c.position="absolute";c.bottom=c.left=c.padding=c.margin="0";q?c.setExpression("width","this.parentNode.offsetWidth"):c.width="100%"}else if(!i&&a.browser.msie){if(a.css(this,"position")=="static")this.style.position="relative";c.position="absolute";c.top=c.left=c.right=c.padding=c.margin="0";if(q){var n=(parseInt(a.css(this,"borderLeftWidth"))||0)+(parseInt(a.css(this,"borderRightWidth"))||0);c.setExpression("width","this.parentNode.offsetWidth - "+
n+'+ "px"')}else c.width="100%"}else c.position="relative",c.margin=!i?"-"+g.T+"px -"+g.R+"px "+(g.T-k)+"px -"+g.L+"px":g.B-k+"px -"+g.R+"px -"+g.B+"px -"+g.L+"px";for(c=0;c<k;c++){var n=Math.max(0,u(j,c,k)),o=e.cloneNode(!1);o.style.borderWidth="0 "+(b[m+"R"]?n:0)+"px 0 "+(b[m+"L"]?n:0)+"px";i?l.appendChild(o):l.insertBefore(o,l.firstChild)}}}}})};a.fn.uncorner=function(){if(h||p)this.css(h?"-moz-border-radius":"-webkit-border-radius",0);this.children("div.jquery-corner").remove();return this};var r=
{useNative:!0,metaAttr:"data-corner"}})(jQuery);
