(function (jQ) {
  "use strict";
  brite.registerView("MainView", { emptyParent: true }, {

    create: function (config, data) {
      return jQ("#tmpl-MainView").render();
    },

    postDisplay: function (config, data) {
      var view = this;
      view.$el.find(".dashboard-link").trigger("click");
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
        var view = this;
        view.setActiveNavLink(ev.currentTarget.className);
        svnadmin.app.showRepositoryListView(undefined);
      },
      "click; .users-link": function (ev) {
        var view = this;
        view.setActiveNavLink(ev.currentTarget.className);
        svnadmin.app.showUserListView(undefined);
      },
      "click; .groups-link": function (ev) {
        var view = this;
        view.setActiveNavLink(ev.currentTarget.className);
        svnadmin.app.showGroupListView(undefined);
      },
      "click; .logout-link": function (ev) {
        var view = this;
        view.setActiveNavLink(ev.currentTarget.className);
        svnadmin.app.logout();
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
