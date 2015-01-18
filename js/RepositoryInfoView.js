(function (jQ) {
  "use strict";
  brite.registerView("RepositoryInfoView", {}, {

    create: function (data) {
      var view = this;
      view.options = {
        providerId: "",
        repositoryId: ""
      };
      jQuery.extend(view.options, data);
      return jQuery("#tmpl-RepositoryInfoView").render({ options: view.options });
    },

    postDisplay: function (data) {
      var view = this;
      view.showBasics();
      view.showPaths(view.options.providerId, view.options.repositoryId);
    },

    events: {
      "click; .refresh-link": function (ev) {
        var view = this;
        view.showBasics();
        view.showPaths(view.options.providerId, view.options.repositoryId);
      },
      "click; .add-link": function (ev) {
        var view = this;
        brite.display("PathAddView", "body", {
          providerId: view.options.providerId,
          repositoryId: view.options.repositoryId,
          onSubmitted: function () {
            view.showPaths(view.options.providerId, view.options.repositoryId);
          }
        }, { emptyParent: false });
      }
    },

    ///////////////////////////////////////////////////////////////////

    showBasics: function () {
      var view = this;
      svnadmin.service.getRepositoryInfo(view.options.providerId, view.options.repositoryId).done(function (resp) {
        jQuery(".basics-wrapper").html(jQuery("#tmpl-RepositoryInfoView-Basics").render({
          options: view.options,
          response: resp
        }));
      });
    },

    showPaths: function (providerId, repositoryId) {
      var view = this;
      var html = jQ("#tmpl-RepositoryInfoView-PathList").render({ options: view.options });
      view.$el.find(".paths-wrapper").html(html);

      var options = {
        showPaging: false,
        showRowNumber: svnadmin.app.config.showtablerownumber,

        singleActions: [
          {
            id: "permissions",
            getName: function (id) { return tr("Permissions"); },
            getLink: function (id) { return "#!/pathpermissions?" + svnadmin.app.createUrlParameterString({ providerid: view.options.providerId, repositoryid: view.options.repositoryId, path: id }); },
            callback: function (id) {
              return svnadmin.app.showPathPermissions(providerId, repositoryId, id);
            }
          }
        ],

        multiActions: [
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
                promises.push(svnadmin.service.deleteRepositoryPath(providerId, repositoryId, ids[i]));
              }
              return jQ.when.apply(null, promises)
                .always(function () {
                  view.showPaths(providerId, repositoryId);
                });
            }
          }
        ],

        columns: [
          { id: "", name: tr("Path") }
        ],

        loadMore: function (offset, num) {
          var def = new jQuery.Deferred();
          svnadmin.service.getRepositoryPaths(providerId, repositoryId).done(function (resp) {
            resp.paths.sort(function (l, r) {
              if (l.path < r.path) {
                return -1;
              } else if (l.path > r.path) {
                return 1;
              }
              return 0;
            });

            var obj = {}, i = 0, row = null;
            obj.hasMore = false;
            obj.rows = [];
            for (i = 0; i < resp.paths.length; ++i) {
              row = {};
              row.id = resp.paths[i].path;
              row.cells = [resp.paths[i].path];
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
}(jQuery));