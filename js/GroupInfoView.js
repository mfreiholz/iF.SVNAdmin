(function (jQ) {
  "use strict";
  brite.registerView("GroupInfoView", {}, {

    create: function (data) {
      var view = this;
      jQ.extend(view.options, data);
      return jQ("#tmpl-GroupInfoView").render({
        options: view.options
      });
    },

    postDisplay: function () {
      var view = this;
      view.showMembers();
    },

    events: {
      "click; .refresh-link": function (ev) {
        var view = this;
        view.showMembers();
      },
      "click; .assigngroup-link": function (ev) {
        var view = this;
        brite.display("BasicSearchDialogView", "body", {
          onSearchMore: function (query, offset, limit) {
            var def = new jQ.Deferred();
            svnadmin.service.searchGroups("", query, offset, limit)
              .done(function (data) {
                var res = {}, i = 0;
                res.hasMore = false;
                res.rows = [];
                for (i = 0; i < data.list.items.length; ++i) {
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
            var defs = [], i = 0;
            for (i = 0; i < ids.length; ++i) {
              // Assign user to selected groups.
              var def = svnadmin.service.groupMemberAssign(view.options.providerId, view.options.groupId, ids[i]);
              defs.push(def);
            }
            jQ.when.apply(null, defs)
              .done(function () {
                view.showMembers();
              })
              .fail(function () {
                alert("Error...");
                view.showMembers();
              });
          }
        }, { emptyParent: false });
      },
      "click; .assignuser-link": function (ev) {
        var view = this;
        brite.display("BasicSearchDialogView", "body", {
          onSearchMore: function (query, offset, limit) {
            var def = new jQ.Deferred();
            svnadmin.service.searchUsers("", query, offset, limit)
              .done(function (data) {
                var res = {}, i = 0;
                res.hasMore = false;
                res.rows = [];
                for (i = 0; i < data.list.items.length; ++i) {
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
            var defs = [], i = 0;
            for (i = 0; i < ids.length; ++i) {
              // Assign user to selected groups.
              var def = svnadmin.service.groupMemberAssign(view.options.providerId, view.options.groupId, ids[i]);
              defs.push(def);
            }
            jQ.when.apply(null, defs)
              .done(function () {
                view.showMembers();
              })
              .fail(function () {
                alert("Error...");
                view.showMembers();
              });
          }
        }, { emptyParent: false });
      }
    },

    /////////////////////////////////////////////////////////////////
    //
    /////////////////////////////////////////////////////////////////

    options: {
      providerId: null,
      groupId: null
    },

    showMembers: function () {
      var view = this;
      var options = {
        showPaging: true,
        showRowNumber: true,
        pageSize: 5,
        singleActions: [],
        multiActions: [
          {
            id: "unassign",
            name: tr("Unassign"),
            callback: function (ids) {
              var defs = [], i = 0;
              for (i = 0; i < ids.length; ++i) {
                defs.push(svnadmin.service.groupMemberUnassign(view.options.providerId, view.options.groupId, ids[i]));
              }
              return jQ.when.apply(null, defs).done(function () {
                view.showMembers(view.options.providerId);
              });
            }
          }
        ],
        columns: [
          { id: "", name: tr("Name") },
          { id: "", name: tr("Type") }
        ],
        loadMore: function (offset, num) {
          var def = new jQuery.Deferred();
          svnadmin.service.getMembersOfGroup(view.options.providerId, view.options.groupId, offset, num).done(function (resp) {
            var obj = {}, i = 0, row = null;
            obj.hasMore = resp.hasmore;
            obj.rows = [];
            for (i = 0; i < resp.members.length; ++i) {
              row = {};
              row.id = resp.members[i].id;
              row.cells = [resp.members[i].displayname, resp.members[i].type];
              obj.rows.push(row);
            }
            def.resolve(obj);
          }).fail(function () {
            def.reject();
          });
          return def.promise();
        }
      };
      brite.display("BasicTableView", view.$el.find(".members .panel-body"), { options: options }, {emptyParent: true });
    }

  });

}(jQuery));