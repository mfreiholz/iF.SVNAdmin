(function (jQ) {
  "use strict";

  brite.registerView("MainView", { emptyParent: true }, {

    create: function (config, data) {
      return jQ("#tmpl-MainView").render();
    },

    postDisplay: function (config, data) {
      var width = jQ(window).width();
      if (width < 768) {
        jQ(".sidebar-collapse").collapse({toggle: false});
      } else {
        jQ(".sidebar-collapse").collapse({toggle: true});
      }
      // Show dashboard by default.
      brite.display("DashboardView", "#page-wrapper", {}, {emptyParent: true});
    },

    events: {
      "click; .navbar-toggle": function (ev) {
        var view = this;
        jQ(".sidebar-collapse").collapse("toggle");
      },
      "click; .dashboard-link": function (ev) {
        brite.display("DashboardView", "#page-wrapper", {}, {emptyParent: true});
      },
      "click; .users-link": function (ev) {
        svnadmin.app.showUserListView(undefined);
      },
      "click; .groups-link": function (ev) {
        svnadmin.app.showGroupListView(undefined);
      },
      "click; .repositories-link": function (ev) {
        svnadmin.app.showRepositoryListView(undefined);
      },
      "click; .logout-link": function (ev) {
        svnadmin.app.logout();
      }
    },

    winEvents: {
      "resize": function (ev) {
        var width = jQ(window).width();
        if (width < 768) {
          jQ(".sidebar-collapse").collapse("hide");
        } else {
          jQ(".sidebar-collapse").collapse("show");
        }
      }
    }

  });

}(jQuery));
