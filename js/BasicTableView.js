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

      "click; .single-action": function (ev) {
        var view = this,
          ele = jQ(ev.currentTarget),
          actionId = ele.data("actionid"),
          rowId = ele.closest("tr").data("rowid"),
          prom = view.invokeSingleAction(actionId, rowId);
        return ev.preventDefault();
      },

      "click; .multi-action": function (ev) {
        var view = this,
          ele = jQ(ev.currentTarget),
          actionId = ele.data("actionid"),
          prom = view.invokeMultiAction(actionId);
        return ev.preventDefault();
      }

    },

    ///////////////////////////////////////////////////////////////////
    // Custom properties
    // The "options" object defines the public API of the table logic.
    ///////////////////////////////////////////////////////////////////

    options: {
      showSearch: false,
      showPaging: false,
      showRowNumber: false,
      pageSize: 10,

      singleActions: [
        {
          id: "",
          getName: function (id) { return ""; },
          getLink: function (id) { return ""; },
          onActivated: function (id) { return null; }
        }
      ],

      multiActions: [
        {
          id: "",
          name: "",
          onActivated: function (ids) { return null; }
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
          hasMore: true,
          rows: [
            { id: 0, cells: ["Value " + (1 + offset), "Value " + (2 + offset), "Value " + (3 + offset)] },
            { id: 1, cells: ["Value " + (4 + offset), "Value " + (5 + offset), "Value " + (6 + offset)] },
            { id: 2, cells: ["Value " + (7 + offset), "Value " + (8 + offset), "Value " + (9 + offset)] }
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
      }
    },

    loadMoreRows: function (offset) {
      var view = this,
        prom = null;
      if (typeof view.options.loadMore === "function") {
        prom = view.options.loadMore(offset, view.options.pageSize).done(function (resp) {
          view.renderRows(resp, offset);
          view.renderPager(offset, resp.hasMore);
          view.renderMultiActions();
        });
      }
      return null;
    },

    loadMoreResults: function (query, offset) {
      var view = this,
        prom = null;
      if (typeof view.options.search === "function") {
        prom = view.options.search(query, offset, -1).done(function (resp) {
          view.renderRows(resp, offset);
          view.renderPager(0, false);
          view.renderMultiActions();
        });
      }
      return prom;
    },

    invokeSingleAction: function (actionId, rowId) {
      var view = this,
        i = 0;
      for (i = 0; i < view.options.singleActions.length; ++i) {
        if (view.options.singleActions[i].id === actionId) {
          if (typeof view.options.singleActions[i].callback === "function") {
            return view.options.singleActions[i].callback(rowId);
          }
        }
      }
      return null;
    },

    invokeMultiAction: function (actionId) {
      var view = this,
        ids = [],
        i = 0;
      view.$el.find("input[type=checkbox]:checked").each(function () {
        ids.push(jQ(this).val());
      });
      for (i = 0; i < view.options.multiActions.length; ++i) {
        if (view.options.multiActions[i].id === actionId) {
          if (typeof view.options.multiActions[i].callback === "function") {
            return view.options.multiActions[i].callback(ids);
          }
        }
      }
      return null;
    },

    renderRows: function (data, offset) {
      var view = this,
        html = jQ("#tmpl-BasicTableView-Rows").render({
          options: view.options,
          data: data,
          offset: offset
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

    renderMultiActions: function () {
      var view = this,
        html = jQ("#tmpl-BasicTableView-MultiActions").render({
          options: view.options
        });
      view.$el.find(".multi-actions-wrapper").html(html);
    }

  });
}(jQuery));