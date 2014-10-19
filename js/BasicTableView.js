(function (jQ) {
  "use strict";
  brite.registerView("BasicTableView", {}, {

    create: function (data, config) {
      var view = this;
      jQ.extend(view.options, data.options);
      return jQ("#tmpl-BasicTableView").render({
        options: view.options
      });
    },

    postDisplay: function (data, config) {
      var view = this;
      return view.loadMoreRows(0);
    },

    events: {

      "keypress; .search-query": function (ev) {
        var view = this,
          ele = jQ(ev.currentTarget),
          query = ele.val();
        if (ev.keyCode !== 13) {
          return;
        } else if (query.length === 0) {
          view.loadMoreRows(0);
        }
        view.loadMoreResults(query, 0);
      },

      "click; li:not(.disabled) a.previous-page": function (ev) {
        var view = this,
          ele = jQ(ev.currentTarget),
          offset = ele.data("offset");
        view.loadMoreRows(offset);
        return ev.preventDefault();
      },

      "click; li:not(.disabled) a.next-page:not(.disabled)": function (ev) {
        var view = this,
          ele = jQ(ev.currentTarget),
          offset = ele.data("offset");
        view.loadMoreRows(offset);
        return ev.preventDefault();
      },

      "click; .action": function (ev) {
        var view = this,
          ele = jQ(ev.currentTarget),
          actionId = ele.data("actionid"),
          prom = view.invokeAction(actionId);
        return ev.preventDefault();
      }

    },

    ///////////////////////////////////////////////////////////////////
    // Custom properties
    ///////////////////////////////////////////////////////////////////

    options: {
      showSearch: false,
      showPaging: false,
      multiSelection: false,
      pageSize: 10,

      actions: [
        {
          id: "clear",
          name: "Clear",
          onActivated: function (ids) {}
        },
        {
          id: "delete",
          name: "Delete",
          onActivated: function (ids) {}
        }
      ],

      columns: [
        { id: "", name: "Column 1" },
        { id: "", name: "Column 2" },
        { id: "", name: "Column 3" }
      ],

      loadMore: function (offset, num) {
        var def = new jQ.Deferred();
        def.resolve({
          hasMore: false,
          rows: [
            { id: 0, cells: ["Value 1", "Value 2", "Value 3"] },
            { id: 1, cells: ["Value 4", "Value 5", "Value 6"] },
            { id: 2, cells: ["Value 7", "Value 8", "Value 9"] }
          ]
        });
        return def.promise();
      },

      search: function (query, offset, num) {
        var def = new jQ.Deferred();
        def.resolve({
          hasMore: false,
          rows: []
        });
        return def.promise();
      },
    },

    loadMoreRows: function (offset) {
      var view = this;
      if (typeof view.options.loadMore === "function") {
        return view.options.loadMore(offset, view.options.pageSize).done(function (resp) {
          view.renderRows(resp);
          view.renderPager(offset, resp.hasMore);
          view.renderActions();
        });
      }
      return null;
    },

    loadMoreResults: function (query, offset) {
      var view = this;
      if (typeof view.options.search === "function") {
        return view.options.search(query, offset, -1).done(function (resp) {
          view.renderRows(resp);
          view.renderPager(0, false);
          view.renderActions();
        });
      }
    },

    invokeAction: function (actionId) {
      var view = this,
        ids = [],
        i = 0;
      if (view.options.multiSelection) {
        view.$el.find("input[type=checkbox]:checked").each(function () {
          ids.push(jQ(this).val());
        });
      }
      for (i = 0; i < view.options.actions.length; ++i) {
        if (view.options.actions[i].id === actionId) {
          if (typeof view.options.actions[i].callback === "function") {
            return view.options.actions[i].callback(ids);
          }
        }
      }
      return null;
    },

    renderRows: function (data) {
      var view = this,
        html = jQ("#tmpl-BasicTableView-Rows").render({
          options: view.options,
          data: data
        });
      view.$el.find("table tbody").html(html);
    },

    renderPager: function (offset, hasMore) {
      var view = this,
        html = jQ("#tmpl-BasicTableView-Pager").render({
          options: view.options,
          offset: offset,
          hasMore: hasMore
        });
      view.$el.find(".pager-wrapper").html(html);
    },

    renderActions: function () {
      var view = this,
        html = jQ("#tmpl-BasicTableView-Actions").render({
          options: view.options
        });
      view.$el.find(".actions-wrapper").html(html);
    }

  });
}(jQuery));