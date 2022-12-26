
$(document).ready(() => {

  // Make clicking on add-on cards open their settings
  $('.add-on-card').each(function (i, elem) {
    var link = elem.dataset.cardLink;

    if (link && !elem.classList.contains('add-on-card--uninstalled')) {
      elem.addEventListener('click', function (e) {
        // Don't open the add-ons settings if the user clicks on a button inside of the card
        if ($(e.target).closest('.add-on-card__cog, .add-on-card__button, .dropdown__link, .toolbar-wrap, .add').length == 0) {
		  window.location.href = link;
        }
      });
    }
  });

  $('.add-on-card .dropdown__link.m-link').on('click', function (e) {
    var modalIs = '.' + $(this).attr('rel');
    var ajax_url = $(this).data('confirm-ajax');
    $(modalIs + " .ajax").html('');

    if (typeof ajax_url != 'undefined') {
      $.post(ajax_url, $(modalIs + " form").serialize(), function(data) {
        $(modalIs + " .ajax").html(data);
        Dropdown.renderFields();
      });
    }

    $(modalIs + " .checklist").html(''); // Reset it
    $(modalIs + " .checklist").append('<li>' + $(this).data('confirm') + '</li>');

    $(modalIs + " form").attr('action', $(this).data('action-url')); // Reset it

    e.preventDefault();
  });

  $('.modal-confirm-remove form').on('submit', function(e) {
		if( $(this).find('.ajax').length && $(this).find('button').hasClass('off') ) {
			$(this).find('.ajax .fieldset-invalid').show();
			e.preventDefault();
		}
	});
});
