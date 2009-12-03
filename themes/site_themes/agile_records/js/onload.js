$(document).ready(function(){


  function switch_link_off(item, classname){
      $(item).removeClass(classname); 
  }
  
  
  $('div#homepage_events div#recent').hide();
  
  $('div#homepage_events ul li a').click(function(){
       switch_link_off('div#homepage_events ul li.on', 'on');
       $(this).parent().addClass('on');
       
       var show_div = $(this).attr('href');
       $('div#homepage_events div').hide();
       $('div'+show_div).show();
       
       return false;
  });
  
  
  
  // BAND EVENTS
  
  $('div#band_events div#recent').hide();
  
  $('div#band_events ul li a').click(function(){
       switch_link_off('div#band_events ul li.on', 'on');
       $(this).parent().addClass('on');
       
       var show_div = $(this).attr('href');
       $('div#band_events div').hide();
       $('div'+show_div).show();
       
       return false;
  });
  
  
 
  // FORUM SHOW/HIDE
  $(".forums a.expand").click(function () {
      $(this).next().slideToggle("normal");
	   
	   $(this).toggleClass('closed');
	   
      return false;
  });
  
  
  
  // STAFF INFO TOGGLE
  $('div#content_sec.staff_profiles ul li h4').next().hide();
  
  $("div#content_sec.staff_profiles ul li h4").click(function () {
      $(this).next().slideToggle("fast");
	   	   
      return false;
  });
  
  
  
  // EVENTS SHOW/HIDE
  $('.events ul#events_upcoming li a.more').next().hide();
  
  $(".events ul#events_upcoming li a.more").click(function () {
      $(this).next().slideToggle("fast");
	   
	   $(this).toggleClass('open');
	   
      return false;
  });
  
  
  // EVENTS GALLERY
  $('ul.thumbs li a').click(function(){
     var src = this.getAttribute('href');
     $('div#gallery_main img').attr('src', src);
     return false;
  });
  
  
  
  // MEMBER CONTROL PANEL SHOW/HIDE
  $('div#navigation_sec ul.pm').hide();
  
  $("div#navigation_sec a.expand").click(function () {
      $("div#navigation_sec ul.pm").slideToggle("normal");
	   
	   $(this).toggleClass('open');
	   
      return false;
  });


});