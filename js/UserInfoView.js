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
      view.showRoles();
    },

    events: {
      "click; .refresh-link": function (ev) {
        var view = this;
        view.showGroups();
        view.showRoles();
      },
      "click; .assigngroup-link": function (ev) {
        var view = this;
        brite.display("BasicSearchDialogView", "body", {
          searchMore: function (query, offset, limit) {
            var def = new jQ.Deferred();
            svnadmin.service.searchGroups("", query, offset, limit)
              .done(function (data) {
                var res = {};
                res.hasMore = false;
                res.rows = [];
                for (var i = 0; i < data.list.items.length; ++i) {
                  var row = {};
                  row.id = data.list.items[i].id;
                  res.rows.push(row);
                }
                def.resolve(res);
              })
              .fail(function () {
                def.reject();
              });
            return def.promise();
          },
          onSubmitted: function (ids) {
            alert("ids=" + JSON.stringify(ids));
            var defs = [];
            for (var i = 0; i < ids.length; ++i) {
              // Assign user to selected groups.
              var def = svnadmin.service.groupMemberAssign(view.options.providerId, ids[i], view.options.userId);
              defs.push(def);
            }
            jQ.when.apply(null, defs)
              .done(function () {
                view.showGroups();
              })
              .fail(function () {
                alert("Error...");
              });
          }
        }, { emptyParent: false });
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
          { id: "", name: tr("Name") }
        ],
        loadMore: function (offset, num) {
          var def = new jQuery.Deferred();
          svnadmin.service.getGroupsOfMember(view.options.providerId, view.options.userId, offset, num).done(function (resp) {
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
