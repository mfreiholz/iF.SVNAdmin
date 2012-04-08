---
layout: default
title: Welcome
---

The iF.SVNAdmin application is a web based GUI to your 
[Subversion authorization file.](http://svnbook.red-bean.com/en/1.1/ch06s04.html#svn-ch-6-sect-4.4.2) 
It is based on [PHP 5.3](http://www.php.net/) and requires a web server (Apache) 
to be installed. The application doesn’t need a database back end or anything 
similar, it completely based on the Subversion authorization- and user 
authentication file. (Inludes LDAP support for users and groups)

Features
--------
* User management
	* Allow users to change their password
* Group management
* Access-Path management
* Repository management (Create, Delete)
* LDAP support (users and groups)
* DIGEST authentication support
* Repository browsing for easier Access-Path creation
* ACL's with pre-defined roles for Web-UI
* Multi-language user interface
