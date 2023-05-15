<?php

declare(strict_types=1);

use TXC\Box\DependencyInjection\ContainerFactory;
use Doctrine\ORM\EntityManagerInterface;

return ContainerFactory::createForTestSuite()->get(EntityManagerInterface::class);
