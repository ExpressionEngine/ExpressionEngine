"use strict";

$(document).ready(function () {
  // Make clicking on add-on cards open their settings
  $('.add-on-card').each(function (i, elem) {
    var link = elem.dataset.cardLink;

    if (link && !elem.classList.contains('add-on-card--uninstalled')) {
      elem.addEventListener('click', function (e) {
        // Don't open the add-ons settings if the user clicks on a button inside of the card
        if ($(e.target).closest('.add-on-card__cog, .add-on-card__button').length == 0) {
          window.location.href = link;
        }
      });
    }
  });
});