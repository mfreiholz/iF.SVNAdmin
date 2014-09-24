(function (jQ) {
  "use strict";
  
  brite.registerView("UserAddView", {}, {
    
    create: function (data, config) {
      return jQ("#tmpl-UserAddView").render({
        providerId: data.providerId
      });
    },
    
    postDisplay: function (data, config) {
      var view = this;
      view.$el.find("#useraddmodal").modal({ show: true });
      view.$el.find("#useraddmodal").on("hidden.bs.modal", function (ev) {
        view.$el.bRemove();
        if (typeof data.submitted === "function") {
          data.submitted();
        }
      });
    },
    
    events: {
      
      "submit; form": function (ev) {
        var view = this,
          element = jQ(ev.currentTarget),
          providerId = view.$element.data("providerid"),
          name = view.$el.find("input[name='name']").val(),
          password = view.$el.find("input[name='password']").val(),
          password2 = view.$el.find("input[name='password2']").val();
        ev.preventDefault();
        
        // Validate form.
        if (!providerId || !name || !password || !password2 || password !== password2) {
          return;
        }
        
        // Create.
        svnadmin.service.createUser(providerId, name, password).done(function (resp) {
          view.$el.find("#useraddmodal").modal("hide");
        }).fail(function () {
          alert("Can not create user.");
        });
      },
      
      "click; button.submit": function (ev) {
        var view = this;
        view.$el.find("form").submit();
      }
      
    }
    
  });
  
  
}(jQuery));