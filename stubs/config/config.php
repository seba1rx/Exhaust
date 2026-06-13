<?php
return [
    "debug" => [
        /**
         * This var allows you to enable a log debug in javascript.
         * It will print to console.log the data used to create the request
         * and each command in the response that the application will handle
         *
         * @see public/assets/js/Exhaust.js
         */
        "frontend" => true,
        /**
         * This var allows you to enable the inclusion of relevant data
         * in the response object related to the execution and handling
         * in the backend of the request
         */
        "backend" => false,
        /**
         * This var allows you to activate/deactivate the DB logging.
         * if activated, the queries executed during the request
         * will be added to the response
         */
        "database" => true,
    ],
    /**
     * Exception and Error
     */
    "exception" => [
        "show_detail" => true,
        "show_trace" => true,
    ],
    /**
     * Session configuration
     */
    "session" => [
        'sessionLifetime' => 1800,
    ],
    "request" => [
        /**
         * A list of paths that should be ignored by the app. This can be useful
         * when the frontend is requesting resources like assets (favicon, js,
         * css, etc) and you don't want the app to try to manage those requests,
         * but let the server itself manage that request without interfering.
         */
        "ignore" => [
            "assets"
        ],
    ],
    /**
     * Access restriction
     */
    "access" => [
        /**
         * Activates or deactivates the validation of
         * connections making requests to the app
         */
        "validate_remote_request_address" => false,
        /**
         * if access->validate_remote_request_address is true,
         * the app will look at this list in order to check if
         * the remote IP is in the whitelist.
         */
        "allowed_remote_request_addresses" => [
            "0.0.0.0",   // allow any incomming request regardless of origin
            // "127.0.0.1",
        ],
    ],
    "websocket" => [
        "library" => "thruway",
        "service_ip" => "0.0.0.0",
        "service_port" => "9090",
        "service_url" => "dev.fw.local", // change it to your needs
        "service_protocol" => "wss",
    ],
    "jwt" => [
        "uses_token" => false,
        "token_type" => "bearer",
        "token_hash" => null,
    ],
    /**
     * Template engine configuration
     *
     * The Exhaust framework comes with a template engine called "Piston"
     * you can check it out by selecting:
     * template_engine->use->piston
     */
    "template_engine" => [
        /**
         * The "Exhaust framework" comes with contracts and facades to seamlessly
         * implement some templating engines. You can chose any of the following
         * ones as they are supported out of the box:
         * + Piston (The template engine packed in this framework)
         * + Twig
         * + Smarty
         * + Blade
         * + Plates
         */
        "use" => "twig",
        "configuration" => [
            "pathToTemplates" => "/resources/templates/",
            "pathToFrameworkTemplates" => "/resources/engeen_templates/",
            "pathToCompilation" => "/storage/templates_compile/",
            "pathToCache" => "/storage/templates_cache/",
            "options" => [
                "debug" => false,
                "strict_variables" => true,
            ],
            "shouldMinifyOutput" => false,
        ],
        "default" => [
            /**
             * Here you can define te default template to load
             */
            "main" => "/landing/landing.html.twig"

        ],
    ],
    /**
     * Database connections
     */
    "DB" => [
        /**
         * Indicate which DB->environment should the system use.
         */
        "use" => "development",
        /**
         * List each environment and its database connection parameters
         */
        "environment" => [
            "development" => [
                "driver" => "mysql",
                "database" => "engine",
                "charset" => "utf8mb4",
                "collation" => "utf8mb4_0900_ai_ci",
                "port" => "3306",
                "host" => "host.docker.internal",
                // "host" => "mysql", // to use it with a docker mysql/mariadb container
                // "host" => "localhost", // to use it with docker mysql/mariadb container in a bundle with the framework
                "user" => "root",
                "password" => "password",
                "root_password" => "password",
            ],
            "production" => [
                "driver" => "mysql",
                "database" => "engine",
                "charset" => "utf8mb4",
                "collation" => "utf8mb4_0900_ai_ci",
                "port" => "3306",
                "host" => "mysql",
                "user" => "root",
                "password" => "password",
                "root_password" => "password",
            ],
        ],
    ],
    /**
     * This section allows you to configure the mailing accounts you
     * want to use in your system.
     */
    "mailing" => [
        "use_mailing" => true,
        "default_account" => "default",
        "accounts" => [
            "default" => [
                "account" => "demo@outlook.com",
                "alias" => "DemoName",
                "password" => "thePasswordHere",
            ],
            "contact" => [
                "account" => "demo@engine.com",
                "alias" => "Contact"
            ],
            "sales" => [
                "account" => "demo@engine.com",
                "alias" => "Contact"
            ],
            "support" => [
                "account" => "demo@engine.com",
                "alias" => "Contact"
            ],
        ],
    ],
    /**
     * The following data is used to configure the website title,
     * main language, description, copyright and other global
     * info displayed in the templates views
     */
    "website" => [
        "app_url" => "dev.engeen.local",
        "app_info" => [
            "language" => "es",
            "title" => "Exhaust app",
            "app_name" => "Exhaust Framework",
            "app_description" => "PHP framework",
            "tag_icon" => "img/engeen.png",
            "copyright_year" => "2026",
            "copyright_text" => "Sebastian Basilio",
        ],
    ],
    /**
     * Here you can define your constants and the Exhaust\App class will load
     * them to the global scope
     */
    "constants" => [
        // "MY_STRING_CONSTANT" => "some value",
        // "MY_BOOL_CONSTANT" => TRUE,
        // "MY_INT_CONSTANT" => 123,
    ]
];