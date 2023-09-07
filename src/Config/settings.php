<?php

declare(strict_types=1);

use Monolog\Logger;
use TXC\Box\Infrastructure\Environment\Environment;
use TXC\Box\Infrastructure\Environment\Settings;

return [
    'slim' => [
        'encoding' => 'UTF-8',
        // Default Language to use on request
        'locale' => 'en_US',
        // Available locales to choose from
        'available_locales' => [
            'en_US',
        ],
        // Returns a detailed HTML page with error details and
        // a stack trace. Should be disabled in production.
        'displayErrorDetails' => !empty(getenv('DISPLAY_ERROR_DETAILS')),
        // Whether to display errors on the internal PHP log or not.
        'logErrors' => !empty(getenv('LOG_ERRORS')),
        // If true, display full errors with message and stack trace on the PHP log.
        // If false, display only "Slim Application Error" on the PHP log.
        // Doesn't do anything when 'logErrors' is false.
        'logErrorDetails' => !empty(getenv('LOG_ERROR_DETAILS')),
        // Path where Slim will cache the container, compiler passes, ...
        'cache_dir' => Settings::getAppRoot() . '/var/cache/slim',
        // Path where Slim template engine will locate template(s)
        'template_dir' => Settings::getAppRoot() . '/templates',
        // Route settings
        'route' => [
            // Path where Slim will cache routes
            'cache_file' => Settings::getAppRoot() . '/var/cache/slim/routes.php',
            // Route names that don't require login
            'public' => [
                'root',
                'login',
            ],
        ]
    ],
    'site' => [],
    'logger' => [
        'prefix' => getenv('APP_NAME') ?: 'app',
        'path' => (getenv('docker') !== false ? 'php://stdout' :
            (getenv('LOG_PATH') ?: Settings::getAppRoot() . '/var/log')),
        'level' => Environment::PRODUCTION !== Environment::from(getenv('ENVIRONMENT')) ? Logger::DEBUG : Logger::NOTICE
    ],
    'amqp' => [
        'rabbitmq' => [
            'host' => getenv('RABBITMQ_HOST') ?: '',
            'port' => getenv('RABBITMQ_PORT') ?: '',
            'username' => getenv('RABBITMQ_USER') ?: '',
            'password' => getenv('RABBITMQ_PASS') ?: '',
            'vhost' => getenv('RABBITMQ_VHOST') ?: '',
        ],
    ],
    'passes' => [
        'console' => [],
        'domain' => [],
        'middleware' => [],
        'repository' => [],
        'route' => [],
    ],
    'cors' => [
        'origin' => 'https://example.com',
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
        'requests' => getenv('LIMIT_REQUEST') ?: 600,
        'period' => getenv('LIMIT_PERIOD') ?: 60,
        'fallback' => getenv('LIMIT_FALLBACK') ?: true,
    ],
    'redis' => [
        'host' => getenv('REDIS_HOST') ?: '127.0.0.1',
        'port' => getenv('REDIS_PORT') ?: 6379,
        'username' => getenv('REDIS_USER') ?: null,
        'password' => getenv('REDIS_PASS') ?: null,
        'timeout' => getenv('REDIS_TIMEOUT') ?: 2.5,
    ],
    'twig' => [
        'debug' => Environment::DEV === Environment::from(getenv('ENVIRONMENT')),
        'charset' => 'UTF-8',
        'cache' => Settings::getAppRoot() . '/var/cache/views'
    ],
    'doctrine' => [
        // Enables or disables Doctrine metadata caching
        // for either performance or convenience during development.
        'dev_mode' => Environment::PRODUCTION !== Environment::from(getenv('ENVIRONMENT')),
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
            'driver' => getenv('DATABASE_DRIVER') ?: '',
            'host' => getenv('DATABASE_HOST') ?: '',
            'port' => getenv('DATABASE_PORT') ?: '',
            'dbname' => getenv('DATABASE_NAME') ?: '',
            'user' => getenv('DATABASE_USER') ?: '',
            'password' => getenv('DATABASE_PASSWORD') ?: '',
            'path' => getenv('DATABASE_PATH') ? Settings::getAppRoot() . getenv('DATABASE_PATH') : '',
            'charset' => getenv('DATABASE_CHARSET') ?: 'utf8mb4',
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
    'application' => [
    ],
];
