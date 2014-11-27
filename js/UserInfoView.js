(function (jQ) {
  "use strict";
  brite.registerView("UserInfoView", {}, {

    create: function (data) {
      var view = this;
      jQ.extend(view.options, data);
      return jQ("#tmpl-UserInfoView").render({
        options: view.options
      });
    },

    postDisplay: function () {
      var view = this;
      view.showGroups();
    },

    events: {
      "click; .refresh-link": function (ev) {
        var view = this;
        view.showGroups();
      }
    },

    /////////////////////////////////////////////////////////////////
    //
    /////////////////////////////////////////////////////////////////

    options: {
      providerId: "",
      userId: ""
    },

    showGroups: function () {
      var view = this,
        providerId = view.options.providerId,
        userId = view.options.userId;

      var options = {
        showPaging: true,
        showRowNumber: true,
        pageSize: 5,
        singleActions: [],
        multiActions: [
          /*{
            id: "unassign",
            name: tr("Unassign"),
            callback: function (ids) {
              var promises = [], i = 0;
              for (i = 0; i < ids.length; ++i) {
                promises.push(svnadmin.service.userUnassignGroup(providerId, userId, ids[i]));
              }
              return jQ.when.apply(null, promises).done(function () {
                view.showGroups(providerId);
              });
            }
          }*/
        ],
        columns: [
          { id: "", name: "Name" }
        ],
        loadMore: function (offset, num) {
          var def = new jQuery.Deferred();
          svnadmin.service.getGroupsOfUser(view.options.providerId, view.options.userId, offset, num).done(function (resp) {
            var obj = {}, i = 0, row = null;
            obj.hasMore = resp.hasmore;
            obj.rows = [];
            for (i = 0; i < resp.groups.length; ++i) {
              row = {};
              row.id = resp.groups[i].id;
              row.cells = [resp.groups[i].displayname];
              obj.rows.push(row);
            }
            def.resolve(obj);
          }).fail(function () {
            def.reject();
          });
          return def.promise();
        }
      };
      brite.display("BasicTableView", view.$el.find(".groups .panel-body"), { options: options }, { emptyParent: true });
    },

    showRoles: function () {
    }

  });
}(jQuery));
