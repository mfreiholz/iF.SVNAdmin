(function (jQ) {
  "use strict";
  brite.registerView("UserAddView", {}, {

    create: function (data, config) {
      var view = this;
      jQ.extend(view.options, data);
      return jQ("#tmpl-UserAddView").render({ providerId: data.providerId });
    },

    postDisplay: function (data, config) {
      var view = this;
      view.$el.find("#useraddmodal").modal({ show: true });
      view.$el.find("#useraddmodal").on("hidden.bs.modal", function (ev) { view.$el.bRemove(); });
    },

    events: {
      "submit; form": function (ev) {
        var view = this,
          element = jQ(ev.currentTarget),
          providerId = view.$element.data("providerid"),
          name = view.$el.find("input[name='name']").val(),
          password = view.$el.find("input[name='password']").val(),
          password2 = view.$el.find("input[name='password2']").val();
        ev.preventDefault();
        // Validate form.
        if (!providerId || !name || !password || !password2) {
          return;
        } else if (password !== password2) {
          alert(tr("Passwords doesn't match."));
          return;
        }
        // Create.
        svnadmin.service.createUser(providerId, name, password)
          .done(function (resp) {
            view.$el.find("#useraddmodal").modal("hide");
            view.options.submitted();
          }).fail(function () {
            alert(tr("Internal error."));
          });
      },
      "click; button.submit": function (ev) {
        var view = this;
        view.$el.find("form").submit();
      }
    },

    ///////////////////////////////////////////////////////////////////

    options: {
      submitted: function () {}
    }

  });


}(jQuery));