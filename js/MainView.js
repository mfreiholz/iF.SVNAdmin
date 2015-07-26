(function (jQ) {
	'use strict';
	brite.registerView('MainView', {emptyParent: true}, {

		create: function (config, data) {
			console.log('NEW MAIN VIEW');
			return jQ('#tmpl-MainView').render();
		},

		postDisplay: function (config, data) {
			var view = this;
			view._updateActiveLink();
		},

		events: {
			'click; .navbar-toggle': function (ev) {
				var view = this;
				jQ('.sidebar-collapse').collapse('toggle');
			}
		},

		winEvents: {
			/*'hashchange': function (ev) {
				var view = this;
				view._updateActiveLink();
			}*/
		},

		_updateActiveLink: function () {
			var view = this,
				url = document.location.href,
				rxUrl = new RegExp('#!(/[^/?]*)(.*)', 'i'),
				match = rxUrl.exec(url),
				path = match && match.length > 1 ? match[1] : '',
				link = path ? '#!' + path : '#!/dashboard';
			view.$el.find('ul.nav li').removeClass('active');
			view.$el.find('ul.nav li a[href*="' + link + '"]').closest('li').addClass('active');
		}

	});
}(jQuery));