(function (jQ) {
	'use strict';
	brite.registerView('RepositoryPermissionsView', {}, {

		create: function (data, config) {
			var view = this,
				def = new jQ.Deferred();
			view.pid = data.providerId;
			view.rid = data.repositoryId;

			// Load all Paths of Repository.
			async.series([
					function (callback) {
						svnadmin.service.getRepositoryPaths(view.pid, view.rid)
							.done(function (res) {
								callback(null, res);
							})
							.fail(function (res) {
								callback(res, null);
							});
					}
				],
				function (err, results) {
					if (err) {
						def.reject('TODO HANDLE ERROR');
						return;
					}
					def.resolve(jQ('#tmpl-RepositoryPermissionsView').render({
						paths: results[0]
					}));
				});

			return def.promise();
		}

	});
}(jQuery));