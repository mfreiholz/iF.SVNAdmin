(function () {
  "use strict";
  brite.registerView("BasicSearchDialogView", {}, {

    create: function (data, config) {
      var view = this;
      view.options = {
        showPermissionSelection: false,
        onSearchMore: function (query, offset, limit) {
          /*var def = new jQuery.Deferred();
          def.resolve({
            hasMore: false,
            rows: [
              { id: 0 },
              { id: 1 }
            ]
          });*/
        },
        onSubmitted: function () {}
      };
      jQuery.extend(view.options, data);
      return jQuery("#tmpl-BasicSearchDialogView").render(view.options);
    },

    postDisplay: function (data, config) {
      var view = this;
      view.$el.find("#basicsearchmodal").modal({ show: true });
      view.$el.find("#basicsearchmodal").on("hidden.bs.modal", function (ev) { view.$el.bRemove(); });
    },

    events: {
      "click; .search-link": function (ev) {
        var view = this;
        view.searchMore();
      },
      "keypress; input[name='searchquery']": function (ev) {
        var view = this;
        if (ev.keyCode === 13 || ev.which === 13) {
          view.searchMore();
          ev.preventDefault();
        }
      },
      "click; .submit": function (ev) {
        var view = this;
        view.$el.find("form").submit();
      },
      "submit; form": function (ev) {
        var view = this,
          selection = view.getSelection(),
          permission = view.getPermission();
        ev.preventDefault();
        view.$el.find("#basicsearchmodal").modal("hide");
        if (view.options.showPermissionSelection) {
          view.options.onSubmitted(selection, permission);
        } else {
          view.options.onSubmitted(selection);
        }
      }
    },

    ///////////////////////////////////////////////////////////////////

    searchMore: function () {
      var view = this,
        query = view.$el.find("input[name='searchquery']").val(),
        searchResultContainer = view.$el.find(".searchresultcontainer");

      // Show loading progress.
      searchResultContainer.html("...");

      // Execute search.
      view.options.onSearchMore(query, 0, -1)
        .done(function (data) {
          var html = jQuery("#tmpl-BasicSearchDialogView-Result").render(data);
          searchResultContainer.html(html);
        })
        .fail(function () {
          alert("Error during search.");
        });
    },

    getSelection: function () {
      var view = this,
        selection = [];
      jQuery("select[name='result'] option:selected").each(function () {
        selection.push(jQuery(this).val());
      });
      return selection;
    },
    
    getPermission: function () {
      var view = this,
        perm = jQuery("select[name='permission'] option:selected").val();
      return perm;
    }

  });
}());