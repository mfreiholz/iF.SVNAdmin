<?php
return array (

    /**
     * Common application configuration
     */

    "common" => array (
        /**
         * Subversion
         */

        "svn_binary_path" => "\\\\DISKSTATION\\Development\\Projects\\iF.SVNAdmin\\Data\\subversion 1.7.4-1\\svn.exe",
        "svnadmin_binary_path" => "\\\\DISKSTATION\\Development\\Projects\\iF.SVNAdmin\\Data\\subversion 1.7.4-1\\svnadmin.exe",
        "svn_config_directory" => "\\\\DISKSTATION\\Development\\Projects\\iF.SVNAdmin\\Data\\svn-config-dir",

        /**
         * Functionality
         */

        "allow_repository_deletion" => false
    ),

    /**
     * Authentication
     * The authentication mechanism may use multiple Authenticators.
     */

    "authenticators" => array (
        array (
            "id" => "authstatic",
            "class_name" => "StaticAuthenticator",
            "users" => array (
                "static_admin" => "static_admin",
                "static_user" => "static_user"
            )
        ),
        array (
            "id" => "authpasswd",
            "class_name" => "PasswdAuthenticator",
            // "file" => "/media/NAS-Development/Projects/iF.SVNAdmin/Data/dav svn.passwd"
            "file" => "\\\\DISKSTATION\\Development\\Projects\\iF.SVNAdmin\\Data\\dav svn.passwd"
        ),
        array (
            "id" => "authdigest",
            "class_name" => "DigestAuthenticator",
            // "file" => "/media/NAS-Development/Projects/iF.SVNAdmin/Data/dav svn.digest.passwd",
            "file" => "\\\\DISKSTATION\\Development\\Projects\\iF.SVNAdmin\\Data\\dav svn.digest.passwd",
            "realm" => "myrealm"
        ),
        array (
            "id" => "authldap",
            "class_name" => "LdapAuthenticator",
            "host_url" => "ldap://192.168.178.24:389/",
            "protocol_version" => 3,
            "bind_dn" => "CN=ADReadUser,CN=Users,DC=insanefactory,DC=com",
            "bind_password" => "ADReadUser",
            "search_base_dn" => "OU=iFSVNAdmin,DC=insanefactory,DC=com",
            "search_filter" => "(&(objectClass=person)(objectClass=user))",
            "attribute" => "sAMAccountName"
        )
    ),

    /**
     * Data Providers
     */

    "providers" => array (

        // Users
        "user" => array (
            "passwdusers" => array (
                "class_name" => "PasswdUserProvider",
                // "file" => "/media/NAS-Development/Projects/iF.SVNAdmin/Data/dav svn.passwd"
                "file" => "\\\\DISKSTATION\\Development\\Projects\\iF.SVNAdmin\\Data\\dav svn.passwd"
            ),
            "digestusers" => array (
                "class_name" => "DigestUserProvider",
                // "file" => "/media/NAS-Development/Projects/iF.SVNAdmin/Data/dav svn.digest.passwd",
                "file" => "\\\\DISKSTATION\\Development\\Projects\\iF.SVNAdmin\\Data\\dav svn.digest.passwd",
                "realm" => "myrealm"
            ),
            "digestusers2" => array (
                "class_name" => "DigestUserProvider",
                // "file" => "/media/NAS-Development/Projects/iF.SVNAdmin/Data/dav svn.digest.passwd",
                "file" => "\\\\DISKSTATION\\Development\\Projects\\iF.SVNAdmin\\Data\\dav svn.digest.passwd",
                "realm" => "myrealm2"
            ),
            "ldapusers" => array (
                "class_name" => "LdapUserProvider",
                "host_url" => "ldap://192.168.178.24:389/",
                "protocol_version" => 3,
                "bind_dn" => "CN=ADReadUser,CN=Users,DC=insanefactory,DC=com",
                "bind_password" => "ADReadUser",
                "search_base_dn" => "OU=iFSVNAdmin,DC=insanefactory,DC=com",
                "search_filter" => "(&(objectClass=person)(objectClass=user))",
                "attributes" => array (
                    "sAMAccountName",
                    "givenName",
                    "sn"
                ),
                "display_name_format" => "%givenName %sn (%sAMAccountName)"
            )
        ),

        // Groups
        "group" => array (
            "svnauthgroups" => array (
                "class_name" => "SvnAuthGroupProvider",
                // "file" => "/media/NAS-Development/Projects/iF.SVNAdmin/Data/dav svn.authz"
                "file" => "\\\\DISKSTATION\\Development\\Projects\\iF.SVNAdmin\\Data\\dav svn.authz"
            ),
            "ldapgroups" => array (
                "class_name" => "LdapGroupProvider",
                "host_url" => "ldap://192.168.178.24:389/",
                "protocol_version" => 3,
                "bind_dn" => "CN=ADReadUser,CN=Users,DC=insanefactory,DC=com",
                "bind_password" => "ADReadUser",
                "search_base_dn" => "OU=iFSVNAdmin,DC=insanefactory,DC=com",
                "search_filter" => "(objectClass=group)",
                "attributes" => array (
                    "sAMAccountName"
                ),
                "display_name_format" => ""
            )
        ),

        // Repositories
        "repository" => array (
            "svnparentrepos" => array (
                "class_name" => "SvnParentRepositoryProvider",
                "path" => "\\\\DISKSTATION\\Development\\Projects\\iF.SVNAdmin\\Data\\my repos",
                "authfile" => "\\\\DISKSTATION\\Development\\Projects\\iF.SVNAdmin\\Data\\dav svn.authz"
            ),
            "svnparentrepos2" => array (
                "class_name" => "SvnParentRepositoryProvider",
                "path" => "\\\\DISKSTATION\\Development\\Projects\\iF.SVNAdmin\\Data\\my repos 2",
                "authfile" => "\\\\DISKSTATION\\Development\\Projects\\iF.SVNAdmin\\Data\\dav svn.authz"
            )
        )
    )
);
?>