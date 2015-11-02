$(document).ready(function(){function e(e){return e+="",2==e.length?e:"0"+e}function t(t,a){var r=t.getFullYear(),i=t.getMonth()+1,l=t.getDate(),d=t.getDay(),s=t.getHours(),c=t.getMinutes();s=(s+11)%12+1,
// Suffix
suffix=1==l?"st":2==l?"nd":3==l?"rd":"th",
// Calculate day of year
diff=t-new Date(t.getFullYear(),0,0),doy=Math.ceil(diff/864e5)-1,
// Calculate days in this month
days_in_month=2==i?1==new Date(r,1,29).getMonth()?29:28:[4,6,9,11].indexOf(i)>-1?30:31;var o={
// Day
d:e(l),D:EE.lang.date.days[d],j:l,l:EE.lang.date.days[d],N:0==d?7:d,S:suffix,w:d,z:doy,
// Week
W:Math.ceil(((t-new Date(t.getFullYear(),0,1))/864e5+new Date(t.getFullYear(),0,1).getDay()+1)/7),
// Month
F:EE.lang.date.months.full[i-1],m:e(i),M:EE.lang.date.months.abbreviated[i-1],n:i,t:days_in_month,
// Year
L:1==new Date(r,1,29).getMonth()?1:0,
// o: year,
Y:r,y:t.getFullYear().toString().substr(2,2),
// Time
a:t.getHours()<12?"am":"pm",A:t.getHours()<12?"AM":"PM",
// B: '???',
g:s,G:t.getHours(),h:e(s),H:e(t.getHours()),i:e(c),s:e(t.getSeconds()),u:t.getMilliseconds(),
// Timezone
// e: foo,
// I: foo,
// O: foo,
// P: foo,
// T: foo,
Z:60*t.getTimezoneOffset()*-1,
// Full Date/Time
// c: foo,
// r: foo,
U:Math.floor(t.getTime()/1e3)};return a.replace(n,function(e){return e=e.replace("%",""),e in o?o[e]:e.slice(1,e.length-1)})}function a(e){$('input[rel="date-picker"]').on("focus",function(){
// find the position of the input clicked
var e=$(this).offset();r.init(this),
// position and toggle the .date-picker-wrap relative to the input clicked
$(".date-picker-wrap").css({top:e.top+30,left:e.left}).show()})}
// hat tip: http://stevenlevithan.com/assets/misc/date.format.js
var n=/%d|%D|%j|%l|%N|%S|%w|%z|%W|%F|%m|%M|%n|%t|%L|%o|%Y|%y|%a|%A|%B|%g|%G|%h|%H|%i|%s|%u|%e|%I|%O|%P|%T|%Z|%c|%r|%U|"[^"]*"|'[^']*'/g,r={calendars:[],element:null,
// showing
year:2010,month:0,init:function(e){var a,n=null,l=null,d=null;if(this.element=e,this.calendars=[],0==$(".date-picker-wrap").length){var s=$("body");$("#cform").length&&(s=$("#cform")),s.append('<div class="date-picker-wrap"><div class="date-picker-clip"><div class="date-picker-clip-inner"></div></div></div>'),
// listen for clicks on elements classed with .date-picker-next
$(".date-picker-clip-inner").on("click",".date-picker-next",function(e){i.next(),
// animate the scrolling of .date-picker-clip forwards
// to the next .date-picker-item
$(".date-picker-clip").animate({scrollLeft:"+=260"},200),
// stop page from reloading
// the source window and appending # to the URI
e.preventDefault()}),
// listen for clicks on elements classed with .date-picker-back
$(".date-picker-clip-inner").on("click",".date-picker-prev",function(e){i.prev(),
// animate the scrolling of .date-picker-clip backwards
// to the previous .date-picker-item
$(".date-picker-clip").animate({scrollLeft:"-=260"},200),
// stop page from reloading
// the source window and appending # to the URI
e.preventDefault()}),
// listen for clicks on elements classed with .date-picker-back
$(".date-picker-clip-inner").on("click",".date-picker-item td a",function(e){if($(".date-picker-item td.act").removeClass("act"),$(this).closest("td").addClass("act"),$(r.element).val()){var a=new Date(1e3*$(r.element).attr("data-timestamp"));a.setYear(r.year),a.setMonth(r.month),a.setDate($(this).text())}else var a=new Date(r.year,r.month,$(this).text());var n=new Date;a.setHours(n.getHours()),a.setMinutes(n.getMinutes()),a.setSeconds(n.getSeconds()),$(r.element).val(t(a,EE.date.date_format)),$(r.element).attr("data-timestamp",t(a,"%U")),$(r.element).focus(),$(".date-picker-wrap").toggle(),e.preventDefault()}),
// Prevent manual scrolling of the huge inner clip div
$(".date-picker-clip-inner").on("mousewheel",function(e){e.preventDefault()})}if($(this.element).val()){var c=$(this.element).attr("data-timestamp");a=new Date(c?1e3*c:Date.parse($(this.element).val())),n=a.getUTCDate(),l=a.getUTCFullYear(),d=a.getUTCMonth()}else a=new Date,l=a.getFullYear(),d=a.getMonth();var o=this.generate(l,d);null!=o&&($(".date-picker-clip-inner").html(o),n&&$(".date-picker-item td:contains("+n+")").each(function(){$(this).text()==n&&$(this).addClass("act")}))},generate:function(e,t){if(
// Set variables
this.month=t,this.year=e,r.calendars.indexOf(e+"-"+t)>-1)return null;var a=i.total_days(e,t),n=(i.total_days(e,t-1),i.first_day(e,t)),l=7-(n+a)%7,d=t-1>-1?t-1:11,s=12>t+1?t+1:0;l=7==l?0:l;
// Leading dimmed
for(var c=['<div class="date-picker-item">','<div class="date-picker-heading">','<a class="date-picker-prev" href="">'+EE.lang.date.months.abbreviated[d]+"</a>","<h3>"+EE.lang.date.months.full[t]+" "+e+"</h3>",'<a class="date-picker-next" href="">'+EE.lang.date.months.abbreviated[s]+"</a>","</div>","<table>","<tr>","<th>"+EE.lang.date.days[0]+"</th>","<th>"+EE.lang.date.days[1]+"</th>","<th>"+EE.lang.date.days[2]+"</th>","<th>"+EE.lang.date.days[3]+"</th>","<th>"+EE.lang.date.days[4]+"</th>","<th>"+EE.lang.date.days[5]+"</th>","<th>"+EE.lang.date.days[6]+"</th>","</tr>"],o=["</table>","</div>"],p=["<tr>"],u=1,h=0,f=0;n>f;f++)p[u++]='<td class="empty"></td>',h++;
// Main calendar
for(var g=0;a>g;g++)h&&h%7===0&&(p[u++]="</tr>",p[u++]="<tr>"),p[u++]='<td><a href="#">',p[u++]=g+1,p[u++]="</a></td>",h++;
// Trailing dimmed
for(var m=0;l>m;m++)p[u++]='<td class="empty"></td>',h++;return p[u++]="</tr>",this.calendars.push(e+"-"+t),c.join("")+p.join("")+o.join("")}},i={select:function(e){var t=new Date(r.year,e);return r.generate(t.getFullYear(),t.getMonth())},prev:function(){var e=this.select(r.month-1);if(null!=e){$(".date-picker-clip-inner").prepend(e);var t=$(".date-picker-clip").scrollLeft();$(".date-picker-clip").scrollLeft(t+260)}},next:function(){var e=this.select(r.month+1);null!=e&&$(".date-picker-clip-inner").append(e)},total_days:function(e,t){return 32-new Date(e,t,32).getDate()},first_day:function(e,t){return new Date(e,t,1).getDay()}};a($('input[rel="date-picker"]').not(".grid-input-form input")),
// Date fields inside a Grid need to be bound when a new row is added
void 0!==Grid&&Grid.bind("date","display",function(e){a($('input[rel="date-picker"]',e))}),$(document).on("focus","input,select,button",function(e){"date-picker"==$(e.target).attr("rel")||$(e.target).closest(".date-picker-wrap").length||$(".date-picker-wrap").hide()}),$(document).on("click",function(e){"date-picker"==$(e.target).attr("rel")||$(e.target).closest(".date-picker-wrap").length||$(".date-picker-wrap").hide()})});