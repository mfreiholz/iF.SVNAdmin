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
        svnadmin.service.login(username, password).done(function (data) {
          svnadmin.app.showMainView();
        })
        .fail(function () {
          alert(tr("Wrong login"));
        });
        return ev.preventDefault();
      }

    }

  });

}(jQuery));
