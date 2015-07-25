(function (jQ) {
		'use strict';
		var _loadingView = null;
		var _loadingViewPromise = null;

		/**
		 * Main class to manage the GUI and all of it's requirements.
		 * @constructor
		 */
		function AppEngine() {
			this.config = {};
			this.config.tablepagesize = 10;
			this.config.showtablerownumber = true;
		}

		AppEngine.prototype.init = function () {
			brite.viewDefaultConfig.loadTmpl = true;
			brite.viewDefaultConfig.loadCss = true;
			brite.viewDefaultConfig.loadJs = true;
		};

		AppEngine.prototype.bootstrap = function () {
			var self = this;
			svnadmin.service.loginCheck().done(function () {
				self.route();
				jQ(window).on('hashchange', function () {
					self.route();
				});
			}).fail(function (jqXHR) {
				if (jqXHR.status === 401) {
					self.showLoginView();
				}
			});
		};

		AppEngine.prototype.route = function () {
			var self = this,
				url = document.location.href,
				rxUrl = new RegExp('#!(/[^/]*)(.*)', 'i'),
				match = rxUrl.exec(url),
				path = match && match.length > 1 ? match[1] : '';

			self.showMainView().done(function () {
				if (path.indexOf('/dashboard') === 0) {
					self.showDashboard();
				} else if (path.indexOf('/repositories') === 0) {
					self.showRepositoryListView(self.getParameter('providerid'));
				} else if (path.indexOf('/repositoryinfo') === 0) {
					self.showRepositoryInfoView(self.getParameter('providerid'), self.getParameter('repositoryid'));
				} else if (path.indexOf('/pathpermissions') === 0) {
					self.showPathPermissions(self.getParameter('providerid'), self.getParameter('repositoryid'), self.getParameter('path'));
				} else if (path.indexOf('/users') === 0) {
					self.showUserListView(self.getParameter('providerid'));
				} else if (path.indexOf('/userinfo') === 0) {
					self.showUserInfoView(self.getParameter('providerid'), self.getParameter('userid'));
				} else if (path.indexOf('/groups') === 0) {
					self.showGroupListView(self.getParameter('providerid'));
				} else if (path.indexOf('/groupinfo') === 0) {
					self.showGroupInfoView(self.getParameter('providerid'), self.getParameter('groupid'));
				} else {
					self.showDashboard();
				}
			});
		};

		AppEngine.prototype.getParameter = function (name, defaultValue) {
			var url = window.location.href;
			var pos = -1;
			var val = '',
				v = '';
			if ((pos = url.indexOf(name + '=')) !== -1) {
				var pos2 = url.indexOf('#', pos);
				if (pos2 === -1) {
					pos2 = url.indexOf('&', pos);
				}
				if (pos2 === -1) {
					v = url.substr(pos + name.length + 1);
				} else {
					v = url.substr(pos + name.length + 1, pos2 - (pos + name.length + 1));
				}
				if (v !== '') {
					val = v;
				}
			}
			if (typeof v !== 'undefined' && v !== '') {
				return decodeURIComponent(v);
			}
			return defaultValue;
		};

		AppEngine.prototype.createUrlParameterString = function (obj) {
			var buff = '',
				k = '';
			for (k in obj) {
				if (obj.hasOwnProperty(k)) {
					if (buff !== '') {
						buff += '&';
					}
					buff += k + '=' + encodeURIComponent(obj[k]);
				}
			}
			return buff;
		};

		AppEngine.prototype.translatePermission = function (perm) {
			if (perm === '') {
				return tr('No Access');
			} else if (perm === 'r') {
				return tr('Read access');
			} else if (perm === 'rw') {
				return tr('Read-/Write access');
			}
			return perm;
		};

		AppEngine.prototype.logout = function () {
			svnadmin.service.logout().always(function () {
				window.location.reload();
			});
		};

		AppEngine.prototype.showLoading = function () {
			if (_loadingView && _loadingViewPromise) {
				return _loadingViewPromise;
			}
			_loadingViewPromise = brite.display('LoadingView', 'body')
				.done(function (view) {
					_loadingView = view;
				});
			return _loadingViewPromise;
		};

		AppEngine.prototype.hideLoading = function () {
			if (_loadingView) {
				_loadingView.$el.bRemove();
				_loadingView = null;
				_loadingViewPromise = null;
			}
		};

		AppEngine.prototype.showWithLoading = function (func) {
			var self = this,
				def = new jQ.Deferred();
			self.showLoading()
				.done(function () {
					func()
						.done(function () {
							def.resolve();
						})
						.fail(function () {
							def.reject();
						})
						.always(function () {
							self.hideLoading();
						});
				});
			return def.promise();
		};

		AppEngine.prototype.showLoginView = function () {
			return brite.display('LoginView', '.AppContent', {emptyParent: true});
		};

		AppEngine.prototype.showMainView = function () {
			return brite.display('MainView', '.AppContent', {}, {emptyParent: true});
		};

		AppEngine.prototype.showDashboard = function () {
			return this.showWithLoading(function () {
				return brite.display('DashboardView', '#page-wrapper', {}, {emptyParent: true});
			});
		};

		AppEngine.prototype.showUserListView = function (providerId) {
			return this.showWithLoading(function () {
				return brite.display('UserListView', '#page-wrapper', {providerId: providerId}, {emptyParent: true});
			});
		};

		AppEngine.prototype.showUserInfoView = function (providerId, userId) {
			return brite.display('UserInfoView', '#page-wrapper', {
				providerId: providerId,
				userId: userId
			}, {emptyParent: true});
		};

		AppEngine.prototype.showUserChangePasswordView = function (providerId, userId) {
			return brite.display('UserChangePasswordView', 'body', {
				providerId: providerId,
				userId: userId
			}, {emptyParent: false});
		};

		AppEngine.prototype.showGroupListView = function (providerId) {
			return brite.display('GroupListView', '#page-wrapper', {providerId: providerId}, {emptyParent: true});
		};

		AppEngine.prototype.showGroupInfoView = function (providerId, groupId) {
			return brite.display('GroupInfoView', '#page-wrapper', {
				providerId: providerId,
				groupId: groupId
			}, {emptyParent: true});
		};

		AppEngine.prototype.showRepositoryListView = function (providerId) {
			return this.showWithLoading(function () {
				return brite.display('RepositoryListView', '#page-wrapper', {providerId: providerId}, {emptyParent: true});
			});
		};

		AppEngine.prototype.showRepositoryInfoView = function (providerId, repositoryId) {
			return brite.display('RepositoryInfoView', '#page-wrapper', {
				providerId: providerId,
				repositoryId: repositoryId
			}, {emptyParent: true});
		};

		AppEngine.prototype.showPathPermissions = function (providerId, repositoryId, path) {
			return brite.display('PathPermissionsView', '#page-wrapper', {
				providerId: providerId,
				repositoryId: repositoryId,
				path: path
			}, {emptyParent: true});
		};

		AppEngine.prototype.showUserSearchDialog = function (showPermissions, onSubmittedCallback) {
			brite.display(
				'BasicSearchDialogView',
				'body',
				{
					showPermissionSelection: showPermissions,
					onSearchMore: function (query, offset, limit) {
						var def = new jQuery.Deferred();
						svnadmin.service.searchUsers('', query, offset, limit)
							.done(function (data) {
								var res = {}, i = 0;
								res.hasMore = false;
								res.rows = [];
								for (i = 0; i < data.list.items.length; ++i) {
									var row = {};
									row.id = data.list.items[i].id;
									row.object = data.list.items[i];
									res.rows.push(row);
								}
								def.resolve(res);
							})
							.fail(function () {
								def.reject();
							});
						return def.promise();
					},
					onSubmitted: onSubmittedCallback
				},
				{emptyParent: false}
			);
		};

		AppEngine.prototype.showGroupSearchDialog = function (showPermissions, onSubmitCallback) {
			brite.display(
				'BasicSearchDialogView',
				'body',
				{
					showPermissionSelection: showPermissions,
					onSearchMore: function (query, offset, limit) {
						var def = new jQuery.Deferred();
						svnadmin.service.searchGroups('', query, offset, limit)
							.done(function (data) {
								var res = {}, i = 0;
								res.hasMore = false;
								res.rows = [];
								for (i = 0; i < data.list.items.length; ++i) {
									var row = {};
									row.id = data.list.items[i].id;
									row.object = data.list.items[i];
									res.rows.push(row);
								}
								def.resolve(res);
							})
							.fail(function () {
								def.reject();
							});
						return def.promise();
					},
					onSubmitted: onSubmitCallback
				},
				{emptyParent: false}
			);
		};

		window.svnadmin = window.svnadmin || {};
		window.svnadmin.app = window.svnadmin.app || new AppEngine();
	}(jQuery)
)
;


(function (jQ) {
	'use strict';
	/**
	 Remote web service client class.
	 Provides access to all REST services.
	 **/
	function ServiceClient() {
	}

	ServiceClient.prototype.ajax = function (settings) {
		return jQ.ajax(settings);
	};

	// Authentication

	ServiceClient.prototype.loginCheck = function () {
		return this.ajax({
			url: 'service/',
			data: {
				m: 'LoginService',
				action: 'check'
			}
		});
	};

	ServiceClient.prototype.login = function (username, password) {
		return this.ajax({
			url: 'service/',
			type: 'POST',
			data: {
				m: 'LoginService',
				action: 'login',
				username: username,
				password: password
			}
		});
	};

	ServiceClient.prototype.logout = function () {
		return this.ajax({
			url: 'service/',
			data: {
				m: 'LoginService',
				action: 'logout'
			}
		});
	};

	// Common

	ServiceClient.prototype.getSystemInfo = function () {
		return this.ajax({
			url: 'service/',
			data: {
				m: 'CommonService',
				action: 'systeminfo'
			}
		});
	};

	ServiceClient.prototype.getFileSystemInfo = function () {
		return this.ajax({
			url: 'service/',
			data: {
				m: 'CommonService',
				action: 'filesysteminfo'
			}
		});
	};

	// Users

	ServiceClient.prototype.getUserProviders = function () {
		return this.ajax({
			url: 'service/',
			data: {
				m: 'UserService',
				action: 'providers'
			}
		});
	};

	ServiceClient.prototype.getUsers = function (providerId, offset, num) {
		return this.ajax({
			url: 'service/',
			data: {
				m: 'UserService',
				action: 'list',
				providerid: providerId,
				offset: offset,
				num: num
			}
		});
	};

	ServiceClient.prototype.searchUsers = function (providerId, query, offset, num) {
		return this.ajax({
			url: 'service/',
			data: {
				m: 'UserService',
				action: 'search',
				providerid: providerId,
				query: query,
				offset: offset,
				num: num
			}
		});
	};

	ServiceClient.prototype.createUser = function (providerId, name, password) {
		return this.ajax({
			url: 'service/',
			type: 'POST',
			data: {
				m: 'UserService',
				action: 'create',
				providerid: providerId,
				name: name,
				password: password
			}
		});
	};

	ServiceClient.prototype.deleteUser = function (providerId, userId) {
		return this.ajax({
			url: 'service/',
			data: {
				m: 'UserService',
				action: 'delete',
				providerid: providerId,
				userid: userId
			}
		});
	};

	ServiceClient.prototype.changePassword = function (providerId, userId, password) {
		return this.ajax({
			url: 'service/',
			type: 'POST',
			data: {
				m: 'UserService',
				action: 'changepassword',
				providerid: providerId,
				userid: userId,
				password: password
			}
		});
	};

	// Groups

	ServiceClient.prototype.getGroupProviders = function () {
		return this.ajax({
			url: 'service/',
			data: {
				m: 'GroupService',
				action: 'providers'
			}
		});
	};

	ServiceClient.prototype.getGroups = function (providerId, offset, num) {
		return this.ajax({
			url: 'service/',
			data: {
				m: 'GroupService',
				action: 'list',
				providerid: providerId,
				offset: offset,
				num: num
			}
		});
	};

	ServiceClient.prototype.searchGroups = function (providerId, query, offset, num) {
		return this.ajax({
			url: 'service/',
			data: {
				m: 'GroupService',
				action: 'search',
				providerid: providerId,
				query: query,
				offset: offset,
				num: num
			}
		});
	};

	ServiceClient.prototype.createGroup = function (providerId, name) {
		return this.ajax({
			url: 'service/',
			data: {
				m: 'GroupService',
				action: 'create',
				providerid: providerId,
				name: name
			}
		});
	};

	ServiceClient.prototype.deleteGroup = function (providerId, groupId) {
		return this.ajax({
			url: 'service/',
			data: {
				m: 'GroupService',
				action: 'delete',
				providerid: providerId,
				groupid: groupId
			}
		});
	};

	// Group <-> Member association

	ServiceClient.prototype.getMembersOfGroup = function (providerId, groupId, offset, num) {
		return this.ajax({
			url: 'service/',
			data: {
				m: 'GroupService',
				action: 'members',
				providerid: providerId,
				groupid: groupId,
				offset: offset,
				num: num
			}
		});
	};

	ServiceClient.prototype.getGroupsOfMember = function (providerId, memberId, offset, num) {
		return this.ajax({
			url: 'service/',
			data: {
				m: 'GroupService',
				action: 'membergroups',
				providerid: providerId,
				memberid: memberId,
				offset: offset,
				num: num
			}
		});
	};

	ServiceClient.prototype.groupMemberAssign = function (providerId, groupId, memberId) {
		return this.ajax({
			url: 'service/',
			data: {
				m: 'GroupService',
				action: 'memberassign',
				providerid: providerId,
				groupid: groupId,
				memberid: memberId
			}
		});
	};

	ServiceClient.prototype.groupMemberUnassign = function (providerId, groupId, memberId) {
		return this.ajax({
			url: 'service/',
			data: {
				m: 'GroupService',
				action: 'memberunassign',
				providerid: providerId,
				groupid: groupId,
				memberid: memberId
			}
		});
	};

	// Repositories

	ServiceClient.prototype.getRepositoryProviders = function () {
		return this.ajax({
			url: 'service/',
			data: {
				m: 'RepositoryService',
				action: 'providers'
			}
		});
	};

	ServiceClient.prototype.getRepositories = function (providerId, offset, num) {
		return this.ajax({
			url: 'service/',
			data: {
				m: 'RepositoryService',
				action: 'list',
				providerid: providerId,
				offset: offset,
				num: num
			}
		});
	};

	ServiceClient.prototype.createRepository = function (providerId, name) {
		return this.ajax({
			url: 'service/?m=RepositoryService&action=create',
			method: 'POST',
			contentType: 'application/json',
			data: JSON.stringify({
				providerid: providerId,
				name: name
			})
		});
	};

	ServiceClient.prototype.deleteRepository = function (providerId, id) {
		return this.ajax({
			url: 'service/?m=RepositoryService&action=delete',
			method: 'DELETE',
			contentType: 'application/json',
			data: JSON.stringify({
				providerid: providerId,
				id: id
			})
		});
	};

	ServiceClient.prototype.browseRepository = function () {
	};

	ServiceClient.prototype.getRepositoryInfo = function (providerId, repositoryId) {
		return this.ajax({
			url: 'service/',
			data: {
				m: 'RepositoryService',
				action: 'info',
				providerid: providerId,
				repositoryid: repositoryId
			}
		});
	};

	ServiceClient.prototype.getRepositoryPaths = function (providerId, repositoryId) {
		return this.ajax({
			url: 'service/',
			data: {
				m: 'RepositoryService',
				action: 'paths',
				providerid: providerId,
				repositoryid: repositoryId
			}
		});
	};

	ServiceClient.prototype.getRepositoryPathPermissions = function (providerId, repositoryId, path) {
		return this.ajax({
			url: 'service/',
			data: {
				m: 'RepositoryService',
				action: 'permissions',
				providerid: providerId,
				repositoryid: repositoryId,
				path: path
			}
		});
	};

	ServiceClient.prototype.createRepositoryPath = function (providerId, repositoryId, path) {
		return this.ajax({
			url: 'service/',
			data: {
				m: 'RepositoryService',
				action: 'addpath',
				providerid: providerId,
				repositoryid: repositoryId,
				path: path
			}
		});
	};

	ServiceClient.prototype.deleteRepositoryPath = function (providerId, repositoryId, path) {
		return this.ajax({
			url: 'service/',
			data: {
				m: 'RepositoryService',
				action: 'deletepath',
				providerid: providerId,
				repositoryid: repositoryId,
				path: path
			}
		});
	};

	ServiceClient.prototype.assignRepositoryPath = function (providerId, repositoryId, path, memberId, permission) {
		return this.ajax({
			url: 'service/',
			data: {
				m: 'RepositoryService',
				action: 'assignpath',
				providerid: providerId,
				repositoryid: repositoryId,
				path: path,
				memberid: memberId,
				permission: permission
			}
		});
	};

	ServiceClient.prototype.unassignRepositoryPath = function (providerId, repositoryId, path, memberId) {
		return this.ajax({
			url: 'service/',
			data: {
				m: 'RepositoryService',
				action: 'unassignpath',
				providerid: providerId,
				repositoryid: repositoryId,
				path: path,
				memberid: memberId
			}
		});
	};

	window.svnadmin = window.svnadmin || {};
	window.svnadmin.service = window.svnadmin.service || new ServiceClient();
}(jQuery));

/**
 * Global Helper Functions
 */
function tr(str) {
	'use strict';
	return str;
}


/**
 * Template Helper Functions
 */
$.views.helpers({
	tr: function (str) {
		'use strict';
		return str;
	},
	formatSize: function (bytes) {
		'use strict';
		var kb = bytes / 1024,
			mb = kb / 1024,
			gb = mb / 1024,
			tb = gb / 1024;
		return Math.round(gb * 100) / 100 + ' GB';
	}
});


/**
 Main entry point for scripts.
 **/
jQuery(document).ready(function () {
	'use strict';
	jQuery.noConflict();
	svnadmin.app.init();
	svnadmin.app.bootstrap();
});
