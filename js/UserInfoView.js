(function (jQ) {
  "use strict";

  /**
   * Register View
   */
  brite.registerView("UserInfoView", {}, {

    create: function (data, config) {
      var providerId = data.providerId,
        userId = data.userId;
      return jQ("#tmpl-UserInfoView").render(data);
    },

    postDisplay: function (data, config) {
      var view = this,
        providerId = data.providerId,
        userId = data.userId,
        htmlGroups = "",
        htmlRoles = "";

      // Groups of user.
      svnadmin.service.getGroupsOfUser(providerId, userId).done(function (response) {
        htmlGroups = jQ("#tmpl-UserInfoView-Groups").render(response);
        view.$el.find(".groups-wrapper").html(htmlGroups);
      });

      // Roles of user.
      htmlRoles = jQ("#tmpl-UserInfoView-Roles").render();
      view.$el.find(".roles-wrapper").html(htmlRoles);
    },

    events: {

      "click; .users-link": function (ev) {
        var view = this,
          element = jQ(ev.currentTarget),
          providerId = element.data("providerid");
        svnadmin.app.showUserListView(providerId);
      }

    }

  });

}(jQuery));
