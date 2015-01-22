(function () {
  "use strict";
  brite.registerView("UserChangePasswordView", {}, {
    
    create: function (data, config) {
      var view = this;
      view.options = {
        providerId: "",
        userId: "",
        submitted: function () {}
      };
      jQuery.extend(view.options, data);
      return jQuery("#tmpl-UserChangePasswordView").render(view.options);
    },
    
    postDisplay: function (data, config) {
      var view = this;
      view.$el.find("#userchangepasswordmodal").modal({ show: true });
      view.$el.find("#userchangepasswordmodal").on("hidden.bs.modal", function (ev) { view.$el.bRemove(); });
    },
    
    events: {
      
      "submit; form": function (ev) {
        var view = this,
          element = jQuery(ev.currentTarget),
          password = view.$el.find("input[name='password']").val(),
          password2 = view.$el.find("input[name='password2']").val();
        ev.preventDefault();
        // Validate form.
        if (!view.options.providerId || !view.options.userId || !password || !password2) {
          return;
        } else if (password !== password2) {
          alert(tr("Passwords doesn't match."));
          return;
        }
        // Send password change to server.
        svnadmin.service.changePassword(view.options.providerId, view.options.userId, password)
          .done(function (res) {
            view.$el.find("#userchangepasswordmodal").modal("hide");
            view.options.submitted();
          })
          .fail(function () {
            alert(tr("Internal error."));
          });
      },
      
      "click; button.submit": function (ev) {
        var view = this;
        view.$el.find("form").submit();
      }
      
    }
    
  });
}());