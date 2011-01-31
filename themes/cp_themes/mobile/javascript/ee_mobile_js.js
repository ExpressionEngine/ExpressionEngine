var jQT = new $.jQTouch({
    addGlossToIcon: true,
	fullscreen: false
});

$(document).ready(function() {
    var anchor = $("a");
    
    anchor.click(function(){
        link = $(this).attr('href');
        
        if ((link !== this.href.substring(0, 1)) && this.target != "_blank" &&  !$(this).hasClass("animate")) {
            window.location.href=link;
        }
        else {
          return false;
        }
    });     
});