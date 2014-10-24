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
      view.showUsers();
    },

    events: {
    },

    /////////////////////////////////////////////////////////////////
    //
    /////////////////////////////////////////////////////////////////

    options: {
      providerId: null,
      groupId: null
    },

    showUsers: function () {
      var view = this;
      var options = {
        showPaging: true,
        showRowNumber: true,
        pageSize: 5,
        singleActions: [],
        multiActions: [],
        columns: [
          { id: "", name: "Name" }
        ],
        loadMore: function (offset, num) {
          var def = new jQuery.Deferred();
          svnadmin.service.getUsersOfGroup(view.options.providerId, view.options.groupId, offset, num).done(function (resp) {
            var obj = {}, i = 0, row = null;
            obj.hasMore = resp.hasmore;
            obj.rows = [];
            for (i = 0; i < resp.users.length; ++i) {
              row = {};
              row.id = resp.users[i].id;
              row.cells = [resp.users[i].displayname];
              obj.rows.push(row);
            }
            def.resolve(obj);
          }).fail(function () {
            def.reject();
          });
          return def.promise();
        }
      };
      brite.display("BasicTableView", view.$el.find(".users .panel-body"), { options: options });
    },

    showRoles: function () {
    }

  });

}(jQuery));