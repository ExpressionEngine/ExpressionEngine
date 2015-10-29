/**
 * @license 
 * jQuery Tools 1.2.1 / Mask - Dim the lights
 * 
 * NO COPYRIGHTS OR LICENSES. DO WHAT YOU LIKE.
 * 
 * http://flowplayer.org/tools/toolbox/mask.html
 *
 * Since: Mar 2010
 * Date:    Tue May 11 09:22:32 2010 +0000 
 */
!function(n){/* one of the greatest headaches in the tool. finally made it */
function o(){
// the horror case
if(n.browser.msie){
// if there are no scrollbars then use window.height
var o=n(document).height(),e=n(window).height();return[window.innerWidth||// ie7+
document.documentElement.clientWidth||// ie6  
document.body.clientWidth,20>o-e?e:o]}
// other well behaving browsers
return[n(window).width(),n(document).height()]}function e(o){return o?o.call(n.mask):void 0}
// static constructs
n.tools=n.tools||{version:"1.2.1"};var t;t=n.tools.expose={conf:{maskId:"exposeMask",loadSpeed:"slow",closeSpeed:"fast",closeOnClick:!0,closeOnEsc:!0,
// css settings
zIndex:9998,opacity:.8,startOpacity:0,color:"#fff",
// callbacks
onLoad:null,onClose:null}};var i,s,c,d,a;n.mask={load:function(r,u){
// already loaded ?
if(c)return this;
// configuration
"string"==typeof r&&(r={color:r}),r=r||d,d=r=n.extend(n.extend({},t.conf),r),i=n("#"+r.maskId),i.length||(i=n("<div/>").attr("id",r.maskId),n("body").append(i));
// set position and dimensions 			
var l=o();i.css({position:"absolute",top:0,left:0,width:l[0],height:l[1],display:"none",opacity:r.startOpacity,zIndex:r.zIndex});
// background color 
var f=i.css("backgroundColor");
// onBeforeLoad
// onBeforeLoad
// esc button
// mask click closes
// resize mask when window is resized
// exposed elements
// make sure element is positioned absolutely or relatively
// make elements sit on top of the mask
// reveal mask
return f&&"transparent"!=f&&"rgba(0, 0, 0, 0)"!=f||i.css("backgroundColor",r.color),e(r.onBeforeLoad)===!1?this:(r.closeOnEsc&&n(document).bind("keydown.mask",function(o){27==o.keyCode&&n.mask.close(o)}),r.closeOnClick&&i.bind("click.mask",function(o){n.mask.close(o)}),n(window).bind("resize.mask",function(){n.mask.fit()}),u&&u.length&&(a=u.eq(0).css("zIndex"),n.each(u,function(){var o=n(this);/relative|absolute|fixed/i.test(o.css("position"))||o.css("position","relative")}),s=u.css({zIndex:Math.max(r.zIndex+1,"auto"==a?0:a)})),i.css({display:"block"}).fadeTo(r.loadSpeed,r.opacity,function(){n.mask.fit(),e(r.onLoad)}),c=!0,this)},close:function(){if(c){
// onBeforeClose
if(e(d.onBeforeClose)===!1)return this;i.fadeOut(d.closeSpeed,function(){e(d.onClose),s&&s.css({zIndex:a})}),
// unbind various event listeners
n(document).unbind("keydown.mask"),i.unbind("click.mask"),n(window).unbind("resize.mask"),c=!1}return this},fit:function(){if(c){var n=o();i.css({width:n[0],height:n[1]})}},getMask:function(){return i},isLoaded:function(){return c},getConf:function(){return d},getExposed:function(){return s}},n.fn.mask=function(o){return n.mask.load(o),this},n.fn.expose=function(o){return n.mask.load(o,this),this}}(jQuery);