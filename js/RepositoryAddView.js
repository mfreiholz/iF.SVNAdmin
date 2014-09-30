(function (jQ) {
  "use strict";

  brite.registerView("RepositoryAddView", {}, {
    
    create: function (data, config) {
      return jQ("#tmpl-RepositoryAddView").render({
        providerId: data.providerId
      });
    },
    
    postDisplay: function (data, config) {
      var view = this;
      view.$el.find("#repositoryaddmodal").modal({ show: true });
      view.$el.find("#repositoryaddmodal").on("hidden.bs.modal", function (ev) {
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
          name = view.$el.find("input[name='name']").val();
        ev.preventDefault();
        
        // Validate form.
        if (!providerId || !name) {
          return;
        }
        
        // Create.
        svnadmin.service.createRepository(providerId, name).done(function (resp) {
          view.$el.find("#repositoryaddmodal").modal("hide");
        }).fail(function () {
          alert("Can not create repository!");
        });
      },
      
      "click; button.submit": function (ev) {
        var view = this;
        view.$el.find("form").submit();
      }
      
    }
    
  });

}(jQuery));