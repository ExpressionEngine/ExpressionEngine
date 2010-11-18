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

(function(b){function o(c){c=parseInt(c).toString(16);return c.length<2?"0"+c:c}function w(c){for(;c&&c.nodeName.toLowerCase()!="html";c=c.parentNode){var f=b.css(c,"backgroundColor");if(f!="rgba(0, 0, 0, 0)"){if(f.indexOf("rgb")>=0){c=f.match(/\d+/g);return"#"+o(c[0])+o(c[1])+o(c[2])}if(f&&f!="transparent")return f}}return"#ffffff"}function x(c,f,m){switch(c){case "round":return Math.round(m*(1-Math.cos(Math.asin(f/m))))}}var j=b.browser.mozilla&&/gecko/i.test(navigator.userAgent),r=b.browser.safari&&
b.browser.version>=3,s=b.browser.msie&&function(){var c=document.createElement("div");try{c.style.setExpression("width","0+0")}catch(f){return false}return true}();b.fn.corner=function(c){if(this.length==0){if(!b.isReady&&this.selector){var f=this.selector,m=this.context;b(function(){b(f,m).corner(c)})}return this}return this.each(function(){var e=b(this),a=(c||e.attr(t.metaAttr)||"").toLowerCase(),p=/keep/.test(a),h=(a.match(/cc:(#[0-9a-f]+)/)||[])[1],g=(a.match(/sc:(#[0-9a-f]+)/)||[])[1],i=parseInt((a.match(/(\d+)px/)||
[])[1])||10,u=(a.match(/round|bevel|notch|bite|cool|sharp|slide|jut|curl|tear|fray|wicked|sculpt|long|dog3|dog2|dog/)||["round"])[0],v={T:0,B:1};a={TL:/top|tl|left/.test(a),TR:/top|tr|right/.test(a),BL:/bottom|bl|left/.test(a),BR:/bottom|br|right/.test(a)};if(!a.TL&&!a.TR&&!a.BL&&!a.BR)a={TL:1,TR:1,BL:1,BR:1};if(t.useNative&&u=="round"&&(j||r)&&!h&&!g){if(a.TL)e.css(j?"-moz-border-radius-topleft":"-webkit-border-top-left-radius",i+"px");if(a.TR)e.css(j?"-moz-border-radius-topright":"-webkit-border-top-right-radius",
i+"px");if(a.BL)e.css(j?"-moz-border-radius-bottomleft":"-webkit-border-bottom-left-radius",i+"px");if(a.BR)e.css(j?"-moz-border-radius-bottomright":"-webkit-border-bottom-right-radius",i+"px")}else{e=document.createElement("div");e.style.overflow="hidden";e.style.height="1px";e.style.backgroundColor=g||"transparent";e.style.borderStyle="solid";g={T:parseInt(b.css(this,"paddingTop"))||0,R:parseInt(b.css(this,"paddingRight"))||0,B:parseInt(b.css(this,"paddingBottom"))||0,L:parseInt(b.css(this,"paddingLeft"))||
0};if(typeof this.style.zoom!=undefined)this.style.zoom=1;if(!p)this.style.border="none";e.style.borderColor=h||w(this.parentNode);p=b.curCSS(this,"height");for(var l in v)if((h=v[l])&&(a.BL||a.BR)||!h&&(a.TL||a.TR)){e.style.borderStyle="none "+(a[l+"R"]?"solid":"none")+" none "+(a[l+"L"]?"solid":"none");var k=document.createElement("div");b(k).addClass("jquery-corner");var d=k.style;if(this.tagName!="INPUT"){h?this.appendChild(k):this.insertBefore(k,this.firstChild);if(h&&p!="auto"){if(b.css(this,
"position")=="static")this.style.position="relative";d.position="absolute";d.bottom=d.left=d.padding=d.margin="0";if(s)d.setExpression("width","this.parentNode.offsetWidth");else d.width="100%"}else if(!h&&b.browser.msie){if(b.css(this,"position")=="static")this.style.position="relative";d.position="absolute";d.top=d.left=d.right=d.padding=d.margin="0";if(s){var n=(parseInt(b.css(this,"borderLeftWidth"))||0)+(parseInt(b.css(this,"borderRightWidth"))||0);d.setExpression("width","this.parentNode.offsetWidth - "+
n+'+ "px"')}else d.width="100%"}else{d.position="relative";d.margin=!h?"-"+g.T+"px -"+g.R+"px "+(g.T-i)+"px -"+g.L+"px":g.B-i+"px -"+g.R+"px -"+g.B+"px -"+g.L+"px"}for(d=0;d<i;d++){n=Math.max(0,x(u,d,i));var q=e.cloneNode(false);q.style.borderWidth="0 "+(a[l+"R"]?n:0)+"px 0 "+(a[l+"L"]?n:0)+"px";h?k.appendChild(q):k.insertBefore(q,k.firstChild)}}}}})};b.fn.uncorner=function(){if(j||r)this.css(j?"-moz-border-radius":"-webkit-border-radius",0);this.children("div.jquery-corner").remove();return this};
var t={useNative:true,metaAttr:"data-corner"}})(jQuery);
