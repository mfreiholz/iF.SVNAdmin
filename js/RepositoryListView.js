(function (jQ) {
  "use strict";
  brite.registerView("RepositoryListView", {}, {

    create: function (data, config) {
      var view = this;
      return jQ("#tmpl-RepositoryListView").render();
    },

    postDisplay: function (data, config) {
      var view = this;
      svnadmin.service.getRepositoryProviders().done(function (resp) {
        var html = jQ("#tmpl-RepositoryListView-Providers").render({ providers: resp });
        view.$el.find(".provider-wrapper").html(html);
        view.showRepositories(resp[0].id);
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

    showRepositories: function (providerId) {
      var view = this;
      var html = jQ("#tmpl-RepositoryListView-RepositoryList").render({ providerId: providerId });
      view.$el.find(".repositorylist-wrapper").html(html);

      var options = {
          showPaging: true,
          showRowNumber: svnadmin.app.config.showtablerownumber,
          pageSize: svnadmin.app.config.tablepagesize,

          singleActions: [
            {
              id: "permissions",
              getName: function (id) { return tr("Permissions"); },
              getLink: function (id) { return "#!/repositories/" + providerId + "/" + id + "/permissions"; },
              callback: function (id) {
                var def = new jQ.Deferred();
                def.resolve();
                window.alert("Show permissions of repository.\nprovider=" + providerId + "\nrepositoryid=" + id);
                return def.promise();
              }
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
                  promises.push(svnadmin.service.deleteRepository(providerId, ids[i]));
                }
                return jQ.when.apply(null, promises).done(function () {
                  view.showRepositories(providerId);
                });
              }
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
              obj.hasMore = resp.hasmore;
              obj.rows = [];
              for (i = 0; i < resp.repositories.length; ++i) {
                row = {};
                row.id = resp.repositories[i].id;
                row.cells = [resp.repositories[i].displayname];
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

/*"click; .info-link": function (ev) {
        var view = this,
          element = jQ(ev.currentTarget),
          providerId = element.data("providerid"),
          repositoryId = element.data("repositoryid");
        //svnadmin.app.showRepositoryInfoView(providerId, repositoryId);
        alert("Show info: " + providerId + " / " + repositoryId);
      },*/