(function (jQ) {
  "use strict";
  
  function showProviders(view) {
    return svnadmin.service.getRepositoryProviders().done(function (resp) {
      view.$el.find(".providers-wrapper").html(
        jQ("#tmpl-RepositoryListView-Providers").render({
          providers: resp
        })
      );
    });
  }
  
  function showRepositories(view, providerId, offset, num) {
    if (!view || !providerId) {
      return;
    }

    offset = !offset ? 0 : offset;
    num = !num ? 10 : num;
    view.$el.find("li.provider").removeClass("active");
    
    return svnadmin.service.getRepositories(providerId, offset, num).done(function (resp) {
      view.$el.find("li.provider[data-id=" + providerId + "]").addClass("active");
      jQ(".table-wrapper").html(jQ("#tmpl-RepositoryListView-RepositoryList").render({
        response: resp,
        providerId: providerId,
        providerEditable: resp.editable,
        offset: offset,
        num: num
      }));
    }).fail(function () {
      alert("FAIL: Can not fetch repositories.");
    });
  }
  
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
      showProviders(view).done(function (resp) {
        showRepositories(view, resp[0].id);
      });
    },
    
    events: {
      
      "click; .provider-link": function (ev) {
        var view = this,
          element = jQ(ev.currentTarget),
          providerId = element.data("id");
        showRepositories(view, providerId);
      },
      
      "click; .add-link": function (ev) {
        var view = this,
          element = jQ(ev.currentTarget),
          providerId = element.data("providerid");
        brite.display("RepositoryAddView", "body", { providerId: providerId, submitted: function () { showRepositories(view, providerId); } }, { emptyParent: false });
      },
      
      "click; .info-link": function (ev) {
        var view = this,
          element = jQ(ev.currentTarget),
          providerId = element.data("providerid"),
          repositoryId = element.data("repositoryid");
        //svnadmin.app.showRepositoryInfoView(providerId, repositoryId);
        alert("Show info: " + providerId + " / " + repositoryId);
      },
      
      "click; .delete-link": function (ev) {
        var view = this,
          element = jQ(ev.currentTarget),
          providerId = element.data("providerid"),
          repositoryId = element.data("repositoryid");
        svnadmin.service.deleteRepository(providerId, repositoryId).done(function (resp) {
          showRepositories(view, providerId);
        }).fail(function () {
          alert("Can not delete.");
        });
      }
      
    }
  });
  
}(jQuery));