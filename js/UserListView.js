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
        view.cache.providers = resp;
        if (typeof data.providerId !== "undefined") {
          view.showUsers(data.providerId);
        } else {
          view.showUsers(resp[0].id);
        }
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
      
    cache: {
      providers: []
    },
    
    getProviderInfo: function (id) {
      var view = this, i = 0;
      for (i = 0; i < view.cache.providers.length; ++i) {
        if (view.cache.providers[i].id === id) {
          return view.cache.providers[i];
        }
      }
      return undefined;
    },

    showUsers: function (providerId) {
      var view = this,
        provider = view.getProviderInfo(providerId),
        html = jQ("#tmpl-UserListView-UserList").render({ providerId: providerId, provider: provider });
      view.$el.find(".userlist-wrapper").html(html);

      var options = {
        showPaging: true,
        showRowNumber: svnadmin.app.config.showtablerownumber,
        pageSize: svnadmin.app.config.tablepagesize,
        singleActions: [
          {
            id: "info",
            getName: function (id) { return tr("Info"); },
            getLink: function (id) { return "#!/userinfo?" + svnadmin.app.createUrlParameterString({providerid: providerId, userid: id}); },
            callback: function (id) { return svnadmin.app.showUserInfoView(providerId, id); }
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
      
      if (provider.editable) {
        options.multiActions = [
          {
            id: "delete",
            name: tr("Delete"),
            callback: function (ids) {
              if (!window.confirm(tr("Are you sure?"))) {
                return new jQuery.Deferred().resolve().promise();
              }
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
        ];
      }
      
      brite.display("BasicTableView", view.$el.find(".table-wrapper"), { options: options });
    }

  });

}(jQuery));