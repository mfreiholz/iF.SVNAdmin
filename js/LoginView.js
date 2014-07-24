(function (jQ) {
  "use strict";
  
  brite.registerView("LoginView", {}, {
    
    create: function (data, config) {
      return jQ("#tmpl-LoginView").render();
    },
    
    postDisplay: function (data, config) {
      
    },
    
    events: {
      
    }
    
  });
  
}(jQuery));