(function (jQ) {
  "use strict";
  brite.registerView("PathAddView", {}, {

    create: function (data, config) {
      var view = this;
      jQ.extend(view.options, data);
      return jQ("#tmpl-PathAddView").render({ options: view.options });
    },

    postDisplay: function (data, config) {
      var view = this;
      view.$el.find("#pathaddmodal").modal({ show: true });
      view.$el.find("#pathaddmodal").on("hidden.bs.modal", function (ev) {
        view.$el.bRemove();
        if (typeof data.onSubmitted === "function") {
          view.options.onSubmitted();
        }
      });
    },

    events: {

      "submit; form": function (ev) {
        var view = this,
          element = jQ(ev.currentTarget),
          path = view.$el.find("input[name='path']").val();
        ev.preventDefault();

        // Validate form.
        if (!path) {
          return;
        }

        // Create.
        svnadmin.service.createRepositoryPath(view.options.providerId, view.options.repositoryId, path).done(function (resp) {
          view.$el.find("#pathaddmodal").modal("hide");
        }).fail(function () {
          alert("Can not create path.");
        });
      },

      "click; button.submit": function (ev) {
        var view = this;
        view.$el.find("form").submit();
      }

    },

    ///////////////////////////////////////////////////////////////////

    options: {
      providerId: "",
      repositoryId: "",
      onSubmitted: function () {}
    }

  });
}(jQuery));