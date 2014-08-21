(function (jQ) {
  "use strict";

  /**
   * Register View
   */
  brite.registerView("UserInfoView", {}, {

    create: function (config, data) {
      var providerId = data.providerid,
        userId = data.userid;
      return jQ("#tmpl-UserInfoView").render(data);
    },

    postDisplay: function (config, data) {
      var view = this,
        providerId = data.providerid,
        userId = data.userid;

    },

    events: {
    }

  });

}(jQuery));
