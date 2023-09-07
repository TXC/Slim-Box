<?php

declare(strict_types=1);

namespace TXC\Box\Commands;

use Composer\InstalledVersions;
use Dotenv\Dotenv;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Completion\CompletionInput;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TXC\Box\Infrastructure\Environment\Environment;
use TXC\Box\Infrastructure\Environment\Settings;

#[AsCommand(name: 'app:setup', description: 'Setup the application')]
class SetupCommand extends AbstractCommand
{
    protected ArrayInput $arguments;

    protected function configure(): void
    {
        $definitions = [
            new InputOption(
                name: 'environment',
                shortcut: 'e',
                mode: InputOption::VALUE_REQUIRED,
                description: 'What type of environment we deploy to. Possible values: dev, test, production',
                default: Environment::DEV->value,
                suggestedValues: function (CompletionInput $input) {
                    return array_filter(
                        array_map(fn($val) => $val->value, Environment::cases()),
                        function ($value) use ($input) {
                            return str_starts_with($input->getCompletionValue(), $value);
                        }
                    );
                }
            ),
            new InputOption(
                name: 'error_details',
                mode: InputOption::VALUE_NEGATABLE,
                description: 'Display error details. [NOT RECOMMENDED IN PRODUCTION]',
                default: true
            ),
            new InputOption(
                name: 'log_error',
                mode: InputOption::VALUE_NEGATABLE,
                description: 'Whether to display errors on the internal PHP log or not',
                default: false
            ),
            new InputOption(
                name: 'log_error_details',
                mode: InputOption::VALUE_NEGATABLE,
                description: 'If set, log full error message and stack trace. ' .
                             'Doesn\'t do anything when \'log_errors\' isn\'t set',
                default: false
            ),

            new InputOption(
                name: 'db_driver',
                mode: InputOption::VALUE_REQUIRED,
                description: 'What driver shall we use'
            ),
            new InputOption(
                name: 'db_host',
                mode: InputOption::VALUE_REQUIRED,
                description: 'Where shall we connect'
            ),
            new InputOption(
                name: 'db_path',
                mode: InputOption::VALUE_REQUIRED,
                description: 'Path to database file'
            ),
            new InputOption(
                name: 'db_port',
                mode: InputOption::VALUE_REQUIRED,
                description: 'What port to connect to',
                default: '3306',
            ),
            new InputOption(
                name: 'db_name',
                mode: InputOption::VALUE_REQUIRED,
                description: 'Database name to use'
            ),
            new InputOption(
                name: 'db_user',
                mode: InputOption::VALUE_REQUIRED,
                description: 'Database user'
            ),
            new InputOption(
                name: 'db_password',
                mode: InputOption::VALUE_REQUIRED,
                description: 'Password to database'
            ),
            new InputOption(
                name: 'db_charset',
                mode: InputOption::VALUE_REQUIRED,
                description: 'What are we using in the database',
                default: 'utf8mb4'
            ),
        ];

        $this
            ->setHelp(<<<EOT
The <info>%command.name%</info> command seeds the database with the drugs, locations and weapons:

    <info>%command.full_name%</info>
EOT
            )->setDefinition(new InputDefinition($definitions));
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        $config = $this->parseFiles();

        $environmentChoices = array_map(fn($val) => $val->value, Environment::cases());
        $environment = $input->getOption('environment');
        if (empty($environment) && getenv('ENVIRONMENT') === false) {
            $environment = $this->io->choice(
                'What environment are we using?',
                $environmentChoices,
                Environment::DEV->value
            );
            $input->setOption('environment', $environment);
        } elseif (empty($environment) && !empty($config['environment'])) {
            $input->setOption('environment', $config['environment']);
        } elseif (getenv('ENVIRONMENT') !== false) {
            if (in_array(getenv('ENVIRONMENT'), $environmentChoices)) {
                $input->setOption('environment', getenv('ENVIRONMENT'));
            }
        } else {
            throw new \RuntimeException('Missing ENVIRONMENT!');
        }

        $error_details = $input->getOption('error_details');
        if ($error_details === null && empty($config['error_details'])) {
            $error_details = match ($environment) {
                Environment::DEV,
                Environment::TEST => true,
                default => false
            };
            $input->setOption('error_details', $error_details);
        } elseif ($error_details === null && empty($config['error_details'])) {
            $input->setOption('error_details', $config['error_details']);
        }

        $log_error = $input->getOption('log_error');
        if ($log_error === null && empty($config['log_error'])) {
            $log_error = match ($environment) {
                Environment::DEV => false,
                default => true
            };
            $input->setOption('log_error', $log_error);
        } elseif ($log_error === null && empty($config['log_error'])) {
            $input->setOption('log_error', $config['log_error']);
        }

        $log_error_details = $input->getOption('log_error_details');
        if ($log_error_details === null && empty($config['log_error_details'])) {
            $log_error_details = match ($environment) {
                Environment::DEV => false,
                default => true
            };
            $input->setOption('log_error_details', $log_error_details);
        } elseif ($log_error_details === null && empty($config['log_error_details'])) {
            $input->setOption('log_error_details', $config['log_error_details']);
        }

        $db_driver = $input->getOption('db_driver');
        if (empty($db_driver) && empty($config['db_driver'])) {
            $drivers = \PDO::getAvailableDrivers();
            $choice = $this->io->choice(
                'What driver shall we use?',
                $drivers,
                'mysql'
            );
            $input->setOption('db_driver', $choice);
        } elseif (empty($db_driver) && !empty($config['db_driver'])) {
            $input->setOption('db_driver', $config['db_driver']);
        }

        if ($input->getOption('db_driver') === 'sqlite') {
            $db_path = $input->getOption('db_path');
            if (empty($db_path) && empty($config['db_path'])) {
                $choice = $this->io->ask(
                    'Where can we find the database? (Relative to app-root)',
                    '/var/db.sqlite'
                );
                $input->setOption('db_path', $choice);
            } elseif (empty($db_path) && !empty($config['db_path'])) {
                $input->setOption('db_path', $config['db_path']);
            }
        }

        $db_host = $input->getOption('db_host');
        if (empty($db_host) && empty($config['db_host'])) {
            $choice = $this->io->ask(
                'Where do we connect to database? (IP or Hostname)',
                '127.0.0.1'
            );
            $input->setOption('db_host', $choice);
        } elseif (empty($db_host) && !empty($config['db_host'])) {
            $input->setOption('db_host', $config['db_host']);
        }

        $db_port = $input->getOption('db_port');
        if (empty($db_port) && empty($config['db_port'])) {
            $choice = $this->io->ask(
                'What port do we connect to?',
                '3306'
            );
            $input->setOption('db_port', $choice);
        } elseif (empty($db_port) && !empty($config['db_port'])) {
            $input->setOption('db_port', $config['db_port']);
        }

        $db_name = $input->getOption('db_name');
        if (empty($db_name) && empty($config['db_name'])) {
            $choice = $this->io->ask(
                'What name do the database have?',
                ''
            );
            $input->setOption('db_name', $choice);
        } elseif (empty($db_name) && !empty($config['db_name'])) {
            $input->setOption('db_name', $config['db_name']);
        }

        $db_user = $input->getOption('db_user');
        if (empty($db_user) && empty($config['db_user'])) {
            $choice = $this->io->ask(
                'WHat username shall we use?',
                ''
            );
            $input->setOption('db_user', $choice);
        } elseif (empty($db_user) && !empty($config['db_user'])) {
            $input->setOption('db_user', $config['db_user']);
        }

        $db_password = $input->getOption('db_password');
        if (empty($db_password) && empty($config['db_password'])) {
            $choice = $this->io->ask(
                'What password do we use?',
                ''
            );
            $input->setOption('db_password', $choice);
        } elseif (empty($db_password) && !empty($config['db_password'])) {
            $input->setOption('db_password', $config['db_password']);
        }

        $db_charset = $input->getOption('db_charset');
        if (empty($db_charset) && !empty($config['db_charset'])) {
            $input->setOption('db_charset', $config['db_charset']);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->configureEnvironmentFile($input);

        $rc = $this->clearCache($output);
        if (!$rc) {
            $this->io->error('Cache failure');
        }

        $this->runCommands($output);

        return self::SUCCESS;
    }

    private function configureEnvironmentFile(InputInterface $input): void
    {
        $options = $input->getOptions();

        $variables = [
            'environment' => 'ENVIRONMENT=%s',
            'error_details' => 'DISPLAY_ERROR_DETAILS=%d',
            'log_error' => 'LOG_ERRORS=%d',
            'log_error_details' => 'LOG_ERROR_DETAILS=%d',
            'db_driver' => 'DATABASE_DRIVER=pdo_%s',
            'db_host' => 'DATABASE_HOST=%s',
            'db_path' => 'DATABASE_PATH=%s',
            'db_port' => 'DATABASE_PORT=%s',
            'db_name' => 'DATABASE_NAME=%s',
            'db_user' => 'DATABASE_USER=%s',
            'db_password' => 'DATABASE_PASSWORD=%s',
            'db_charset' => 'DATABASE_CHARSET=%s',
        ];

        $envContent = '';
        foreach ($variables as $key => $str) {
            if (isset($options[$key])) {
                $envContent .= sprintf($str, $options[$key]);
            } else {
                $envContent .= '#' . sprintf($str, '');
            }
            $envContent .= PHP_EOL;
        }

        file_put_contents(Settings::getAppRoot() . '/.env', $envContent);
    }

    private function clearCache(OutputInterface $output): int
    {
        $compiledContainer = dirname(__FILE__) . '/../../var/cache/CompiledContainer.php';
        if (!file_exists($compiledContainer)) {
            return self::FAILURE;
        }

        if (@unlink($compiledContainer) === false) {
            return self::SUCCESS;
        } else {
            $output->writeln(['', '<error>Clearing cache failed!</error>', '']);
            return self::FAILURE;
        }
    }

    private function runCommands(OutputInterface $output): void
    {
        $commands = [];

        if (InstalledVersions::getVersion('doctrine/migrations') !== null) {
            $commands[] = 'migrations:migrate';
        } else {
            $commands[] = ['orm:schema-tool:update', ['complete' => true, 'force' => true]];
        }

        // Validate the mapping files
        $commands[] = 'orm:validate-schema';
        // Clear all cache
        $commands[] = 'app:cache:clear';

        try {
            $defaultArguments =  [
                '--no-interaction' => true,
                '--quiet' => true
            ];
            foreach ($commands as $command) {
                $arguments = $defaultArguments;
                if (is_array($command)) {
                    $command = $command[0];
                    $arguments = array_merge($arguments, $command[1]);
                }

                $res = $this->getApplication()
                    ->find($command)
                    ->run(new ArrayInput($arguments), $output);
                if ($res !== self::SUCCESS) {
                    throw new \UnexpectedValueException('Unexpected return code', $res);
                }
            }
        } catch (\Throwable $e) {
            $error = sprintf('Command \'%s\' failed with \'%s\' [%d]!', $command, $e->getMessage(), $e->getCode());
            $this->io->error($error);
        }
    }

    private function parseFiles(): array
    {
        $config = [];
        if (file_exists('/secrets/secrets.ini')) {
            $config = array_merge($config, $this->normaliseSecrets());
        }

        if (file_exists(Settings::getAppRoot() . '/.env')) {
            $config = array_merge($config, $this->normaliseDotEnv());
        }
        return $config;
    }

    private function normaliseSecrets(): array
    {
        $config = parse_ini_file('/secrets/secrets.ini', true);
        return [
            'db_driver' => $config['database']['db_driver'] ?? 'mysql',
            'db_host' => $config['database']['db_host'] ?? '',
            'db_port' => $config['database']['db_port'] ?? '',
            'db_name' => $config['database']['db_name'] ?? '',
            'db_user' => $config['database']['db_user'] ?? '',
            'db_password' => $config['database']['db_password'] ?? '',
            'db_charset' => $config['database']['db_charset'] ?? 'utf8mb4',
        ];
    }

    private function normaliseDotEnv(): array
    {
        $appRoot = Settings::getAppRoot();
        $content = file_get_contents($appRoot . '/.env');
        $config = Dotenv::parse($content);
        if (str_starts_with($config['DATABASE_DRIVER'], 'pdo_')) {
            $config['DATABASE_DRIVER'] = substr($config['DATABASE_DRIVER'], 4);
        }

        return [
            'environment' => $config['ENVIRONMENT'] ?? 'dev',
            'error_details' => $config['DISPLAY_ERROR_DETAILS'] ?? '0',
            'log_error' => $config['LOG_ERRORS'] ?? '0',
            'log_error_details' => $config['LOG_ERROR_DETAILS'] ?? '0',
            'db_driver' => $config['DATABASE_DRIVER'] ?? 'mysql',
            'db_host' => $config['DATABASE_HOST'] ?? '',
            'db_path' => $config['DATABASE_PATH'] ?? '',
            'db_port' => $config['DATABASE_PORT'] ?? '',
            'db_name' => $config['DATABASE_NAME'] ?? '',
            'db_user' => $config['DATABASE_USER'] ?? '',
            'db_password' => $config['DATABASE_PASSWORD'] ?? '',
            'db_charset' => $config['DATABASE_CHARSET'] ?? 'utf8mb4',
        ];
    }
}
