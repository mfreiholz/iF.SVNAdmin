(function (jQ) {
	'use strict';
	brite.registerView('UserChangePasswordView', {}, {

		create: function (data, config) {
			var view = this;
			view.pid = data.providerId;
			view.id = data.id;
			view.onSubmit = data.onSubmit;
			view.onCancel = data.onCancel;
			view.submitted = false;
			return jQ('#tmpl-UserChangePasswordView').render({pid: view.pid, id: view.id});
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
					password = view.$el.find('input[name="password"]').val(),
					password2 = view.$el.find('input[name="password2"]').val();
				view.submitted = true;
				ev.preventDefault();

				if (!password || !password2) {
					return;
				} else if (password !== password2) {
					alert(tr('Passwords doesn\'t match.'));
					return;
				}

				svnadmin.app.showWithLoading(function () {
					return svnadmin.service.changePassword(view.pid, view.id, password)
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