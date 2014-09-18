(function (jQ) {
  "use strict";

  brite.registerView("LoginView", {}, {

    create: function (data, config) {
      return jQ("#tmpl-LoginView").render();
    },

    postDisplay: function (data, config) {
    },

    events: {

      "submit; form": function (ev) {
        var view = this,
          element = jQ(ev.currentTarget),
          username = jQ("input[name=username]").val(),
          password = jQ("input[name=password]").val();
        view.$el.find("button").prop("disabled", true);
        
        svnadmin.service.login(username, password).done(function (data) {
          svnadmin.app.showMainView();
        }).fail(function () {
          alert(tr("Wrong login"));
        }).always(function () {
          view.$el.find("button").prop("disabled", false);
        });
        
        return ev.preventDefault();
      }

    }

  });

}(jQuery));
