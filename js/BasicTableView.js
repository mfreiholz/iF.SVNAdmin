(function (jQ) {
  "use strict";

  // Defines default values for the table-view.
  var _options = {
    showFilter: true,
    showPaging: true,

    loadMore: function (offset, num) {
      var def = new jQ.Deferred();
      def.resolve({
        hasMore: false,
        rows: []
      });
      return def.promise();
    }
  };

  /**
   * Register View
   */
  brite.registerView("BasicTableView", {}, {

    create: function (config, data) {
      return jQ("#tmpl-BasicTableView").render();
    },

    postDisplay: function (config, data) {
    },

    events: {
    }

  });

}(jQuery));
