<?php

declare(strict_types=1);

namespace TXC\Box\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TXC\Box\Infrastructure\Environment\Settings;

#[AsCommand(name: 'app:cache:clear', description: 'Clear all caches')]
class CacheClearCommand extends AbstractCommand
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $settings = $this->getContainer()->get(Settings::class);
        $cacheDirs = [
            $settings->get('doctrine.cache_dir'),
            $settings->get('slim.cache_dir'),
            $settings->get('twig.cache'),
        ];
        foreach ($cacheDirs as $cacheDir) {
            if (!file_exists($cacheDir)) {
                continue;
            }

            $this->removeDirectory($cacheDir);
        }

        return Command::SUCCESS;
    }

    private function removeDirectory(string $path): void
    {
        $files = glob($path . '/*');
        if (false === $files) {
            return;
        }

        foreach ($files as $file) {
            is_dir($file) ? $this->removeDirectory($file) : unlink($file);
        }
        rmdir($path);
    }
}
