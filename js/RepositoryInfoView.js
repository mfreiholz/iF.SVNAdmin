(function () {
  "use strict";
  brite.registerView("RepositoryInfoView", {}, {

    create: function (data) {
      var view = this;
      jQuery.extend(view.options, data);
      return jQuery("#tmpl-RepositoryInfoView").render({ options: view.options });
    },

    postDisplay: function (data) {
      var view = this;
    },

    events: {
    },

    ///////////////////////////////////////////////////////////////////
    //
    ///////////////////////////////////////////////////////////////////

    options: {
      providerId: "",
      repositoryId: ""
    },

    showPaths: function (providerId, repositoryId) {
    }

  });
}());