(function (jQ) {
  "use strict";
  brite.registerView("UserListView", {}, {

    create: function (data, config) {
      var view = this;
      return jQ("#tmpl-UserListView").render();
    },

    postDisplay: function (data, config) {
      var view = this;
      svnadmin.service.getUserProviders().done(function (resp) {
        var html = jQ("#tmpl-UserListView-Providers").render({ providers: resp });
        view.$el.find(".provider-wrapper").html(html);
        view.showUsers(resp[0].id);
      });
    },

    events: {

      "click; .provider-link": function (ev) {
        var view = this,
          element = jQ(ev.currentTarget),
          providerId = element.data("providerid");
        view.showUsers(providerId);
      },

      "click; .add-link": function (ev) {
        var view = this,
          element = jQ(ev.currentTarget),
          providerId = element.data("providerid");
        brite.display("UserAddView", "body", { providerId: providerId, submitted: function () { view.showUsers(providerId); } }, { emptyParent: false });
      }

    },  // End of events.

    ///////////////////////////////////////////////////////////////////
    //
    ///////////////////////////////////////////////////////////////////

    showUsers: function (providerId) {
      var view = this;
      var html = jQ("#tmpl-UserListView-UserList").render({ providerId: providerId });
      view.$el.find(".userlist-wrapper").html(html);

      var options = {
        showPaging: true,
        showRowNumber: true,
        pageSize: 5,

        singleActions: [
          {
            id: "info",
            getName: function (id) { return tr("Info"); },
            getLink: function (id) { return "#!/users/" + providerId + "/" + id + "/info"; },
            callback: function (id) { return svnadmin.app.showUserInfoView(providerId, id); }
          }
        ],

        multiActions: [
          {
            id: "delete",
            name: tr("Delete"),
            callback: function (ids) {
              var promises = [],
                i = 0;
              for (i = 0; i < ids.length; ++i) {
                promises.push(svnadmin.service.deleteUser(providerId, ids[i]));
              }
              return jQ.when.apply(null, promises).done(function () {
                view.showUsers(providerId);
              });
            }
          }
        ],

        columns: [
          { id: "", name: tr("Name") },
          { id: "", name: tr("Login") }
        ],

        loadMore: function (offset, num) {
          view.$el.find("li.provider").removeClass("active");
          var def = new jQuery.Deferred();
          svnadmin.service.getUsers(providerId, offset, num).done(function (resp) {
            var obj = {}, i = 0, row = null;
            obj.hasMore = resp.hasmore;
            obj.rows = [];
            for (i = 0; i < resp.users.length; ++i) {
              row = {};
              row.id = resp.users[i].id;
              row.cells = [resp.users[i].displayname, resp.users[i].name];
              obj.rows.push(row);
            }
            def.resolve(obj);
            view.$el.find("li.provider[data-providerid=" + providerId + "]").addClass("active");
          }).fail(function () {
            def.reject();
          });
          return def.promise();
        }
      };
      brite.display("BasicTableView", view.$el.find(".table-wrapper"), { options: options });
    }

  });

}(jQuery));
/*

  function showUsers(providerId, offset, num) {
    if (!providerId) {
      return;
    }
    offset = !offset ? 0 : offset;
    num = !num ? 10 : num;
    _providerId = providerId;
    jQ(".UserListViewProviders li").removeClass("active");

    return svnadmin.service.getUsers(providerId, offset, num).done(function (data) {
      jQ(".UserListViewProviders li[data-id=" + providerId + "]").addClass("active");
      jQ(".user-table-wrapper").html(jQ("#tmpl-UserListViewUserTable").render({
        response: data,
        providerId: providerId,
        providerEditable: data.editable,
        offset: offset,
        num: num
      }));
    }).fail(function () {
      alert("FAIL: Can not fetch users.");
    });
  }


      "click; a.user-link": function (ev) {
        var view = this,
          element = jQ(ev.currentTarget),
          userId = element.data("id");
        svnadmin.app.showUserInfoView(_providerId, userId);
      },

      "click; button.deleteuser": function (ev) {
        var view = this,
          checkedElements = jQ("input[name=user-selection]:checked"),
          defs = [],
          i = 0;
        for (i = 0; i < checkedElements.length; ++i) {
          var elem = jQ(checkedElements[i]);
          var providerId = elem.data("providerid");
          var userId = elem.data("userid");
          defs.push(svnadmin.service.deleteUser(providerId, userId));
        }
        jQ.when(defs).done(function () {
          showUsers(_providerId);
        }).fail(function () {
          alert("ERROR");
        });
      },

      "click; li:not(.disabled) a.previous-page": function (ev) {
        var view = this,
          ele = jQ(ev.currentTarget),
          offset = ele.data("offset"),
          num = ele.data("num");
        showUsers(_providerId, offset, num);
        return ev.preventDefault();
      },

      "click; li:not(.disabled) a.next-page:not(.disabled)": function (ev) {
        var view = this,
          ele = jQ(ev.currentTarget),
          offset = ele.data("offset"),
          num = ele.data("num");
        showUsers(_providerId, offset, num);
        return ev.preventDefault();
      }

*/