(function (jQ) {
  "use strict";

  /**
   * Register view
   */
  brite.registerView("RepositoryListView", {}, {

    create: function (data, config) {
      var view = this;
      return jQ("#tmpl-RepositoryListView").render();
    },

    postDisplay: function (data, config) {
      var view = this;
      svnadmin.service.getRepositoryProviders().done(function (resp) {
        var html = jQ("#tmpl-RepositoryListView-Providers").render({ providers: resp });
        view.$el.find(".providers-wrapper").html(html);
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

      /*"click; .info-link": function (ev) {
        var view = this,
          element = jQ(ev.currentTarget),
          providerId = element.data("providerid"),
          repositoryId = element.data("repositoryid");
        //svnadmin.app.showRepositoryInfoView(providerId, repositoryId);
        alert("Show info: " + providerId + " / " + repositoryId);
      },*/

      /*"click; .delete-link": function (ev) {
        var view = this,
          element = jQ(ev.currentTarget),
          providerId = element.data("providerid"),
          repositoryId = element.data("repositoryid");
        svnadmin.service.deleteRepository(providerId, repositoryId).done(function (resp) {
          view.showRepositories(providerId);
        }).fail(function () {
          alert("Can not delete.");
        });
      }*/

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
          multiSelection: true,
          pageSize: 5,
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
          },
          onAction: function (actionId, ids) {
            window.alert("action=" + actionId + "; ids=" + JSON.stringify(ids));
          }
        };

      brite.display("BasicTableView", view.$el.find(".table-wrapper"), { options: options });
    }

  });

}(jQuery));