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

(function(a){var o,p;function g(a){a=parseInt(a).toString(16);return a.length<2?"0"+a:a}function x(f){for(;f&&f.nodeName.toLowerCase()!="html";f=f.parentNode){var d=a.css(f,"backgroundColor");if(d!="rgba(0, 0, 0, 0)"){if(d.indexOf("rgb")>=0)return f=d.match(/\d+/g),"#"+g(f[0])+g(f[1])+g(f[2]);if(d&&d!="transparent")return d}}return"#ffffff"}function y(a,d,h){switch(a){case "round":return Math.round(h*(1-Math.cos(Math.asin(d/h))))}}var h=a.browser.mozilla&&/gecko/i.test(navigator.userAgent),q=a.browser.safari&&
a.browser.version>=3,r=a.browser.msie&&function(){var a=document.createElement("div");try{a.style.setExpression("width","0+0")}catch(d){return!1}return!0}();a.fn.corner=function(f){if(this.length==0){if(!a.isReady&&this.selector){var d=this.selector,g=this.context;a(function(){a(d,g).corner(f)})}return this}return this.each(function(){var e=a(this),b=(f||e.attr(o)||"").toLowerCase(),d=/keep/.test(b),i=(b.match(/cc:(#[0-9a-f]+)/)||[])[1],g=(b.match(/sc:(#[0-9a-f]+)/)||[])[1],j=parseInt((b.match(/(\d+)px/)||
[])[1])||10,s=(b.match(/round|bevel|notch|bite|cool|sharp|slide|jut|curl|tear|fray|wicked|sculpt|long|dog3|dog2|dog/)||["round"])[0],t={T:0,B:1},b={TL:/top|tl|left/.test(b),TR:/top|tr|right/.test(b),BL:/bottom|bl|left/.test(b),BR:/bottom|br|right/.test(b)};!b.TL&&!b.TR&&!b.BL&&!b.BR&&(b={TL:1,TR:1,BL:1,BR:1});if(p&&s=="round"&&(h||q)&&!i&&!g)b.TL&&e.css(h?"-moz-border-radius-topleft":"-webkit-border-top-left-radius",j+"px"),b.TR&&e.css(h?"-moz-border-radius-topright":"-webkit-border-top-right-radius",
j+"px"),b.BL&&e.css(h?"-moz-border-radius-bottomleft":"-webkit-border-bottom-left-radius",j+"px"),b.BR&&e.css(h?"-moz-border-radius-bottomright":"-webkit-border-bottom-right-radius",j+"px");else{e=document.createElement("div");e.style.overflow="hidden";e.style.height="1px";e.style.backgroundColor=g||"transparent";e.style.borderStyle="solid";var g=parseInt(a.css(this,"paddingTop"))||0,u=parseInt(a.css(this,"paddingRight"))||0,v=parseInt(a.css(this,"paddingBottom"))||0,w=parseInt(a.css(this,"paddingLeft"))||
0;if(typeof this.style.zoom!=void 0)this.style.zoom=1;if(!d)this.style.border="none";e.style.borderColor=i||x(this.parentNode);var d=a.curCSS(this,"height"),l;for(l in t)if((i=t[l])&&(b.BL||b.BR)||!i&&(b.TL||b.TR)){e.style.borderStyle="none "+(b[l+"R"]?"solid":"none")+" none "+(b[l+"L"]?"solid":"none");var k=document.createElement("div");a(k).addClass("jquery-corner");var c=k.style;if(this.tagName!="INPUT"){i?this.appendChild(k):this.insertBefore(k,this.firstChild);if(i&&d!="auto"){if(a.css(this,
"position")=="static")this.style.position="relative";c.position="absolute";c.bottom=c.left=c.padding=c.margin="0";r?c.setExpression("width","this.parentNode.offsetWidth"):c.width="100%"}else if(!i&&a.browser.msie){if(a.css(this,"position")=="static")this.style.position="relative";c.position="absolute";c.top=c.left=c.right=c.padding=c.margin="0";if(r){var m=(parseInt(a.css(this,"borderLeftWidth"))||0)+(parseInt(a.css(this,"borderRightWidth"))||0);c.setExpression("width","this.parentNode.offsetWidth - "+
m+'+ "px"')}else c.width="100%"}else c.position="relative",c.margin=!i?"-"+g+"px -"+u+"px "+(g-j)+"px -"+w+"px":v-j+"px -"+u+"px -"+v+"px -"+w+"px";for(c=0;c<j;c++){var m=Math.max(0,y(s,c,j)),n=e.cloneNode(!1);n.style.borderWidth="0 "+(b[l+"R"]?m:0)+"px 0 "+(b[l+"L"]?m:0)+"px";i?k.appendChild(n):k.insertBefore(n,k.firstChild)}}}}})};a.fn.uncorner=function(){if(h||q)this.css(h?"-moz-border-radius":"-webkit-border-radius",0);this.children("div.jquery-corner").remove();return this};p=!0;o="data-corner"})(jQuery);
