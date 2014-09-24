(function (jQ) {
  "use strict";
  var _providerId = "";
  
  function showRepositories(providerId, offset, num) {
    if (!providerId) {
      return;
    }
    offset = !offset ? 0 : offset;
    num = !num ? 10 : num;
    _providerId = providerId;
    jQ(".RepositoryListViewProviders li").removeClass("active");
    
    return svnadmin.service.getUsers(providerId, offset, num).done(function (data) {
      jQ(".RepositoryListViewProviders li[data-id=" + providerId + "]").addClass("active");
      jQ(".table-wrapper").html(jQ("#tmpl-RepositoryListViewUserTable").render({
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
  
  brite.registerView("RepositoryListView", {}, {
    
    create: function () {
      return jQ("#tmpl-RepositoryListView").render();
    },
    
    postDisplay: function () {
      // Providers.
      svnadmin.service.getRepositoryProviders().done(function (resp) {
        jQ(".provider-selection-wrapper").html(
          jQ("#tmpl-RepositoryListViewProviders").render({
            providers: resp
          })
        );
        showRepositories(resp[0].id);
      });
    },
    
    events: {
    }
    
  });
  
}(jQuery));