iF.SVNAdmin
===========
The iF.SVNAdmin application is a web based GUI to your Subversion authorization 
file. It is based on PHP 5.3 and requires a web server (Apache) to be installed. 
The application doesn’t need a database back end or anything similar, it 
completely based on the Subversion authorization- and user authentication file. 
(+Inludes LDAP support for users and groups)


Installation (Version 1.4.0 - 1.6.0)
------------------------------------

### Extract & Prepare

Extract the ZIP archive and copy the complete `/svnadmin/` folder to your web folder. You can also copy the contents of the folder to your web servers (VirtualHost) root directory.

### File Permissions

The application requires full-access to its own `/data/` folder.

`$> chmod -R 777 /var/www/svnadmin/data`

To manage the Subversion authorization file it is also required to set write permissions on the SVNAuthFile. (make a backup!)

### Configure

After the above steps you can reach the application by typing `http://www.<yourhost>.com/svnadmin/` into the address bar of your browser and configure it.


Updating from older version to 1.4.x
------------------------------------
Easy to say: “It is not possible.”

The 1.4 comes with a new structure to make future updates easy. All configurations are now saved in the new `data/config.ini` file, which is created on the first start up. We recommend to save your current `include/config.inc.php` to simply copy-paste your configuration into the new setup page.


Updating from >= 1.4.x to 1.6.x
-------------------------------
No tests until now...
I recommend to do clean installation, because the complete templating system has been changed and a lot of files has been moved.

__Note:__ You can still use your old `/data/` directory, which contains the configuration of the application.


Additional configuration
========================

### Show Repository delete option

```
[GUI]
RepositoryDeleteEnabled=true
```

FAQ
===
Q: I get a "Maximum execution time exceeded" error, if i try to update/synchronize my data.
A: http://www.php.net/manual/de/info.configuration.php#ini.max-execution-time


Who is responsible for this crap?
---------------------------------
&copy; 2009-2012 Manuel Freiholz, [insaneFactory.com](http://www.insanefactory.com/if-svnadmin/)