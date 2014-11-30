(function () {
  "use strict";
  brite.registerView("BasicSearchDialogView", {}, {

    create: function (data, config) {
      var view = this;
      jQuery.extend(view.options, data);
      return jQuery("#tmpl-BasicSearchDialogView").render({ options: view.options });
    },

    postDisplay: function (data, config) {
      var view = this;
      view.$el.find("#basicsearchmodal").modal({ show: true });
      view.$el.find("#basicsearchmodal").on("hidden.bs.modal", function (ev) {
        view.$el.bRemove();
        if (typeof data.onSubmitted === "function") {
          data.onSubmitted();
        }
      });
    },

    events: {
    },

    ///////////////////////////////////////////////////////////////////

    options: {
      onSubmitted: function () {},
      searchMore: function (query, offset, limit) {
        var def = new jQuery.Deferred();
        def.resolve({
          hasMore: false,
          rows: [
            { id: 0, cells: ["Value#1", "Value#2"] },
            { id: 1, cells: ["Value#1", "Value#2"] }
          ]
        });
      }
    }

  });
}());