(function (jQ) {
	'use strict';
	brite.registerView('UserAddView', {}, {

		create: function (data, config) {
			var view = this;
			view.pid = data.providerId;
			view.onSubmit = data.onSubmit;
			view.onCancel = data.onCancel;
			view.submitted = false;
			return jQ('#tmpl-UserAddView').render();
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
					name = view.$el.find('input[name="name"]').val(),
					password = view.$el.find('input[name="password"]').val(),
					password2 = view.$el.find('input[name="password2"]').val();
				view.submitted = true;
				ev.preventDefault();

				if (!name || !password || !password2) {
					return;
				} else if (password !== password2) {
					alert(tr('Passwords doesn\'t match.'));
					return;
				}

				svnadmin.app.showWithLoading(function () {
					return svnadmin.service.createUser(view.pid, name, password)
						.done(function (resp) {
							view.$el.find('#dialog').modal('hide');
						}).fail(function () {
							alert('Can not create user!');
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