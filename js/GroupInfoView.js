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
        multiActions: [],
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
    },

    showRoles: function () {
    }

  });

}(jQuery));