(function (jQ) {
	'use strict';
	brite.registerView('UserListView', {}, {

		create: function (data, config) {
			var view = this,
				def = new jQ.Deferred();
			view.prov = null;

			async.series([
					// Load all available providers.
					function (callback) {
						svnadmin.service.getUserProviders()
							.done(function (res) {
								view.prov = view._getProviderFromListById(res, data.providerId || res[0].id);
								callback(null, res);
							})
							.fail(function (err) {
								callback(err, null);
							});
					},
					// Load list of available users.
					function (callback) {
						svnadmin.service.getUsers(view.prov.id, 0, -1)
							.done(function (res) {
								callback(null, res);
							})
							.fail(function (err) {
								callback(err, null);
							});
					}
				],
				// Render content now.
				function (err, results) {
					if (err) {
						def.reject('TODO HANDLE ERROR');
						return;
					}
					var html = jQ('#tmpl-UserListView').render({
						provider: view.prov,
						providers: results[0],
						users: results[1].items
					});
					def.resolve(html);
				});

			return def.promise();
		},

		events: {
			'click; .NewUserLink': function (ev) {
				var view = this;
				ev.preventDefault();
				brite.display('UserAddView', 'body', {
					providerId: view.prov.id,
					onSubmit: function () {
						svnadmin.app.showUserListView(view.prov.id);
					}
				}, {emptyParent: false});
			},
			'click; .ChangePasswordLink': function (ev) {
				var view = this;
				ev.preventDefault();
				brite.display('UserChangePasswordView', 'body', {
					providerId: view.prov.id,
					id: jQ(ev.currentTarget).data('id')
				}, {emptyParent: false});
			},
			'click; .DeleteUserLink': function (ev) {
				var view = this,
					id = jQ(ev.currentTarget).data('id');
				ev.preventDefault();
				if (!window.confirm(tr('Are you sure?')))
					return;
				svnadmin.app.showWithLoading(function () {
					return svnadmin.service.deleteUser(view.prov.id, id)
						.done(function () {
							svnadmin.app.showUserListView(view.prov.id);
						})
						.fail(function () {
							console.log('TODO HANDLE ERROR');
						});
				});
			}
		},

		_getProviderFromListById: function (list, id) {
			for (var i = 0; i < list.length; ++i) {
				if (list[i].id === id)
					return list[i];
			}
			return null;
		}

	});
}(jQuery));