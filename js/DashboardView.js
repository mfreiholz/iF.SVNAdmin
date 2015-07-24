(function (jQ) {
	'use strict';

	brite.registerView('DashboardView', {}, {

		create: function (config, data) {
			var view = this,
				def = new jQ.Deferred(),
				promises = [];

			promises.push(svnadmin.service.getSystemInfo());
			promises.push(svnadmin.service.getFileSystemInfo());

			jQ.when.apply(this, promises)
				.done(function (res1, res2) {
					var systemInfo = res1[0],
						fileSystemInfo = res2[0],
						html = jQ('#tmpl-DashboardView').render({
							systemInfoHtml: jQ('#tmpl-SystemInfoPanelView').render(systemInfo),
							fileSystemInfoHtml: jQ('#tmpl-FileSystemInfoPanelView').render(fileSystemInfo)
						});
					def.resolve(html);
				})
				.fail(function () {
					def.reject();
				});

			return def.promise();
		}

	});

}(jQuery));