"use strict";
jQuery(document).ready(function ($) {

  var indexer = {

    isIndexing: false,

    start: function (sites, interval, targetWrapper) {
      if (this.isIndexing == true) {
        return;
      }
      this.isIndexing = true;
      this.currentSite = 0;
      this.indexedPosts = 0;
      this.totalPosts = 0;
      this.sites = sites;
      this.interval = interval;
      this.$wrapper = $(targetWrapper);
      this.$progress = $('<div class="esi-indexer-progress">').appendTo(this.$wrapper);
      this.$message = $('<div class="esi-indexer-message">').appendTo(this.$wrapper);
      this.$errors = $('<div class="esi-indexer-errors">').appendTo(this.$wrapper);
      this.$message.html("Starting… Don't close this page until the process is finished\n");
      this.updateProgress();
      this.run();
    },

    run: function () {
      this.requestReindex(this.sites[this.currentSite], this.indexedPosts, this.interval);
    },

    requestReindex: function (site, from, size) {
      $.post(ajaxurl, {
        action: 'es_reindex',
        site: site,
        from: from,
        size: size
      })
        .done(this.httpSuccess.bind(this))
        .fail(this.httpError.bind(this));
    },

    httpSuccess: function (data) {
      if (typeof data !== 'object') {
        this.$errors.append('<pre>' + data + '</pre>');
        return;
      }

      if (data.success == false) {
        this.$errors.append('<pre>' + data.message + '</pre>');
        return;
      }
      console.log(data);
      this.indexedPosts = data.indexed;
      this.totalPosts = data.total;
      this.$message.html('Indexed ' + this.indexedPosts + '/' + this.totalPosts + ' posts.')
      this.updateProgress();

      if (this.indexedPosts >= this.totalPosts) {
        // finised with current site.
        if(this.currentSite + 1 < this.sites.length) {
          this.currentSite++;
          this.indexedPosts = 0;
          this.totalPosts = 0;
          this.$message.append(' Finished. Switching blog.');
        } else {
          // no more sites to index
          this.$message.append(' Done.');
          this.isIndexing = false;
          return;
        }
      }

      this.run();
    },

    httpError: function (data) {
      this.$errors.append('<pre>' + data + '</pre>');
    },

    updateProgress: function () {
      var percent = 0;
      if (this.totalPosts) {
        percent = this.indexedPosts / this.totalPosts * 100;
      }
      var html = '<div class="esi-progress-bar" style="width: ' + percent + '%;">' + Math.round(percent) + '%</div>';
      this.$progress.html(html);
    }

  };

  $('.es-reindex').click(function (e) {
    e.preventDefault();
    var sites = $(e.target).attr('data-sites').split(',');
    indexer.start(sites, 500, '.es-reindex-output');
  });
});
