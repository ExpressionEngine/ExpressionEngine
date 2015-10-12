/*!
 * jQuery UI Sortable @VERSION
 * http://jqueryui.com
 *
 * Copyright 2014 jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 *
 * http://api.jqueryui.com/sortable/
 */
!function(t){"function"==typeof define&&define.amd?
// AMD. Register as an anonymous module.
define(["jquery","./core","./mouse","./widget"],t):
// Browser globals
t(jQuery)}(function(t){return t.widget("ui.sortable",t.ui.mouse,{version:"@VERSION",widgetEventPrefix:"sort",ready:!1,options:{appendTo:"parent",axis:!1,connectWith:!1,containment:!1,cursor:"auto",cursorAt:!1,dropOnEmpty:!0,forcePlaceholderSize:!1,forceHelperSize:!1,grid:!1,handle:!1,helper:"original",items:"> *",opacity:!1,placeholder:!1,revert:!1,scroll:!0,scrollSensitivity:20,scrollSpeed:20,scope:"default",tolerance:"intersect",zIndex:1e3,
// callbacks
activate:null,beforeStop:null,change:null,deactivate:null,out:null,over:null,receive:null,remove:null,sort:null,start:null,stop:null,update:null},_isOverAxis:function(t,e,i){return t>=e&&e+i>t},_isFloating:function(t){return/left|right/.test(t.css("float"))||/inline|table-cell/.test(t.css("display"))},_create:function(){var t=this.options;this.containerCache={},this.element.addClass("ui-sortable"),
//Get the items
this.refresh(),
//Let's determine if the items are being displayed horizontally
this.floating=this.items.length?"x"===t.axis||this._isFloating(this.items[0].item):!1,
//Let's determine the parent's offset
this.offset=this.element.offset(),
//Initialize mouse events for interaction
this._mouseInit(),this._setHandleClassName(),
//We're ready to go
this.ready=!0},_setOption:function(t,e){this._super(t,e),"handle"===t&&this._setHandleClassName()},_setHandleClassName:function(){this.element.find(".ui-sortable-handle").removeClass("ui-sortable-handle"),t.each(this.items,function(){(this.instance.options.handle?this.item.find(this.instance.options.handle):this.item).addClass("ui-sortable-handle")})},_destroy:function(){this.element.removeClass("ui-sortable ui-sortable-disabled").find(".ui-sortable-handle").removeClass("ui-sortable-handle"),this._mouseDestroy();for(var t=this.items.length-1;t>=0;t--)this.items[t].item.removeData(this.widgetName+"-item");return this},_mouseCapture:function(e,i){var s=null,o=!1,r=this;
//We have to refresh the items data once first
//Find out if the clicked node (or one of its parents) is a actual item in this.items
return this.reverting?!1:this.options.disabled||"static"===this.options.type?!1:(this._refreshItems(e),t(e.target).parents().each(function(){return t.data(this,r.widgetName+"-item")===r?(s=t(this),!1):void 0}),t.data(e.target,r.widgetName+"-item")===r&&(s=t(e.target)),s&&(!this.options.handle||i||(t(this.options.handle,s).find("*").addBack().each(function(){this===e.target&&(o=!0)}),o))?(this.currentItem=s,this._removeCurrentsFromItems(),!0):!1)},_mouseStart:function(e,i,s){var o,r,n=this.options;
//Post "activate" events to possible containers
if(this.currentContainer=this,
//We only need to call refreshPositions, because the refreshItems call has been moved to mouseCapture
this.refreshPositions(),
//Create and append the visible helper
this.helper=this._createHelper(e),
//Cache the helper size
this._cacheHelperProportions(),/*
		 * - Position generation -
		 * This block generates everything position related - it's the core of draggables.
		 */
//Cache the margins of the original element
this._cacheMargins(),
//Get the next scrolling parent
this.scrollParent=this.helper.scrollParent(),
//The element's absolute position on the page minus margins
this.offset=this.currentItem.offset(),this.offset={top:this.offset.top-this.margins.top,left:this.offset.left-this.margins.left},t.extend(this.offset,{click:{//Where the click happened, relative to the element
left:e.pageX-this.offset.left,top:e.pageY-this.offset.top},parent:this._getParentOffset(),relative:this._getRelativeOffset()}),
// Only after we got the offset, we can change the helper's position to absolute
// TODO: Still need to figure out a way to make relative sorting possible
this.helper.css("position","absolute"),this.cssPosition=this.helper.css("position"),
//Generate the original position
this.originalPosition=this._generatePosition(e),this.originalPageX=e.pageX,this.originalPageY=e.pageY,
//Adjust the mouse offset relative to the helper if "cursorAt" is supplied
n.cursorAt&&this._adjustOffsetFromHelper(n.cursorAt),
//Cache the former DOM position
this.domPosition={prev:this.currentItem.prev()[0],parent:this.currentItem.parent()[0]},
//If the helper is not the original, hide the original so it's not playing any role during the drag, won't cause anything bad this way
this.helper[0]!==this.currentItem[0]&&this.currentItem.hide(),
//Create the placeholder
this._createPlaceholder(),
//Set a containment if given in the options
n.containment&&this._setContainment(),n.cursor&&"auto"!==n.cursor&&(// cursor option
r=this.document.find("body"),
// support: IE
this.storedCursor=r.css("cursor"),r.css("cursor",n.cursor),this.storedStylesheet=t("<style>*{ cursor: "+n.cursor+" !important; }</style>").appendTo(r)),n.opacity&&(// opacity option
this.helper.css("opacity")&&(this._storedOpacity=this.helper.css("opacity")),this.helper.css("opacity",n.opacity)),n.zIndex&&(// zIndex option
this.helper.css("zIndex")&&(this._storedZIndex=this.helper.css("zIndex")),this.helper.css("zIndex",n.zIndex)),
//Prepare scrolling
this.scrollParent[0]!==document&&"HTML"!==this.scrollParent[0].tagName&&(this.overflowOffset=this.scrollParent.offset()),
//Call callbacks
this._trigger("start",e,this._uiHash()),
//Recache the helper size
this._preserveHelperProportions||this._cacheHelperProportions(),!s)for(o=this.containers.length-1;o>=0;o--)this.containers[o]._trigger("activate",e,this._uiHash(this));//Execute the drag once - this causes the helper not to be visible before getting its correct position
//Prepare possible droppables
return t.ui.ddmanager&&(t.ui.ddmanager.current=this),t.ui.ddmanager&&!n.dropBehaviour&&t.ui.ddmanager.prepareOffsets(this,e),this.dragging=!0,this.helper.addClass("ui-sortable-helper"),this._mouseDrag(e),!0},_mouseDrag:function(e){var i,s,o,r,n=this.options,h=!1;
//Rearrange
for(
//Compute the helpers position
this.position=this._generatePosition(e),this.positionAbs=this._convertPositionTo("absolute"),this.lastPositionAbs||(this.lastPositionAbs=this.positionAbs),
//Do scrolling
this.options.scroll&&(this.scrollParent[0]!==document&&"HTML"!==this.scrollParent[0].tagName?(this.overflowOffset.top+this.scrollParent[0].offsetHeight-e.pageY<n.scrollSensitivity?this.scrollParent[0].scrollTop=h=this.scrollParent[0].scrollTop+n.scrollSpeed:e.pageY-this.overflowOffset.top<n.scrollSensitivity&&(this.scrollParent[0].scrollTop=h=this.scrollParent[0].scrollTop-n.scrollSpeed),this.overflowOffset.left+this.scrollParent[0].offsetWidth-e.pageX<n.scrollSensitivity?this.scrollParent[0].scrollLeft=h=this.scrollParent[0].scrollLeft+n.scrollSpeed:e.pageX-this.overflowOffset.left<n.scrollSensitivity&&(this.scrollParent[0].scrollLeft=h=this.scrollParent[0].scrollLeft-n.scrollSpeed)):(e.pageY-t(document).scrollTop()<n.scrollSensitivity?h=t(document).scrollTop(t(document).scrollTop()-n.scrollSpeed):t(window).height()-(e.pageY-t(document).scrollTop())<n.scrollSensitivity&&(h=t(document).scrollTop(t(document).scrollTop()+n.scrollSpeed)),e.pageX-t(document).scrollLeft()<n.scrollSensitivity?h=t(document).scrollLeft(t(document).scrollLeft()-n.scrollSpeed):t(window).width()-(e.pageX-t(document).scrollLeft())<n.scrollSensitivity&&(h=t(document).scrollLeft(t(document).scrollLeft()+n.scrollSpeed))),h!==!1&&t.ui.ddmanager&&!n.dropBehaviour&&t.ui.ddmanager.prepareOffsets(this,e)),
//Regenerate the absolute position used for position checks
this.positionAbs=this._convertPositionTo("absolute"),
//Set the helper position
this.options.axis&&"y"===this.options.axis||(this.helper[0].style.left=this.position.left+"px"),this.options.axis&&"x"===this.options.axis||(this.helper[0].style.top=this.position.top+"px"),i=this.items.length-1;i>=0;i--)if(
//Cache variables and intersection, continue if no intersection
s=this.items[i],o=s.item[0],r=this._intersectsWithPointer(s),r&&s.instance===this.currentContainer&&o!==this.currentItem[0]&&this.placeholder[1===r?"next":"prev"]()[0]!==o&&!t.contains(this.placeholder[0],o)&&("semi-dynamic"===this.options.type?!t.contains(this.element[0],o):!0)){if(this.direction=1===r?"down":"up","pointer"!==this.options.tolerance&&!this._intersectsWithSides(s))break;this._rearrange(e,s),this._trigger("change",e,this._uiHash());break}
//Post events to containers
//Interconnect with droppables
//Call callbacks
return this._contactContainers(e),t.ui.ddmanager&&t.ui.ddmanager.drag(this,e),this._trigger("sort",e,this._uiHash()),this.lastPositionAbs=this.positionAbs,!1},_mouseStop:function(e,i){if(e){if(
//If we are using droppables, inform the manager about the drop
t.ui.ddmanager&&!this.options.dropBehaviour&&t.ui.ddmanager.drop(this,e),this.options.revert){var s=this,o=this.placeholder.offset(),r=this.options.axis,n={};r&&"x"!==r||(n.left=o.left-this.offset.parent.left-this.margins.left+(this.offsetParent[0]===document.body?0:this.offsetParent[0].scrollLeft)),r&&"y"!==r||(n.top=o.top-this.offset.parent.top-this.margins.top+(this.offsetParent[0]===document.body?0:this.offsetParent[0].scrollTop)),this.reverting=!0,t(this.helper).animate(n,parseInt(this.options.revert,10)||500,function(){s._clear(e)})}else this._clear(e,i);return!1}},cancel:function(){if(this.dragging){this._mouseUp({target:null}),"original"===this.options.helper?this.currentItem.css(this._storedCSS).removeClass("ui-sortable-helper"):this.currentItem.show();
//Post deactivating events to containers
for(var e=this.containers.length-1;e>=0;e--)this.containers[e]._trigger("deactivate",null,this._uiHash(this)),this.containers[e].containerCache.over&&(this.containers[e]._trigger("out",null,this._uiHash(this)),this.containers[e].containerCache.over=0)}
//$(this.placeholder[0]).remove(); would have been the jQuery way - unfortunately, it unbinds ALL events from the original node!
return this.placeholder&&(this.placeholder[0].parentNode&&this.placeholder[0].parentNode.removeChild(this.placeholder[0]),"original"!==this.options.helper&&this.helper&&this.helper[0].parentNode&&this.helper.remove(),t.extend(this,{helper:null,dragging:!1,reverting:!1,_noFinalSort:null}),this.domPosition.prev?t(this.domPosition.prev).after(this.currentItem):t(this.domPosition.parent).prepend(this.currentItem)),this},serialize:function(e){var i=this._getItemsAsjQuery(e&&e.connected),s=[];return e=e||{},t(i).each(function(){var i=(t(e.item||this).attr(e.attribute||"id")||"").match(e.expression||/(.+)[\-=_](.+)/);i&&s.push((e.key||i[1]+"[]")+"="+(e.key&&e.expression?i[1]:i[2]))}),!s.length&&e.key&&s.push(e.key+"="),s.join("&")},toArray:function(e){var i=this._getItemsAsjQuery(e&&e.connected),s=[];return e=e||{},i.each(function(){s.push(t(e.item||this).attr(e.attribute||"id")||"")}),s},/* Be careful with the following core functions */
_intersectsWith:function(t){var e=this.positionAbs.left,i=e+this.helperProportions.width,s=this.positionAbs.top,o=s+this.helperProportions.height,r=t.left,n=r+t.width,h=t.top,a=h+t.height,l=this.offset.click.top,c=this.offset.click.left,p="x"===this.options.axis||s+l>h&&a>s+l,f="y"===this.options.axis||e+c>r&&n>e+c,u=p&&f;// Right Half
// Left Half
// Bottom Half
return"pointer"===this.options.tolerance||this.options.forcePointerForContainers||"pointer"!==this.options.tolerance&&this.helperProportions[this.floating?"width":"height"]>t[this.floating?"width":"height"]?u:r<e+this.helperProportions.width/2&&i-this.helperProportions.width/2<n&&h<s+this.helperProportions.height/2&&o-this.helperProportions.height/2<a},_intersectsWithPointer:function(t){var e="x"===this.options.axis||this._isOverAxis(this.positionAbs.top+this.offset.click.top,t.top,t.height),i="y"===this.options.axis||this._isOverAxis(this.positionAbs.left+this.offset.click.left,t.left,t.width),s=e&&i,o=this._getDragVerticalDirection(),r=this._getDragHorizontalDirection();return s?this.floating?r&&"right"===r||"down"===o?2:1:o&&("down"===o?2:1):!1},_intersectsWithSides:function(t){var e=this._isOverAxis(this.positionAbs.top+this.offset.click.top,t.top+t.height/2,t.height),i=this._isOverAxis(this.positionAbs.left+this.offset.click.left,t.left+t.width/2,t.width),s=this._getDragVerticalDirection(),o=this._getDragHorizontalDirection();return this.floating&&o?"right"===o&&i||"left"===o&&!i:s&&("down"===s&&e||"up"===s&&!e)},_getDragVerticalDirection:function(){var t=this.positionAbs.top-this.lastPositionAbs.top;return 0!==t&&(t>0?"down":"up")},_getDragHorizontalDirection:function(){var t=this.positionAbs.left-this.lastPositionAbs.left;return 0!==t&&(t>0?"right":"left")},refresh:function(t){return this._refreshItems(t),this._setHandleClassName(),this.refreshPositions(),this},_connectWith:function(){var t=this.options;return t.connectWith.constructor===String?[t.connectWith]:t.connectWith},_getItemsAsjQuery:function(e){function i(){h.push(this)}var s,o,r,n,h=[],a=[],l=this._connectWith();if(l&&e)for(s=l.length-1;s>=0;s--)for(r=t(l[s]),o=r.length-1;o>=0;o--)n=t.data(r[o],this.widgetFullName),n&&n!==this&&!n.options.disabled&&a.push([t.isFunction(n.options.items)?n.options.items.call(n.element):t(n.options.items,n.element).not(".ui-sortable-helper").not(".ui-sortable-placeholder"),n]);for(a.push([t.isFunction(this.options.items)?this.options.items.call(this.element,null,{options:this.options,item:this.currentItem}):t(this.options.items,this.element).not(".ui-sortable-helper").not(".ui-sortable-placeholder"),this]),s=a.length-1;s>=0;s--)a[s][0].each(i);return t(h)},_removeCurrentsFromItems:function(){var e=this.currentItem.find(":data("+this.widgetName+"-item)");this.items=t.grep(this.items,function(t){for(var i=0;i<e.length;i++)if(e[i]===t.item[0])return!1;return!0})},_refreshItems:function(e){this.items=[],this.containers=[this];var i,s,o,r,n,h,a,l,c=this.items,p=[[t.isFunction(this.options.items)?this.options.items.call(this.element[0],e,{item:this.currentItem}):t(this.options.items,this.element),this]],f=this._connectWith();if(f&&this.ready)//Shouldn't be run the first time through due to massive slow-down
for(i=f.length-1;i>=0;i--)for(o=t(f[i]),s=o.length-1;s>=0;s--)r=t.data(o[s],this.widgetFullName),r&&r!==this&&!r.options.disabled&&(p.push([t.isFunction(r.options.items)?r.options.items.call(r.element[0],e,{item:this.currentItem}):t(r.options.items,r.element),r]),this.containers.push(r));for(i=p.length-1;i>=0;i--)for(n=p[i][1],h=p[i][0],s=0,l=h.length;l>s;s++)a=t(h[s]),a.data(this.widgetName+"-item",n),// Data for target checking (mouse manager)
c.push({item:a,instance:n,width:0,height:0,left:0,top:0})},refreshPositions:function(e){
//This has to be redone because due to the item being moved out/into the offsetParent, the offsetParent's position will change
this.offsetParent&&this.helper&&(this.offset.parent=this._getParentOffset());var i,s,o,r;for(i=this.items.length-1;i>=0;i--)s=this.items[i],
//We ignore calculating positions of all connected containers when we're not over them
s.instance!==this.currentContainer&&this.currentContainer&&s.item[0]!==this.currentItem[0]||(o=this.options.toleranceElement?t(this.options.toleranceElement,s.item):s.item,e||(s.width=o.outerWidth(),s.height=o.outerHeight()),r=o.offset(),s.left=r.left,s.top=r.top);if(this.options.custom&&this.options.custom.refreshContainers)this.options.custom.refreshContainers.call(this);else for(i=this.containers.length-1;i>=0;i--)r=this.containers[i].element.offset(),this.containers[i].containerCache.left=r.left,this.containers[i].containerCache.top=r.top,this.containers[i].containerCache.width=this.containers[i].element.outerWidth(),this.containers[i].containerCache.height=this.containers[i].element.outerHeight();return this},_createPlaceholder:function(e){e=e||this;var i,s,o=e.options;o.placeholder&&o.placeholder.constructor!==String||(i=o.placeholder,s=e.currentItem[0].nodeName.toLowerCase(),o.placeholder={element:function(){var o=t("<"+s+">",e.document[0]).addClass(i||e.currentItem[0].className+" ui-sortable-placeholder").removeClass("ui-sortable-helper");return"tr"===s?e.currentItem.children().each(function(){t("<td>&#160;</td>",e.document[0]).attr("colspan",t(this).attr("colspan")||1).appendTo(o)}):"img"===s&&o.attr("src",e.currentItem.attr("src")),i||o.css("visibility","hidden"),o},update:function(t,r){
// 1. If a className is set as 'placeholder option, we don't force sizes - the class is responsible for that
// 2. The option 'forcePlaceholderSize can be enabled to force it even if a class name is specified
(!i||o.forcePlaceholderSize)&&(
//If the element doesn't have a actual height by itself (without styles coming from a stylesheet), it receives the inline height from the dragged item
(!r.height()||o.forcePlaceholderSize&&("tbody"===s||"tr"===s))&&r.height(e.currentItem.innerHeight()-parseInt(e.currentItem.css("paddingTop")||0,10)-parseInt(e.currentItem.css("paddingBottom")||0,10)),r.width()||r.width(e.currentItem.innerWidth()-parseInt(e.currentItem.css("paddingLeft")||0,10)-parseInt(e.currentItem.css("paddingRight")||0,10)))}}),
//Create the placeholder
e.placeholder=t(o.placeholder.element.call(e.element,e.currentItem)),
//Append it after the actual current item
e.currentItem.after(e.placeholder),
//Update the size of the placeholder (TODO: Logic to fuzzy, see line 316/317)
o.placeholder.update(e,e.placeholder)},_contactContainers:function(e){var i,s,o,r,n,h,a,l,c,p,f=null,u=null;
// get innermost container that intersects with item
for(i=this.containers.length-1;i>=0;i--)
// never consider a container that's located within the item itself
if(!t.contains(this.currentItem[0],this.containers[i].element[0]))if(this._intersectsWith(this.containers[i].containerCache)){
// if we've already found a container and it's more "inner" than this, then continue
if(f&&t.contains(this.containers[i].element[0],f.element[0]))continue;f=this.containers[i],u=i}else
// container doesn't intersect. trigger "out" event if necessary
this.containers[i].containerCache.over&&(this.containers[i]._trigger("out",e,this._uiHash(this)),this.containers[i].containerCache.over=0);
// if no intersecting containers found, return
if(f)
// move the item into the container if it's not there already
if(1===this.containers.length)this.containers[u].containerCache.over||(this.containers[u]._trigger("over",e,this._uiHash(this)),this.containers[u].containerCache.over=1);else{for(
//When entering a new container, we will find the item with the least distance and append our item near it
o=1e4,r=null,c=f.floating||this._isFloating(this.currentItem),n=c?"left":"top",h=c?"width":"height",p=c?"clientX":"clientY",s=this.items.length-1;s>=0;s--)t.contains(this.containers[u].element[0],this.items[s].item[0])&&this.items[s].item[0]!==this.currentItem[0]&&(a=this.items[s].item.offset()[n],l=!1,e[p]-a>this.items[s][h]/2&&(l=!0),Math.abs(e[p]-a)<o&&(o=Math.abs(e[p]-a),r=this.items[s],this.direction=l?"up":"down"));
//Check if dropOnEmpty is enabled
if(!r&&!this.options.dropOnEmpty)return;if(this.currentContainer===this.containers[u])return void(this.currentContainer.containerCache.over||(this.containers[u]._trigger("over",e,this._uiHash()),this.currentContainer.containerCache.over=1));r?this._rearrange(e,r,null,!0):this._rearrange(e,null,this.containers[u].element,!0),this._trigger("change",e,this._uiHash()),this.containers[u]._trigger("change",e,this._uiHash(this)),this.currentContainer=this.containers[u],
//Update the placeholder
this.options.placeholder.update(this.currentContainer,this.placeholder),this.containers[u]._trigger("over",e,this._uiHash(this)),this.containers[u].containerCache.over=1}},_createHelper:function(e){var i=this.options,s=t.isFunction(i.helper)?t(i.helper.apply(this.element[0],[e,this.currentItem])):"clone"===i.helper?this.currentItem.clone():this.currentItem;
//Add the helper to the DOM if that didn't happen already
return s.parents("body").length||t("parent"!==i.appendTo?i.appendTo:this.currentItem[0].parentNode)[0].appendChild(s[0]),s[0]===this.currentItem[0]&&(this._storedCSS={width:this.currentItem[0].style.width,height:this.currentItem[0].style.height,position:this.currentItem.css("position"),top:this.currentItem.css("top"),left:this.currentItem.css("left")}),(!s[0].style.width||i.forceHelperSize)&&s.width(this.currentItem.width()),(!s[0].style.height||i.forceHelperSize)&&s.height(this.currentItem.height()),s},_adjustOffsetFromHelper:function(e){"string"==typeof e&&(e=e.split(" ")),t.isArray(e)&&(e={left:+e[0],top:+e[1]||0}),"left"in e&&(this.offset.click.left=e.left+this.margins.left),"right"in e&&(this.offset.click.left=this.helperProportions.width-e.right+this.margins.left),"top"in e&&(this.offset.click.top=e.top+this.margins.top),"bottom"in e&&(this.offset.click.top=this.helperProportions.height-e.bottom+this.margins.top)},_getParentOffset:function(){
//Get the offsetParent and cache its position
this.offsetParent=this.helper.offsetParent();var e=this.offsetParent.offset();
// This is a special case where we need to modify a offset calculated on start, since the following happened:
// 1. The position of the helper is absolute, so it's position is calculated based on the next positioned parent
// 2. The actual offset parent is a child of the scroll parent, and the scroll parent isn't the document, which means that
//    the scroll is included in the initial calculation of the offset of the parent, and never recalculated upon drag
// This needs to be actually done for all browsers, since pageX/pageY includes this information
// with an ugly IE fix
return"absolute"===this.cssPosition&&this.scrollParent[0]!==document&&t.contains(this.scrollParent[0],this.offsetParent[0])&&(e.left+=this.scrollParent.scrollLeft(),e.top+=this.scrollParent.scrollTop()),(this.offsetParent[0]===document.body||this.offsetParent[0].tagName&&"html"===this.offsetParent[0].tagName.toLowerCase()&&t.ui.ie)&&(e={top:0,left:0}),{top:e.top+(parseInt(this.offsetParent.css("borderTopWidth"),10)||0),left:e.left+(parseInt(this.offsetParent.css("borderLeftWidth"),10)||0)}},_getRelativeOffset:function(){if("relative"===this.cssPosition){var t=this.currentItem.position();return{top:t.top-(parseInt(this.helper.css("top"),10)||0)+this.scrollParent.scrollTop(),left:t.left-(parseInt(this.helper.css("left"),10)||0)+this.scrollParent.scrollLeft()}}return{top:0,left:0}},_cacheMargins:function(){this.margins={left:parseInt(this.currentItem.css("marginLeft"),10)||0,top:parseInt(this.currentItem.css("marginTop"),10)||0}},_cacheHelperProportions:function(){this.helperProportions={width:this.helper.outerWidth(),height:this.helper.outerHeight()}},_setContainment:function(){var e,i,s,o=this.options;"parent"===o.containment&&(o.containment=this.helper[0].parentNode),("document"===o.containment||"window"===o.containment)&&(this.containment=[0-this.offset.relative.left-this.offset.parent.left,0-this.offset.relative.top-this.offset.parent.top,t("document"===o.containment?document:window).width()-this.helperProportions.width-this.margins.left,(t("document"===o.containment?document:window).height()||document.body.parentNode.scrollHeight)-this.helperProportions.height-this.margins.top]),/^(document|window|parent)$/.test(o.containment)||(e=t(o.containment)[0],i=t(o.containment).offset(),s="hidden"!==t(e).css("overflow"),this.containment=[i.left+(parseInt(t(e).css("borderLeftWidth"),10)||0)+(parseInt(t(e).css("paddingLeft"),10)||0)-this.margins.left,i.top+(parseInt(t(e).css("borderTopWidth"),10)||0)+(parseInt(t(e).css("paddingTop"),10)||0)-this.margins.top,i.left+(s?Math.max(e.scrollWidth,e.offsetWidth):e.offsetWidth)-(parseInt(t(e).css("borderLeftWidth"),10)||0)-(parseInt(t(e).css("paddingRight"),10)||0)-this.helperProportions.width-this.margins.left,i.top+(s?Math.max(e.scrollHeight,e.offsetHeight):e.offsetHeight)-(parseInt(t(e).css("borderTopWidth"),10)||0)-(parseInt(t(e).css("paddingBottom"),10)||0)-this.helperProportions.height-this.margins.top])},_convertPositionTo:function(e,i){i||(i=this.position);var s="absolute"===e?1:-1,o="absolute"!==this.cssPosition||this.scrollParent[0]!==document&&t.contains(this.scrollParent[0],this.offsetParent[0])?this.scrollParent:this.offsetParent,r=/(html|body)/i.test(o[0].tagName);return{top:i.top+// The absolute mouse position
this.offset.relative.top*s+// Only for relative positioned nodes: Relative offset from element to offset parent
this.offset.parent.top*s-// The offsetParent's offset without borders (offset + border)
("fixed"===this.cssPosition?-this.scrollParent.scrollTop():r?0:o.scrollTop())*s,left:i.left+// The absolute mouse position
this.offset.relative.left*s+// Only for relative positioned nodes: Relative offset from element to offset parent
this.offset.parent.left*s-// The offsetParent's offset without borders (offset + border)
("fixed"===this.cssPosition?-this.scrollParent.scrollLeft():r?0:o.scrollLeft())*s}},_generatePosition:function(e){var i,s,o=this.options,r=e.pageX,n=e.pageY,h="absolute"!==this.cssPosition||this.scrollParent[0]!==document&&t.contains(this.scrollParent[0],this.offsetParent[0])?this.scrollParent:this.offsetParent,a=/(html|body)/i.test(h[0].tagName);
// This is another very weird special case that only happens for relative elements:
// 1. If the css position is relative
// 2. and the scroll parent is the document or similar to the offset parent
// we have to refresh the relative offset during the scroll so there are no jumps
/*
		 * - Position constraining -
		 * Constrain the position to a mix of grid, containment.
		 */
//If we are not dragging yet, we won't check for options
return"relative"!==this.cssPosition||this.scrollParent[0]!==document&&this.scrollParent[0]!==this.offsetParent[0]||(this.offset.relative=this._getRelativeOffset()),this.originalPosition&&(this.containment&&(e.pageX-this.offset.click.left<this.containment[0]&&(r=this.containment[0]+this.offset.click.left),e.pageY-this.offset.click.top<this.containment[1]&&(n=this.containment[1]+this.offset.click.top),e.pageX-this.offset.click.left>this.containment[2]&&(r=this.containment[2]+this.offset.click.left),e.pageY-this.offset.click.top>this.containment[3]&&(n=this.containment[3]+this.offset.click.top)),o.grid&&(i=this.originalPageY+Math.round((n-this.originalPageY)/o.grid[1])*o.grid[1],n=this.containment?i-this.offset.click.top>=this.containment[1]&&i-this.offset.click.top<=this.containment[3]?i:i-this.offset.click.top>=this.containment[1]?i-o.grid[1]:i+o.grid[1]:i,s=this.originalPageX+Math.round((r-this.originalPageX)/o.grid[0])*o.grid[0],r=this.containment?s-this.offset.click.left>=this.containment[0]&&s-this.offset.click.left<=this.containment[2]?s:s-this.offset.click.left>=this.containment[0]?s-o.grid[0]:s+o.grid[0]:s)),{top:n-// The absolute mouse position
this.offset.click.top-// Click offset (relative to the element)
this.offset.relative.top-// Only for relative positioned nodes: Relative offset from element to offset parent
this.offset.parent.top+(// The offsetParent's offset without borders (offset + border)
"fixed"===this.cssPosition?-this.scrollParent.scrollTop():a?0:h.scrollTop()),left:r-// The absolute mouse position
this.offset.click.left-// Click offset (relative to the element)
this.offset.relative.left-// Only for relative positioned nodes: Relative offset from element to offset parent
this.offset.parent.left+(// The offsetParent's offset without borders (offset + border)
"fixed"===this.cssPosition?-this.scrollParent.scrollLeft():a?0:h.scrollLeft())}},_rearrange:function(t,e,i,s){i?i[0].appendChild(this.placeholder[0]):e.item[0].parentNode.insertBefore(this.placeholder[0],"down"===this.direction?e.item[0]:e.item[0].nextSibling),
//Various things done here to improve the performance:
// 1. we create a setTimeout, that calls refreshPositions
// 2. on the instance, we have a counter variable, that get's higher after every append
// 3. on the local scope, we copy the counter variable, and check in the timeout, if it's still the same
// 4. this lets only the last addition to the timeout stack through
this.counter=this.counter?++this.counter:1;var o=this.counter;this._delay(function(){o===this.counter&&this.refreshPositions(!s)})},_clear:function(t,e){
//Post events to containers
function i(t,e,i){return function(s){i._trigger(t,s,e._uiHash(e))}}this.reverting=!1;
// We delay all events that have to be triggered to after the point where the placeholder has been removed and
// everything else normalized again
var s,o=[];if(
// We first have to update the dom position of the actual currentItem
// Note: don't do it if the current item is already removed (by a user), or it gets reappended (see #4088)
!this._noFinalSort&&this.currentItem.parent().length&&this.placeholder.before(this.currentItem),this._noFinalSort=null,this.helper[0]===this.currentItem[0]){for(s in this._storedCSS)("auto"===this._storedCSS[s]||"static"===this._storedCSS[s])&&(this._storedCSS[s]="");this.currentItem.css(this._storedCSS).removeClass("ui-sortable-helper")}else this.currentItem.show();for(this.fromOutside&&!e&&o.push(function(t){this._trigger("receive",t,this._uiHash(this.fromOutside))}),!this.fromOutside&&this.domPosition.prev===this.currentItem.prev().not(".ui-sortable-helper")[0]&&this.domPosition.parent===this.currentItem.parent()[0]||e||o.push(function(t){this._trigger("update",t,this._uiHash())}),
// Check if the items Container has Changed and trigger appropriate
// events.
this!==this.currentContainer&&(e||(o.push(function(t){this._trigger("remove",t,this._uiHash())}),o.push(function(t){return function(e){t._trigger("receive",e,this._uiHash(this))}}.call(this,this.currentContainer)),o.push(function(t){return function(e){t._trigger("update",e,this._uiHash(this))}}.call(this,this.currentContainer)))),s=this.containers.length-1;s>=0;s--)e||o.push(i("deactivate",this,this.containers[s])),this.containers[s].containerCache.over&&(o.push(i("out",this,this.containers[s])),this.containers[s].containerCache.over=0);if(
//Do what was originally in plugins
this.storedCursor&&(this.document.find("body").css("cursor",this.storedCursor),this.storedStylesheet.remove()),this._storedOpacity&&this.helper.css("opacity",this._storedOpacity),this._storedZIndex&&this.helper.css("zIndex","auto"===this._storedZIndex?"":this._storedZIndex),this.dragging=!1,e||this._trigger("beforeStop",t,this._uiHash()),
//$(this.placeholder[0]).remove(); would have been the jQuery way - unfortunately, it unbinds ALL events from the original node!
this.placeholder[0].parentNode.removeChild(this.placeholder[0]),this.cancelHelperRemoval||(this.helper[0]!==this.currentItem[0]&&this.helper.remove(),this.helper=null),!e){for(s=0;s<o.length;s++)o[s].call(this,t);//Trigger all delayed events
this._trigger("stop",t,this._uiHash())}return this.fromOutside=!1,!this.cancelHelperRemoval},_trigger:function(){t.Widget.prototype._trigger.apply(this,arguments)===!1&&this.cancel()},_uiHash:function(e){var i=e||this;return{helper:i.helper,placeholder:i.placeholder||t([]),position:i.position,originalPosition:i.originalPosition,offset:i.positionAbs,item:i.currentItem,sender:e?e.element:null}}})});