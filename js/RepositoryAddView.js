(function (jQ) {
	'use strict';
	brite.registerView('RepositoryAddView', {}, {

		create: function (data, config) {
			var view = this;
			view.pid = data.providerId;
			view.onSubmit = data.onSubmit;
			view.onCancel = data.onCancel;
			view.submitted = false;
			return jQ('#tmpl-RepositoryAddView').render();
		},

		postDisplay: function (data, config) {
			var view = this;
			view.$el.find('#dialog').modal('show');
		},

		events: {
			'hidden.bs.modal; #dialog': function (ev) {
				var view = this;
				view.$el.bRemove();
				if (view.submitted && typeof view.onSubmit === 'function') {
					view.onSubmit();
				} else if (!view.submitted && typeof view.onCancel === 'function') {
					view.onCancel();
				}
			},
			'submit; form': function (ev) {
				var view = this,
					name = view.$el.find('input[name="name"]').val();
				view.submitted = true;
				ev.preventDefault();

				if (!name)
					return;

				svnadmin.app.showWithLoading(function () {
					return svnadmin.service.createRepository(view.pid, name)
						.done(function (resp) {
							view.$el.find('#dialog').modal('hide');
						}).fail(function () {
							alert('Can not create repository!');
						});
				});
			},
			'click; button.submit': function (ev) {
				var view = this;
				view.$el.find('form').submit();
			}
		}

	});
}(jQuery));