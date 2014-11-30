(function (jQ) {
  "use strict";
  brite.registerView("GroupListView", {}, {

    create: function (data, config) {
      var view = this;
      return jQ("#tmpl-GroupListView").render();
    },

    postDisplay: function (data, config) {
      var view = this;
      svnadmin.service.getGroupProviders().done(function (resp) {
        var html = jQ("#tmpl-GroupListView-Providers").render({ providers: resp });
        view.$el.find(".provider-wrapper").html(html);
        if (typeof data.providerId !== "undefined") {
          view.showGroups(data.providerId);
        } else {
          view.showGroups(resp[0].id);
        }
      });
    },

    events: {

      "click; .provider-link": function (ev) {
        var view = this,
          element = jQ(ev.currentTarget),
          providerId = element.data("providerid");
        view.showGroups(providerId);
      },

      "click; .add-link": function (ev) {
        var view = this,
          element = jQ(ev.currentTarget),
          providerId = element.data("providerid");
        brite.display("GroupAddView", "body", { providerId: providerId, submitted: function () { view.showGroups(providerId); } }, { emptyParent: false });
      }

    }, // End of events.

    ///////////////////////////////////////////////////////////////////
    //
    ///////////////////////////////////////////////////////////////////

    showGroups: function (providerId) {
      var view = this;
      var html = jQ("#tmpl-GroupListView-GroupList").render({ providerId: providerId });
      view.$el.find(".grouplist-wrapper").html(html);

      var options = {
        showPaging: true,
        showRowNumber: true,
        pageSize: 5,

        singleActions: [
          {
            id: "info",
            getName: function (id) { return tr("Info"); },
            getLink: function (id) { return "#!/groupinfo?" + svnadmin.app.createUrlParameterString({ providerid: providerId, groupid: id }); },
            callback: function (id) { return svnadmin.app.showGroupInfoView(providerId, id); }
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
                promises.push(svnadmin.service.deleteGroup(providerId, ids[i]));
              }
              return jQ.when.apply(null, promises).done(function () {
                view.showGroups(providerId);
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
          svnadmin.service.getGroups(providerId, offset, num).done(function (resp) {
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

/*,

      "click; .info": function (ev) {
        var view = this,
          element = jQ(ev.currentTarget),
          providerId = element.data("providerid"),
          groupId = element.data("groupid");
        svnadmin.app.showGroupInfoView(providerId, groupId);
      },

      "click; .delete": function (ev) {
        var view = this,
          element = jQ(ev.currentTarget),
          providerId = element.data("providerid"),
          groupId = element.data("groupid");
        svnadmin.service.deleteGroup(providerId, groupId).done(function (resp) {
          showGroups(providerId, 0, -1);
        }).fail(function () {
          alert("Can not delete group.");
        });
      }*/