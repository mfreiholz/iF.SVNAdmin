<?php
return array (

    "common" => array (
        // Absolute path to the "svn" binary.
        // e.g. Linux: "/usr/bin/svn"
        // e.g. Windows: "C:\\Program Files (x86)\\Subversion\\bin\\svn.exe"
        "svn_binary_path" => "/usr/bin/svn",

        // Absolute path to the "svnadmin" binary.
        // e.g. Linux: "/bin/svnadmin"
        // e.g. Windows: "C:\\Program Files (x86)\\Subversion\\bin\\svnadmin.exe"
        "svnadmin_binary_path" => "/usr/bin/svnadmin",

        // Absolute path to a custom Subversion user config directory.
        // Some systems with SElinux permit access to the default config directory.
        // This directive can be used to define a custom directory on which the application does have access.
        // e.g.: "<iF.SVNAdmin-Root>/data/svnconfig"
        "svn_config_directory" => SVNADMIN_DATA_DIR . DIRECTORY_SEPARATOR . "svnconfig",

        // Absolute path to the global Subversion "authz" file. It contains all user permissions
        // and will also be accessed by Apache or svnserve-deamon.
        // e.g. Linux: "/opt/subversion/authz"
        // e.g. Windows: "C:\\Path\\To\\authz"
        "svn_authz_file" => SVNADMIN_DATA_DIR . DIRECTORY_SEPARATOR . "subversion.authz",

        // Number of backups to store of each Subversion "authz" file.
        // The system creates a backup for every action, e.g.: Deleting five users generates five backups.
        // Setting this configuration to 0 disables the backup functionality.
        "svn_authz_file_backup_count" => 25,

        // Indicates whether the function to delete an repository should be available.
        "repository_deletion_enabled" => false,

        // Indicates whether the repository-delete action should move the repository into a trash folder instead of deleting it.
        // The trash will provide an "empty trash" function, if "repository_deletion_enabled" is enabled.
        "repository_deletion_trash_enabled" => false
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
            "file" => SVNADMIN_DATA_DIR . DIRECTORY_SEPARATOR . "dav svn.passwd"
        ),
        array (
            "id" => "authdigest",
            "class_name" => "DigestAuthenticator",
            "file" => SVNADMIN_DATA_DIR . DIRECTORY_SEPARATOR . "dav svn.digest.passwd",
            "realm" => "myrealm"
        ),
        /*array (
            "id" => "authldap",
            "class_name" => "LdapAuthenticator",
            "host_url" => "ldap://h2377348.stratoserver.net:389/",
            "protocol_version" => 3,
            "bind_dn" => "CN=ADReadUser,CN=Users,DC=insanefactory,DC=com",
            "bind_password" => "ADReadUser",
            "search_base_dn" => "OU=iF.SVNAdmin,DC=insanefactory,DC=com",
            "search_filter" => "(&(objectClass=person)(objectClass=user))",
            "attribute" => "sAMAccountName"
        )*/
    ),

    /**
     * Data Providers
     */

    "providers" => array (

        // Users
        "user" => array (
            "passwdusers" => array (
                "class_name" => "PasswdUserProvider",
                "file" => SVNADMIN_DATA_DIR . DIRECTORY_SEPARATOR . "dav svn.passwd"
            ),
            "digestusers" => array (
                "class_name" => "DigestUserProvider",
                "file" => SVNADMIN_DATA_DIR . DIRECTORY_SEPARATOR . "dav svn.digest.passwd",
                "realm" => "myrealm"
            ),
            "ldapusers" => array (
                "class_name" => "LdapUserProvider",
                "host_url" => "ldap://h2377348.stratoserver.net:389/",
                "protocol_version" => 3,
                "bind_dn" => "CN=ADReadUser,CN=Users,DC=insanefactory,DC=com",
                "bind_password" => "abcABC123!\"§",
                "search_base_dn" => "OU=iF.SVNAdmin,DC=insanefactory,DC=com",
                "search_filter" => "(&(objectClass=person)(objectClass=user))",
                "attributes" => array (
                    "sAMAccountName",
                    "givenName",
                    "sn"
                ),
                "display_name_format" => "%givenName %sn"
            )
        ),

        // Groups
        "group" => array (
            "svnauthgroups" => array (
                "class_name" => "SvnAuthGroupProvider",
                "svn_authz_file" => ""
            ),
            "svnauthgroups2" => array (
                "class_name" => "SvnAuthGroupProvider",
                "svn_authz_file" => SVNADMIN_DATA_DIR . DIRECTORY_SEPARATOR . "subversion2.authz"
            ),
            /*"ldapgroups" => array (
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
            )*/
        ),

        "groupmember" => array(
          "svnauthzgroupmembers" => array(
            "class_name" => "SvnAuthzGroupMemberAssociater",
            "for_provider" => array("svnauthgroups", "svnauthgroups2", "passwdusers", "digestusers", "digestusers2"),
            "svn_authz_file" => ""
          )
        ),

        // Repositories
        "repository" => array (
            "svnparentrepos" => array (
                "class_name" => "SvnParentRepositoryProvider",
                "path" => SVNADMIN_DATA_DIR . DIRECTORY_SEPARATOR . "my repos",
                "svn_authz_file" => ""
            ),
            "svnparentrepos2" => array (
                "class_name" => "SvnParentRepositoryProvider",
                "path" => SVNADMIN_DATA_DIR . DIRECTORY_SEPARATOR . "my repos 2",
                "svn_authz_file" => ""
            )
        )
    )
);
?>
