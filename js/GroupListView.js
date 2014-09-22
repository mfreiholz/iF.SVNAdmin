(function (jQ) {
  "use strict";
  var _providerId = "";
  
  function showGroups(providerId, offset, num) {
    if (!providerId) {
      return;
    }
    offset = !offset ? 0 : offset;
    num = !num ? 10 : num;
    _providerId = providerId;
    jQ(".GroupListViewProviders li").removeClass("active");
    
    return svnadmin.service.getGroups(providerId, offset, num).done(function (resp) {
      jQ(".GroupListViewProviders li[data-id=" + providerId + "]").addClass("active");
      jQ(".table-wrapper").html(jQ("#tmpl-GroupListViewGroupTable").render({
        response: resp
      }));
    }).fail(function () {
      alert("FAIL: Can not fetch groups.");
    });
  }
  
  /**
   * Register view
   */
  brite.registerView("GroupListView", {}, {
    
    create: function (data, config) {
      return jQ("#tmpl-GroupListView").render();
    },
    
    postDisplay: function (data, config) {
      // Get providers.
      svnadmin.service.getGroupProviders().done(function (resp) {
        var html = jQ("#tmpl-GroupListViewProviders").render({ providers: resp, current: resp[0].id });
        jQ(".provider-selection-wrapper").html(html);
        showGroups(resp[0].id);
      });
    },
    
    events: {
      
      "click; .provider-link": function (ev) {
        var view = this,
          element = jQ(ev.currentTarget),
          providerId = element.data("id");
        showGroups(providerId);
      },
      
      "click; .add-link": function (ev) {
        var view = this,
          element = jQ(ev.currentTarget);
        brite.display("GroupAddView", "body", { providerId: _providerId }, { emptyParent: false });
        // TODO Refresh group list on finish.
      }
      
    }
    
  });
  
}(jQuery));