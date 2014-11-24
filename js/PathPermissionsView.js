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
      view.showMembers();
    },

    events: {
      "click; .refresh-link": function (ev) {
        var view = this;
        view.showMembers();
      },
      "click; .assign-user-link": function (ev) {
        var view = this;
      },
      "click; .assign-group-link": function (ev) {
        var view = this;
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

    showMembers: function () {
      var view = this;
      view.$el.find(".members-wrapper").html(jQuery("#tmpl-PathPermissionsView-Members").render({ options: view.options }));

      var options = {
        showPaging: false,
        showRowNumber: svnadmin.app.config.showtablerownumber,
        singleActions: [],
        multiActions: [
          {
            id: "unassign",
            name: tr("Unassign"),
            callback: function (ids) {
              var promises = [],
                i = 0;
              /*for (i = 0; i < ids.length; ++i) {
                promises.push(svnadmin.service.deleteRepositoryPath(providerId, repositoryId, ids[i]));
              }*/
              return jQuery.when.apply(null, promises).done(function () {
                view.showMembers();
              });
            }
          }
        ],
        columns: [
          { id: "", name: tr("Member") },
          { id: "", name: tr("Type") },
          { id: "", name: tr("Permission") }
        ],
        loadMore: function (offset, num) {
          var def = new jQuery.Deferred();
          svnadmin.service.getRepositoryPathPermissions(view.options.providerId, view.options.repositoryId, view.options.path).done(function (resp) {
            resp.permissions.sort(function (l, r) {
              if (l.member < r.member) {
                return -1;
              } else if (l.member > r.member) {
                return 1;
              }
              return 0;
            });

            var obj = {}, i = 0, row = null;
            obj.hasMore = false;
            obj.rows = [];
            for (i = 0; i < resp.permissions.length; ++i) {
              row = {};
              row.id = resp.permissions[i].member.id;
              row.cells = [resp.permissions[i].member.displayname, resp.permissions[i].member.type, svnadmin.app.translatePermission(resp.permissions[i].permission)];
              obj.rows.push(row);
            }
            def.resolve(obj);
          }).fail(function () {
            def.reject();
          });
          return def.promise();
        }
      };
      brite.display("BasicTableView", view.$el.find(".table-wrapper"), { options: options });
    }

  });
}());