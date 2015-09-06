(function (jQ) {
	'use strict';
	brite.registerView('RepositoryListView', {}, {

		create: function (data, config) {
			var view = this,
				def = new jQ.Deferred();
			view.prov = null;

			async.series([
					// Load all available providers.
					function (callback) {
						svnadmin.service.getRepositoryProviders()
							.done(function (res) {
								view.prov = elws.getProviderFromListById(res, data.providerId || res[0].id);
								callback(null, res);
							})
							.fail(function (err) {
								callback(err, null);
							});
					},
					// Load list of available repositories.
					// Provider might be preselected by "data.providerId".
					function (callback) {
						svnadmin.service.getRepositories(view.prov.id, 0, -1)
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
					var html = jQ('#tmpl-RepositoryListView').render({
						provider: view.prov,
						providers: results[0],
						repositories: results[1].items
					});
					def.resolve(html);
				});

			return def.promise();
		},

		events: {
			'click; .NewRepositoryLink': function (ev) {
				var view = this;
				ev.preventDefault();
				brite.display('RepositoryAddView', 'body', {
					providerId: view.prov.id,
					onSubmit: function () {
						svnadmin.app.showRepositoryListView(view.prov.id);
					}
				}, {emptyParent: false});
			},
			'click; .DeleteRepositoryLink': function (ev) {
				var view = this,
					id = jQ(ev.currentTarget).data('id');
				ev.preventDefault();
				if (!window.confirm(tr('Are you sure?')))
					return;
				svnadmin.app.showWithLoading(function () {
					return svnadmin.service.deleteRepository(view.prov.id, id)
						.done(function () {
							svnadmin.app.showRepositoryListView(view.prov.id);
						})
						.fail(function () {
							console.log('TODO HANDLE ERROR');
						});
				});
			}
		}

	});
}(jQuery));