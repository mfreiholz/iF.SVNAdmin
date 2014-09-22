(function (jQ) {
  "use strict";
  
  brite.registerView("GroupInfoView", {}, {
    
    create: function (data, config) {
      return jQ("#tmpl-GroupInfoView").render({ providerId: data.providerId, groupId: data.groupId });
    },
    
    postDisplay: function () {
    },
    
    events: {
    }
    
  });

}(jQuery));