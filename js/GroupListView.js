(function (jQ) {
  
  brite.registerView("GroupListView", {}, {
    
    create: function (data, config) {
      return jQ("#tmpl-GroupListView").render();
    },
    
    postDisplay: function (data, config) {
      // Get providers.
      svnadmin.service.getGroupProviders().done(function (resp) {
        var html = jQ("#tmpl-GroupListViewProviders").render({ providers: resp, current: resp[0].id });
        jQ(".provider-selection-wrapper").html(html);
        //showGroups(data[0].id);
      });
    },
    
    events: {
      "click; .provider-link": function (ev) {
        var view = this,
          element = jQ(ev.currentTarget),
          providerId = element.data("id");
        //showGroups(providerId);
      },
      
    }
    
  });
  
}(jQuery));