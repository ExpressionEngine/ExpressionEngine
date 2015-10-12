/*!
 * Nestable jQuery Plugin - Copyright (c) 2012 David Bushell - http://dbushell.com/
 * Dual-licensed under the BSD or MIT licenses
 */
!function(t,s,e,i){function a(s,i){this.w=t(e),this.el=t(s),this.options=t.extend({},n,i),this.init()}var o="ontouchstart"in e,l=function(){var t=e.createElement("div"),i=e.documentElement;if(!("pointerEvents"in t.style))return!1;t.style.pointerEvents="auto",t.style.pointerEvents="x",i.appendChild(t);var a=s.getComputedStyle&&"auto"===s.getComputedStyle(t,"").pointerEvents;return i.removeChild(t),!!a}(),n={listNodeName:"ol",itemNodeName:"li",rootClass:"dd",listClass:"dd-list",itemClass:"dd-item",dragClass:"dd-dragel",handleClass:"dd-handle",collapsedClass:"dd-collapsed",placeClass:"dd-placeholder",noDragClass:"dd-nodrag",emptyClass:"dd-empty",expandBtnHTML:'<button data-action="expand" type="button">Expand</button>',collapseBtnHTML:'<button data-action="collapse" type="button">Collapse</button>',group:0,maxDepth:5,threshold:20};a.prototype={init:function(){var e=this;e.reset(),e.el.data("nestable-group",this.options.group),this.options.placeElement!==i?e.placeEl=this.options.placeElement:e.placeEl=t('<div class="'+e.options.placeClass+'"/>'),t.each(this.el.find(e.options.itemNodeName+"."+e.options.itemClass),function(s,i){e.setParent(t(i))}),e.el.on("click","button",function(s){if(!e.dragEl){var i=t(s.currentTarget),a=i.data("action"),o=i.parent(e.options.itemNodeName+"."+e.options.itemClass);"collapse"===a&&e.collapseItem(o),"expand"===a&&e.expandItem(o)}});var a=function(s){var i=t(s.target);if(!i.hasClass(e.options.handleClass)){if(i.closest("."+e.options.noDragClass).length)return;i=i.closest("."+e.options.handleClass)}i.length&&!e.dragEl&&(e.isTouch=/^touch/.test(s.type),e.isTouch&&1!==s.touches.length||(s.preventDefault(),e.dragStart(s.touches?s.touches[0]:s)))},l=function(t){e.dragEl&&(t.preventDefault(),e.dragMove(t.touches?t.touches[0]:t))},n=function(t){e.dragEl&&(t.preventDefault(),e.dragStop(t.touches?t.touches[0]:t))};o&&(e.el[0].addEventListener("touchstart",a,!1),s.addEventListener("touchmove",l,!1),s.addEventListener("touchend",n,!1),s.addEventListener("touchcancel",n,!1)),e.el.on("mousedown",a),e.w.on("mousemove",l),e.w.on("mouseup",n)},serialize:function(){var s,e=0,i=this;return step=function(s,e){var a=[],o=s.children(i.options.itemNodeName+"."+i.options.itemClass);return o.each(function(){var s=t(this),o=t.extend({},s.data()),l=s.children(i.options.listNodeName+"."+i.options.listClass);l.length&&(o.children=step(l,e+1)),a.push(o)}),a},s=step(i.el.find(i.options.listNodeName+"."+i.options.listClass).first(),e)},serialise:function(){return this.serialize()},reset:function(){this.mouse={offsetX:0,offsetY:0,startX:0,startY:0,lastX:0,lastY:0,nowX:0,nowY:0,distX:0,distY:0,dirAx:0,dirX:0,dirY:0,lastDirX:0,lastDirY:0,distAxX:0,distAxY:0},this.isTouch=!1,this.moving=!1,this.dragEl=null,this.dragRootEl=null,this.dragDepth=0,this.hasNewRoot=!1,this.pointEl=null},expandItem:function(t){t.removeClass(this.options.collapsedClass),t.children('[data-action="expand"]').hide(),t.children('[data-action="collapse"]').show(),t.children(this.options.listNodeName+"."+this.options.listClass).show()},collapseItem:function(t){var s=t.children(this.options.listNodeName+"."+this.options.listClass);s.length&&(t.addClass(this.options.collapsedClass),t.children('[data-action="collapse"]').hide(),t.children('[data-action="expand"]').show(),t.children(this.options.listNodeName+"."+this.options.listClass).hide())},expandAll:function(){var s=this;s.el.find(s.options.itemNodeName+"."+s.options.itemClass).each(function(){s.expandItem(t(this))})},collapseAll:function(){var s=this;s.el.find(s.options.itemNodeName+"."+s.options.itemClass).each(function(){s.collapseItem(t(this))})},setParent:function(s){s.children(this.options.listNodeName+"."+this.options.listClass).length&&(s.prepend(t(this.options.expandBtnHTML)),s.prepend(t(this.options.collapseBtnHTML))),s.children('[data-action="expand"]').hide()},unsetParent:function(t){t.removeClass(this.options.collapsedClass),t.children("[data-action]").remove(),t.children(this.options.listNodeName+"."+this.options.listClass).remove()},dragStart:function(s){var a=this.mouse,o=t(s.target),l=o.closest(this.options.itemNodeName+"."+this.options.itemClass);this.placeEl.css("height",l.height()),a.offsetX=s.offsetX!==i?s.offsetX:s.pageX-o.offset().left,a.offsetY=s.offsetY!==i?s.offsetY:s.pageY-o.offset().top,a.startX=a.lastX=s.pageX,a.startY=a.lastY=s.pageY,this.dragRootEl=this.el,this.dragEl=t(e.createElement(this.options.listNodeName)).addClass(this.options.listClass+" "+this.options.dragClass),this.dragEl.css("width",l.width()),l.after(this.placeEl),l[0].parentNode.removeChild(l[0]),l.appendTo(this.dragEl),t(e.body).append(this.dragEl),this.dragEl.css({left:s.pageX-a.offsetX,top:s.pageY-a.offsetY});
// total depth of dragging item
var n,d,h=this.dragEl.find(this.options.itemNodeName+"."+this.options.itemClass);for(n=0;n<h.length;n++)d=t(h[n]).parents(this.options.listNodeName+"."+this.options.listClass).length,d>this.dragDepth&&(this.dragDepth=d)},dragStop:function(t){var s=this.dragEl.children(this.options.itemNodeName+"."+this.options.itemClass).first();s[0].parentNode.removeChild(s[0]),this.placeEl.replaceWith(s),this.dragEl.remove(),this.el.trigger("change"),this.hasNewRoot&&this.dragRootEl.trigger("change"),this.reset()},dragMove:function(i){var a,o,n,d,h,r=this.options,p=this.mouse;this.dragEl.css({left:i.pageX-p.offsetX,top:i.pageY-p.offsetY}),
// mouse position last events
p.lastX=p.nowX,p.lastY=p.nowY,
// mouse position this events
p.nowX=i.pageX,p.nowY=i.pageY,
// distance mouse moved between events
p.distX=p.nowX-p.lastX,p.distY=p.nowY-p.lastY,
// direction mouse was moving
p.lastDirX=p.dirX,p.lastDirY=p.dirY,
// direction mouse is now moving (on both axis)
p.dirX=0===p.distX?0:p.distX>0?1:-1,p.dirY=0===p.distY?0:p.distY>0?1:-1;
// axis mouse is now moving on
var c=Math.abs(p.distX)>Math.abs(p.distY)?1:0;
// do nothing on first move
if(!p.moving)return p.dirAx=c,void(p.moving=!0);
// calc distance moved on this axis (and direction)
p.dirAx!==c?(p.distAxX=0,p.distAxY=0):(p.distAxX+=Math.abs(p.distX),0!==p.dirX&&p.dirX!==p.lastDirX&&(p.distAxX=0),p.distAxY+=Math.abs(p.distY),0!==p.dirY&&p.dirY!==p.lastDirY&&(p.distAxY=0)),p.dirAx=c,/**
             * move horizontal
             */
p.dirAx&&p.distAxX>=r.threshold&&(
// reset move distance on x-axis for new phase
p.distAxX=0,n=this.placeEl.prev(r.itemNodeName+"."+r.itemClass),
// increase horizontal level if previous sibling exists and is not collapsed
p.distX>0&&n.length&&!n.hasClass(r.collapsedClass)&&(
// cannot increase level when item above is collapsed
a=n.find(r.listNodeName+"."+r.listClass).last(),
// check if depth limit has reached
h=this.placeEl.parents(r.listNodeName+"."+r.listClass).length,h+this.dragDepth<=r.maxDepth&&(
// create new sub-level if one doesn't exist
a.length?(
// else append to next level up
a=n.children(r.listNodeName+"."+r.listClass).last(),a.append(this.placeEl)):(a=t("<"+r.listNodeName+"/>").addClass(r.listClass),a.append(this.placeEl),n.append(a),this.setParent(n)))),
// decrease horizontal level
p.distX<0&&(
// we can't decrease a level if an item preceeds the current one
d=this.placeEl.next(r.itemNodeName+"."+r.itemClass),d.length||(o=this.placeEl.parent(),this.placeEl.closest(r.itemNodeName+"."+r.itemClass).after(this.placeEl),o.children().length||this.unsetParent(o.parent()))));var m=!1;if(
// find list item under cursor
l||(this.dragEl[0].style.visibility="hidden"),this.pointEl=t(e.elementFromPoint(i.pageX-e.body.scrollLeft,i.pageY-(s.pageYOffset||e.documentElement.scrollTop))),l||(this.dragEl[0].style.visibility="visible"),this.pointEl.hasClass(r.handleClass)&&(this.pointEl=this.pointEl.closest(r.itemNodeName+"."+r.itemClass)),this.pointEl.hasClass(r.emptyClass))m=!0;else if(!this.pointEl.length||!this.pointEl.hasClass(r.itemClass))return;
// find parent list of item under cursor
var g=this.pointEl.closest("."+r.rootClass),f=this.dragRootEl.data("nestable-id")!==g.data("nestable-id");/**
             * move vertical
             */
if(!p.dirAx||f||m){
// check if groups match if dragging over new root
if(f&&r.group!==g.data("nestable-group"))return;if(
// check depth limit
h=this.dragDepth-1+this.pointEl.parents(r.listNodeName+"."+r.listClass).length,h>r.maxDepth)return;var u=i.pageY<this.pointEl.offset().top+this.pointEl.height()/2;o=this.placeEl.parent(),
// if empty create new list to replace empty placeholder
m?(a=t(e.createElement(r.listNodeName)).addClass(r.listClass),a.append(this.placeEl),this.pointEl.replaceWith(a)):u?this.pointEl.before(this.placeEl):this.pointEl.after(this.placeEl),o.children().length||this.unsetParent(o.parent()),this.dragRootEl.find(r.itemNodeName+"."+r.itemClass).length||this.dragRootEl.append('<div class="'+r.emptyClass+'"/>'),
// parent root list has changed
f&&(this.dragRootEl=g,this.hasNewRoot=this.el[0]!==this.dragRootEl[0])}}},t.fn.nestable=function(s){var e=this,i=this;return e.each(function(){var e=t(this).data("nestable");e?"string"==typeof s&&"function"==typeof e[s]&&(i=e[s]()):(t(this).data("nestable",new a(this,s)),t(this).data("nestable-id",(new Date).getTime()))}),i||e}}(window.jQuery||window.Zepto,window,document);