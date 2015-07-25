<?php
return array(

    "common" => array(
        // Absolute path to the "svn" binary.
        // e.g. Linux: "/usr/bin/svn"
        // e.g. Windows: "C:\\Program Files (x86)\\Subversion\\bin\\svn.exe"
        //"svn_binary_path" => "/usr/bin/svn",
        "svn_binary_path" => "D:\\Development\\Data\\subversion 1.7.4-1\\svn.exe",

        // Absolute path to the "svnadmin" binary.
        // e.g. Linux: "/bin/svnadmin"
        // e.g. Windows: "C:\\Program Files (x86)\\Subversion\\bin\\svnadmin.exe"
        //"svnadmin_binary_path" => "/usr/bin/svnadmin",
        "svnadmin_binary_path" => "D:\\Development\\Data\\subversion 1.7.4-1\\svnadmin.exe",

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
        "repository_deletion_enabled" => true,

        // Indicates whether the repository-delete action should move the repository into a trash folder instead of deleting it.
        // The trash will provide an "empty trash" function, if "repository_deletion_enabled" is enabled.
        "repository_deletion_trash_enabled" => false
    ),

    /**
     * Authentication
     * The authentication mechanism may use multiple Authenticators.
     */

    "authenticators" => array(
        /*array(
            "id" => "authstatic",
            "class_name" => "StaticAuthenticator",
            "users" => array (
                "static_admin" => "static_admin",
                "static_user" => "static_user"
            )
        ),*/
        /*array(
            "id" => "authpasswd",
            "class_name" => "PasswdAuthenticator",
            "file" => SVNADMIN_DATA_DIR . DIRECTORY_SEPARATOR . "svn users.passwd"
        ),*/
        /*array(
            "id" => "authdigest",
            "class_name" => "DigestAuthenticator",
            "file" => SVNADMIN_DATA_DIR . DIRECTORY_SEPARATOR . "svn users.digest.passwd",
            "realm" => "myrealm"
        ),*/
        /*array(
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

    "providers" => array(

        "repository" => array(
            array(
                "id" => "svn-cpp-core",
                "name" => "C++ Core Development",
                "class_name" => "SvnParentRepositoryProvider",
                "path" => SVNADMIN_DATA_DIR . DIRECTORY_SEPARATOR . "my repos",
                "svn_authz_file" => ""
            ),
            array(
                "id" => "svn-java-web",
                "name" => "Java Web Development",
                "class_name" => "SvnParentRepositoryProvider",
                "path" => SVNADMIN_DATA_DIR . DIRECTORY_SEPARATOR . "my repos 2",
                "svn_authz_file" => ""
            ),
            array(
                "id" => "svn-obsolete",
                "name" => "Obsolete Repositories",
                "class_name" => "SvnParentRepositoryProvider",
                "path" => SVNADMIN_DATA_DIR . DIRECTORY_SEPARATOR . "obsolete repos",
                "svn_authz_file" => ""
            ),
            array(
                "id" => "svn-error-on-select",
                "name" => "Throws exception on select",
                "class_name" => "SvnParentRepositoryProvider",
                "path" => SVNADMIN_DATA_DIR . DIRECTORY_SEPARATOR . "does not exist",
                "svn_authz_file" => ""
            )
        ),

        "user" => array(
            array(
                "id" => "passwdusers",
                "name" => "Users from PASSWD",
                "class_name" => "PasswdUserProvider",
                "file" => SVNADMIN_DATA_DIR . DIRECTORY_SEPARATOR . "svn users.passwd"
            ),
            array(
                "id" => "digestusers",
                "name" => "Users from DIGEST",
                "class_name" => "DigestUserProvider",
                "file" => SVNADMIN_DATA_DIR . DIRECTORY_SEPARATOR . "svn users.digest.passwd",
                "realm" => "myrealm"
            ),
            // The LdapUserProvider READS users from a local or remote LDAP (Active Directory, OpenLDAP) server.
            // The providers doesn't provide any CREATE- or DELETE- functionality.
            // NOTE: DO NOT USE IT YET - IN DEVELOPMENT
            /*"ldapusers" => array(
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
            )*/

        ),

        // Group backend
        // =============
        "group" => array(

            // The SvnAuthGroupProvider manages groups from a "authz" file.
            // svn_authz_file = If empty, it will use the default configured authz file from above common-section (recommended)
            "svnauthgroups" => array(
                "class_name" => "SvnAuthGroupProvider",
                "svn_authz_file" => ""
            ),

            // NOTE: DO NOT USE IT YET - IN DEVELOPMENT
            /*"ldapgroups" => array(
                "class_name" => "LdapGroupProvider",
                "host_url" => "ldap://h2377348.stratoserver.net:389/",
                "protocol_version" => 3,
                "bind_dn" => "CN=ADReadUser,CN=Users,DC=insanefactory,DC=com",
                "bind_password" => "abcABC123!\"§",
                "search_base_dn" => "OU=iF.SVNAdmin,DC=insanefactory,DC=com",
                "search_filter" => "(objectClass=group)",
                "attributes" => array (
                    "sAMAccountName"
                ),
                "display_name_format" => ""
            )*/

        ),

        // Group member association logic
        // ==============================
        "groupmember" => array(

            // Associates the members by the subversion authz-file.
            // for_provider = List of provider ID's, which uses this logic.
            // svn_authz_file = If empty, it will use the default configured authz file from above common-section (recommended)
            "svnauthzgroupmembers" => array(
                "class_name" => "SvnAuthzGroupMemberAssociater",
                "for_provider" => array("svnauthgroups", "passwdusers", "digestusers"),
                "svn_authz_file" => ""
            )

        )

    )
);
// The SvnParentRepositoryProvider provides multiple repositories from a single directory.
// Recursive directory structures are not supported by this backend logic, only flat structures are allowed.
// svn_authz_file = Used to store permissions. If empty, it will use the default configured authz file from above common-section (recommended)

// The PasswdUserProvider manages users of a simple "passwd" file.

// The DigestUserProvider manages users of a "digest" passwd file.
// realm = The realm of the users.