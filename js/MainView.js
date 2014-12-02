(function (jQ) {
  "use strict";
  brite.registerView("MainView", { emptyParent: true }, {

    create: function (config, data) {
      return jQ("#tmpl-MainView").render();
    },

    postDisplay: function (config, data) {
    },

    events: {
      "click; .navbar-toggle": function (ev) {
        var view = this;
        jQ(".sidebar-collapse").collapse("toggle");
      },
      "click; .dashboard-link": function (ev) {
        var view = this;
        view.setActiveNavLink(ev.currentTarget.className);
        svnadmin.app.showDashboard();
      },
      "click; .repositories-link": function (ev) {
        var view = this,
          providerId = jQ(ev.currentTarget).data("providerid");
        view.setActiveNavLink(ev.currentTarget.className);
        svnadmin.app.showRepositoryListView(providerId);
      },
      "click; .users-link": function (ev) {
        var view = this,
          element = jQuery(ev.currentTarget),
          providerId = element.data("providerid");
        view.setActiveNavLink(ev.currentTarget.className);
        svnadmin.app.showUserListView(providerId);
      },
      "click; .groups-link": function (ev) {
        var view = this,
          element = jQuery(ev.currentTarget),
          providerId = element.data("providerid");
        view.setActiveNavLink(ev.currentTarget.className);
        svnadmin.app.showGroupListView(providerId);
      },
      "click; .logout-link": function (ev) {
        var view = this;
        view.setActiveNavLink(ev.currentTarget.className);
        svnadmin.app.logout();
      },
      "click; .repositoryinfo-link": function (ev) {
        var view = this,
          element = jQuery(ev.currentTarget),
          providerId = element.data("providerid"),
          repositoryId = element.data("repositoryid");
        view.setActiveNavLink("repository-link");
        svnadmin.app.showRepositoryInfoView(providerId, repositoryId);
      }
    },

    ///////////////////////////////////////////////////////////////////
    //
    ///////////////////////////////////////////////////////////////////

    setActiveNavLink: function (linkClass) {
      var view = this;
      view.$el.find("ul.nav li").removeClass("active");
      view.$el.find("ul.nav li a[class='" + linkClass + "']").closest("li").addClass("active");
    }

  });

}(jQuery));
