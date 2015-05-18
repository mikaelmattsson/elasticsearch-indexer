"use strict";
jQuery(document).ready(function ($) {

  var isIndexing = false;

  var requestReindex = function (from, size) {
    $.post(ajaxurl, {
      action: 'es_reindex',
      from: from,
      size: size
    })
      .always(function (data) {
        $('.es-reindex-output').append($('<div>').html(data));
        if (data.indexOf('Finished') !== 0) {
          from += size;
          requestReindex(from, size);
        } else {
          isIndexing = false;
        }
      });
  };

  $('.es-reindex').click(function (e) {
    if (isIndexing) {
      return;
    }
    isIndexing = true;
    e.preventDefault();
    $('.es-reindex-output').html("Starting… Don't close this page until the process is finished\n");
    requestReindex(0, 500);
  });
});
