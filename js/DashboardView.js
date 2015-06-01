(function (jQ) {
  "use strict";

  brite.registerView("DashboardView", {}, {
  
    create: function (config, data) {
      var view = this,
        def = new jQ.Deferred(),
        promises = [],
        p = null;

      // Load system info.
      p = svnadmin.service.getSystemInfo().done(function (data) {
        var html = jQ("#tmpl-SystemInfoPanelView").render({ response: data });
        return html;
      });
      promises.push(p);

      // Load file system info.
      p = svnadmin.service.getFileSystemInfo().done(function (data) {
        var html = jQ("#tmpl-FileSystemInfoPanelView").render({ response: data });
        return html;
      });
      promises.push(p);

      jQ.when.apply(this, promises)
        .done(function (res1, res2) {
          var html = jQ("#tmpl-DashboardView").render({ response: jQ.extend({}, res1[0], res2[0]) });
          def.resolve(html);
        })
        .fail(function () {
          def.reject();
        });

      return def.promise();
    }
  
  });

}(jQuery));