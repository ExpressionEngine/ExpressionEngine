/**
 * Fixes an issue in jQuery UI's Sortable implementation of it's
 * tolerance: 'intercect' option not working correctly; this fix
 * ensures once an item overlaps by 50%, the sort happens, and does
 * not depend on the position of the cursor
 */
EE.sortable_sort_helper=function(e,t){
// Get the axis to determine if we're working with heights or widths
var i=0==$(this).sortable("option","axis")?"y":$(this).sortable("option","axis"),r=$(this),o=r.children(".ui-sortable-placeholder:first"),s="y"==i?t.helper.outerHeight():t.helper.outerWidth(),h="y"==i?t.position.top:t.position.left,l=h+s;
// Ensure placeholder is the same height as helper for
// calculations to work
o.height(t.helper.outerHeight()),r.children(":visible").each(function(){var e=$(this);if(!e.hasClass("ui-sortable-helper")&&!e.hasClass("ui-sortable-placeholder")){var t="y"==i?e.outerHeight():e.outerWidth(),a="y"==i?e.position().top:e.position().left,n=a+t,p=Math.min(s,t)/2;if(h>a&&n>h){var f=h-a;if(p>f)return o.insertBefore(e),r.sortable("refreshPositions"),!1}else if(n>l&&l>a){var f=n-l;if(p>f)return o.insertAfter(e),r.sortable("refreshPositions"),!1}}})};