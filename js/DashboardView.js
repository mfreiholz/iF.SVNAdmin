(function (jQ) {
  "use strict";

  brite.registerView("DashboardView", {}, {
  
    create: function (config, data) {
      return jQ("#tmpl-DashboardView").render();
    },

    postDisplay: function (config, data) {
      // Load system info.
      svnadmin.service.getSystemInfo().done(function (data) {
        var html = jQ("#tmpl-SystemInfoPanelView").render({ response: data });
        jQ(".system-info-wrapper").html(html);
      });
      
      // Load file system info.
      svnadmin.service.getFileSystemInfo().done(function (data) {
        var html = jQ("#tmpl-FileSystemInfoPanelView").render({ response: data });
        jQ(".filesystem-info-wrapper").html(html);
      });
    },
    
    events: {
    }
  
  });

}(jQuery));