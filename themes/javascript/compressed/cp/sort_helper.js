/**
 * Fixes an issue in jQuery UI's Sortable implementation of it's
 * tolerance: 'intercect' option not working correctly; this fix
 * ensures once an item overlaps by 50%, the sort happens, and does
 * not depend on the position of the cursor
 */

EE.sortable_sort_helper=function(m,a){var e=!1==$(this).sortable("option","axis")?"y":$(this).sortable("option","axis"),f=$(this),k=f.children(".ui-sortable-placeholder:first"),l="y"==e?a.helper.outerHeight():a.helper.outerWidth(),g="y"==e?a.position.top:a.position.left,h=g+l;f.children(":visible").each(function(){var b=$(this);if(!b.hasClass("ui-sortable-helper")&&!b.hasClass("ui-sortable-placeholder")){var d="y"==e?b.outerHeight():b.outerWidth(),c="y"==e?b.position().top:b.position().left,a=c+d,
d=Math.min(l,d)/2;if(g>c&&g<a){if(c=g-c,c<d)return k.insertBefore(b),f.sortable("refreshPositions"),!1}else if(h<a&&h>c&&(c=a-h,c<d))return k.insertAfter(b),f.sortable("refreshPositions"),!1}})};
