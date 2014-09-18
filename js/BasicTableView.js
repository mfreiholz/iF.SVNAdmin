(function (jQ) {
  "use strict";

  // Defines default values for the table-view.
  var _options = {
    showSearch: true,
    showPaging: true,
    title: "Basic Table View",
    columns: [],
    actions: [],

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
      return jQ("#tmpl-BasicTableView").render(_options);
    },

    postDisplay: function (config, data) {
    },

    events: {
    }

  });

}(jQuery));
