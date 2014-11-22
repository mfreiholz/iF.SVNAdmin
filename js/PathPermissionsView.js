(function () {
  "use strict";
  brite.registerView("PathPermissionsView", {}, {

    create: function (data, config) {
      var view = this;
      jQuery.extend(view.options, data);
      return jQuery("#tmpl-PathPermissionsView").render({ options: view.options });
    },

    postDisplay: function (data, config) {
      var view = this;
      view.showUsers();
      view.showGroups();
    },

    events: {
      "click; .refresh-link": function (ev) {
        var view = this;
        view.showUsers();
        view.showGroups();
      }
    },

    ///////////////////////////////////////////////////////////////////
    //
    ///////////////////////////////////////////////////////////////////

    options: {
      providerId: "",
      repositoryId: "",
      path: ""
    },

    showUsers: function () {
      var view = this;
      view.$el.find(".users-wrapper").html(jQuery("#tmpl-PathPermissionsView-Users").render({ options: view.options }));
    },

    showGroups: function () {
      var view = this;
      view.$el.find(".groups-wrapper").html(jQuery("#tmpl-PathPermissionsView-Groups").render({ options: view.options }));
    }

  });
}());