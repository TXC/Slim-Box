<?php

declare(strict_types=1);

use Monolog\Logger;
use TXC\Box\Infrastructure\Environment\Environment;
use TXC\Box\Infrastructure\Environment\Settings;

return [
    'slim' => [
        // Language to use on request
        'locale' => 'en_US.UTF-8',
        // Returns a detailed HTML page with error details and
        // a stack trace. Should be disabled in production.
        'displayErrorDetails' => !empty($_ENV['DISPLAY_ERROR_DETAILS']),
        // Whether to display errors on the internal PHP log or not.
        'logErrors' => !empty($_ENV['LOG_ERRORS']),
        // If true, display full errors with message and stack trace on the PHP log.
        // If false, display only "Slim Application Error" on the PHP log.
        // Doesn't do anything when 'logErrors' is false.
        'logErrorDetails' => !empty($_ENV['LOG_ERROR_DETAILS']),
        // Path where Slim will cache the container, compiler passes, ...
        'cache_dir' => Settings::getAppRoot() . '/var/cache/slim',
        // Path where Slim template engine will locate template(s)
        'template_dir' => Settings::getAppRoot() . '/templates',
        // PHP-DI Autowiring support, enabled by default in PHP-DI
        //'autowiring' => true,
        // PHP-DI Attributes support, disabled by default in PHP-DI
        //'attributes' => true,
        'route' => [
            'cache_file' => Settings::getAppRoot() . '/var/cache/slim/routes.php',
        ]
    ],
    'logger' => [
        'prefix' => $_ENV['APP_NAME'] ?? 'app',
        'path' => isset($_ENV['docker']) ? 'php://stdout' :
            $_ENV['LOG_PATH'] ?? Settings::getAppRoot() . '/var/log',
        'level' => Environment::PRODUCTION !== Environment::from($_ENV['ENVIRONMENT']) ? Logger::DEBUG : Logger::NOTICE
    ],
    'amqp' => [
        'rabbitmq' => [
            'host' => $_ENV['RABBITMQ_HOST'] ?? '',
            'port' => $_ENV['RABBITMQ_PORT'] ?? '',
            'username' => $_ENV['RABBITMQ_USER'] ?? '',
            'password' => $_ENV['RABBITMQ_PASS'] ?? '',
            'vhost' => $_ENV['RABBITMQ_VHOST'] ?? '',
        ],
    ],
    'blacklist' => [
        'compilerpass' => [
            'console' => [],
            'domain' => [],
            'middleware' => [],
            'repository' => [],
            'route' => [],
        ],
    ],
    'csrf' => [
        'enabled' => true,
        'prefix' => 'dope',
        'storage_limit' => 200,
        'strength' => 16,
        'persistent_token' => true,
        'blacklist' => [
            // A list of url paths to ignore CSRF checks on
            // URL paths will be matched against each regular expression in this list.
            // Each regular expression should map to an array of methods.
            // Regular expressions will be delimited with ~ in preg_match, so if you
            // have routes with ~ in them, you must escape this character in your regex.
            // Also, remember to use ^ when you only want to match the beginning of a URL path!
            //
            // '/' => ['GET', 'POST'],
        ],
    ],
    'limit' => [
        'requests' => $_ENV['LIMIT_REQUEST'] ?? 600,
        'period' => $_ENV['LIMIT_PERIOD'] ?? 60,
        'fallback' => $_ENV['LIMIT_FALLBACK'] ?? true,
    ],
    'redis' => [
        'host' => $_ENV['REDIS_HOST'] ?? '127.0.0.1',
        'port' => $_ENV['REDIS_PORT'] ?? 6379,
        'username' => $_ENV['REDIS_USER'] ?? null,
        'password' => $_ENV['REDIS_PASS'] ?? null,
        'timeout' => $_ENV['REDIS_TIMEOUT'] ?? 2.5,
    ],
    'twig' => [
        'debug' => false,
        'charset' => 'UTF-8',
        'cache' => Settings::getAppRoot() . '/var/cache/views'
    ],
    'doctrine' => [
        // Enables or disables Doctrine metadata caching
        // for either performance or convenience during development.
        'dev_mode' => Environment::PRODUCTION !== Environment::from($_ENV['ENVIRONMENT']),
        // Path where Doctrine will cache the processed metadata
        // when 'dev_mode' is false.
        'cache_dir' => Settings::getAppRoot() . '/var/cache/doctrine',
        // List of paths where Doctrine will search for metadata.
        // Metadata can be either YML/XML files or PHP classes annotated
        // with comments or PHP8 attributes.
        'metadata_dirs' => [Settings::getAppRoot() . '/src/Domain'],
        // The parameters Doctrine needs to connect to your database.
        // These parameters depend on the driver (for instance the 'pdo_sqlite' driver
        // needs a 'path' parameter and doesn't use most of the ones shown in this example).
        // Refer to the Doctrine documentation to see the full list
        // of valid parameters:
        // @see https://www.doctrine-project.org/projects/doctrine-dbal/en/current/reference/configuration.html
        'connection' => [
            'driver' => $_ENV['DATABASE_DRIVER'] ?? '',
            'host' => $_ENV['DATABASE_HOST'] ?? '',
            'port' => $_ENV['DATABASE_PORT'] ?? '',
            'dbname' => $_ENV['DATABASE_NAME'] ?? '',
            'user' => $_ENV['DATABASE_USER'] ?? '',
            'password' => $_ENV['DATABASE_PASSWORD'] ?? '',
            'path' => isset($_ENV['DATABASE_PATH']) ? Settings::getAppRoot() . $_ENV['DATABASE_PATH'] : '',
            'charset' => $_ENV['DATABASE_CHARSET'] ?? 'utf8mb4',
        ],
        'migrations' => [
            'table_storage' => [
                'table_name' => 'migrations',
                'version_column_name' => 'version',
                'version_column_length' => 1024,
                'executed_at_column_name' => 'executed_at',
                'execution_time_column_name' => 'execution_time',
            ],
            'migrations_paths' => [
                'App\Migrations' => Settings::getAppRoot() . '/migrations',
            ],
            'all_or_nothing' => true,
            'transactional' => true,
            'check_database_platform' => true,
            'organize_migrations' => 'none',
            'connection' => null,
            'em' => null,
        ],
    ],
    'blacklist' => [
        'compilerpass' => [
            'console' => [],
            'domain' => [],
            'middleware' => [],
            'repository' => [],
            'route' => [],
        ]
    ],
    'application' => [
    ],
];
