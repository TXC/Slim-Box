<?php

declare(strict_types=1);

namespace TXC\Box\Commands;

use DI\Container;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\StyleInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class AbstractCommand extends Command
{
    protected ?StyleInterface $io = null;

    private Container $container;

    public function __construct(Container $container, string $name = null)
    {
        $this->container = $container;
        parent::__construct($name);
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
        parent::initialize($input, $output);
    }

    protected function getEntityManager(): EntityManagerInterface
    {
        return $this->container->get(EntityManagerInterface::class);
    }

    protected function getContainer(): Container
    {
        return $this->container;
    }
}
