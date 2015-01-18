(function (jQ) {
  "use strict";
  brite.registerView("RepositoryListView", {}, {

    create: function (data, config) {
      var view = this;
      view.cache = {
        providers: []
      };
      return jQ("#tmpl-RepositoryListView").render();
    },

    postDisplay: function (data, config) {
      var view = this;
      svnadmin.service.getRepositoryProviders().done(function (resp) {
        var html = jQ("#tmpl-RepositoryListView-Providers").render({ providers: resp });
        view.$el.find(".provider-wrapper").html(html);
        view.cache.providers = resp;
        if (typeof data.providerId !== "undefined") {
          view.showRepositories(data.providerId);
        } else {
          view.showRepositories(resp[0].id);
        }
      });
    },

    events: {
      "click; .provider-link": function (ev) {
        var view = this,
          element = jQ(ev.currentTarget),
          providerId = element.data("providerid");
        view.showRepositories(providerId);
      },
      "click; .add-link": function (ev) {
        var view = this,
          element = jQ(ev.currentTarget),
          providerId = element.data("providerid");
        brite.display("RepositoryAddView", "body", { providerId: providerId, submitted: function () { view.showRepositories(providerId); } }, { emptyParent: false });
      }
    },

    ///////////////////////////////////////////////////////////////////
    //
    ///////////////////////////////////////////////////////////////////

    getProviderInfo: function (id) {
      var view = this, i = 0;
      for (i = 0; i < view.cache.providers.length; ++i) {
        if (view.cache.providers[i].id === id) {
          return view.cache.providers[i];
        }
      }
      return undefined;
    },

    showRepositories: function (providerId) {
      var view = this;
      var provider = view.getProviderInfo(providerId);
      var html = jQ("#tmpl-RepositoryListView-RepositoryList").render({ providerId: providerId, provider: provider });
      view.$el.find(".repositorylist-wrapper").html(html);

      var options = {
        showPaging: true,
        showRowNumber: svnadmin.app.config.showtablerownumber,
        pageSize: svnadmin.app.config.tablepagesize,
        singleActions: [
          {
            id: "info",
            getName: function (id) { return tr("Info"); },
            getLink: function (id) { return "#!/repositoryinfo?" + svnadmin.app.createUrlParameterString({providerid: providerId, repositoryid: id}); },
            callback: function (id) { return svnadmin.app.showRepositoryInfoView(providerId, id); }
          }
        ],
        columns: [
          { id: "", name: tr("Name") }
        ],
        loadMore: function (offset, num) {
          view.$el.find("li.provider").removeClass("active");
          var def = new jQuery.Deferred();
          svnadmin.service.getRepositories(providerId, offset, num).done(function (resp) {
            var obj = {}, i = 0, row = null;
            obj.hasMore = resp.list.hasmore;
            obj.rows = [];
            for (i = 0; i < resp.list.items.length; ++i) {
              row = {};
              row.id = resp.list.items[i].id;
              row.cells = [resp.list.items[i].displayname];
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
                promises.push(svnadmin.service.deleteRepository(providerId, ids[i]));
              }
              return jQ.when.apply(null, promises).done(function () {
                view.showRepositories(providerId);
              });
            }
          }
        ];
      }

      brite.display("BasicTableView", view.$el.find(".table-wrapper"), { options: options });
    }

  });

}(jQuery));