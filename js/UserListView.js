(function (jQ) {
  "use strict";
  var _providerId = "";

  function showUsers(providerId, offset, num) {
    if (!providerId) {
      return;
    }
    if (!offset) {
      offset = 0;
    }
    if (!num) {
      num = 10;
    }
    _providerId = providerId;
    jQ(".UserListViewProviders li").removeClass("active");

    return svnadmin.service.getUsers(providerId, offset, num).done(function (data) {
      jQ(".UserListViewProviders li[data-id=" + providerId +"]").addClass("active");
      jQ(".user-table-wrapper").html(jQ("#tmpl-UserListViewUserTable").render({
        response: data,
        providerId: providerId,
        providerEditable: data.editable,
        offset: offset,
        num: num
      }));
    })
    .fail(function () {
      alert("FAIL: Can not fetch users.");
    });
  }

  /**
   * Register View
   */
  brite.registerView("UserListView", {}, {

    create: function (config, data) {
      return jQ("#tmpl-UserListView").render();
    },

    postDisplay: function (config, data) {
      // Load user providers and the users of the first provider.
      svnadmin.service.getUserProviders().done(function (data) {
        var html = jQ("#tmpl-UserListViewProviders").render({ providers: data, current: data[0].id });
        jQ(".provider-selection-wrapper").html(html);
        showUsers(data[0].id);
      });
    },

    events: {
      "click; .provider-link": function (ev) {
        var view = this,
          element = jQ(ev.currentTarget),
          providerId = element.data("id");
        showUsers(providerId);
      },
      "click; a.user-link": function (ev) {
        var view = this,
          element = jQ(ev.currentTarget),
          userId = element.data("id");
        svnadmin.app.showUserInfoView(_providerId, userId);
      },
      "click; button.submituser": function (ev) {
        var view = this,
          element = jQ(ev.currentTarget),
          name = view.$el.find("input[name=username]").val(),
          password = view.$el.find("input[name=password]").val(),
          password2 = view.$el.find("input[name=password2]").val();
        if (password === "") {
          alert("Password is empty!");
          return;
        }
        if (password !== password2) {
          alert("Passwords doesn't match!");
          return;
        }
        svnadmin.service.createUser(_providerId, name, password).done(function (data) {
          jQ("#useraddmodal").modal("hide");
          showUsers(_providerId);
        })
        .fail(function () {
          alert("Error: Can not add user.");
        });
      },
      "click; button.deleteuser": function (ev) {
        var view = this,
          checkedElements = jQ("input[name=user-selection]:checked"),
          defs = [];
        for (var i = 0; i < checkedElements.length; ++i) {
          var elem = jQ(checkedElements[i]);
          var providerId = elem.data("providerid");
          var userId = elem.data("userid");
          defs.push(svnadmin.service.deleteUser(providerId, userId));
        }
        jQ.when(defs).done(function () {
          showUsers(_providerId);
        })
        .fail(function () {
          alert("ERROR");
        });
      },
      "click; li:not(.disabled) a.previous-page": function (ev) {
        var view = this,
          ele = jQ(ev.currentTarget),
          offset = ele.data("offset"),
          num = ele.data("num");
        showUsers(_providerId, offset, num);
        return ev.preventDefault();
      },
      "click; li:not(.disabled) a.next-page:not(.disabled)": function (ev) {
        var view = this,
          ele = jQ(ev.currentTarget),
          offset = ele.data("offset"),
          num = ele.data("num");
        showUsers(_providerId, offset, num);
        return ev.preventDefault();
      }
    }

  });

}(jQuery));
