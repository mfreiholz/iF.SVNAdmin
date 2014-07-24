<?php
return array (

    "engine" => array (
        // Name of the request parameter, which contains the module ID.
        "module_parameter" => "m",

        // Used "Encoding", if not set by module.
        "default_encoding" => "UTF-8",

        // Default "Content-Type", if not set by module.
        "default_content_type" => "text/html",

        // Directory which contains the modules.
        "module_directory" => "../include/service/"
    ),

    "modules" => array (

        "LoginService" => array (
            "class_name" => "LoginService",
            "description" => "",
            "author" => ""
        ),

        "CommonService" => array (
            "class_name" => "CommonService",
            "description" => "",
            "author" => ""
        ),

        "TranslationService" => array (
            "class_name" => "TranslationService",
            "description" => "",
            "author" => ""
        ),

        "UserService" => array (
            "class_name" => "UserService",
            "description" => "",
            "author" => ""
        ),

        "GroupService" => array (
            "class_name" => "GroupService",
            "description" => "",
            "author" => ""
        ),

        "RepositoryService" => array (
            "class_name" => "RepositoryService",
            "description" => "",
            "author" => ""
        )
    )
)
;
?>