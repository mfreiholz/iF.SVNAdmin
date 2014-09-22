(function (jQ) {
  "use strict";

  brite.registerView("GroupAddView", {}, {
    
    create: function (data, config) {
      return jQ("#tmpl-GroupAddView").render({
        providerId: data.providerId
      });
    },
    
    postDisplay: function (data, config) {
      var view = this;
      view.$el.find("#groupaddmodal").modal({ show: true });
      view.$el.find("#groupaddmodal").on("hidden.bs.modal", function (ev) {
        view.$el.bRemove();
        // TODO Do callback => done
      });
    },
    
    events: {
      
      "submit; form": function (ev) {
        var view = this,
          element = jQ(ev.currentTarget),
          providerId = view.$element.data("providerid"),
          name = view.$el.find("input[name='name']").val();
        
        // Validate form.
        if (!providerId || !name) {
          return;
        }
        
        // Create.
        svnadmin.service.createGroup(providerId, name).done(function (resp) {
          view.$el.find("#groupaddmodal").modal("hide");
        }).fail(function () {
          alert("Can not create group!");
        }).always(function () {
          
        });
        return ev.preventDefault();
      },
      
      "click; button.submit": function (ev) {
        var view = this;
        view.$el.find("form").submit();
      }
      
    }
    
  });

}(jQuery));