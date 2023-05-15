<?php

declare(strict_types=1);

namespace TXC\Box\Testing;

use Doctrine\ORM\EntityManagerInterface;

trait DatabaseTruncation
{
    use WithContainer;

    protected function truncateDatabaseTables(): void
    {
        $entityManager = $this->getContainer()->get(EntityManagerInterface::class);

        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($entityManager);
        $metadatas = $entityManager->getMetadataFactory()->getAllMetadata();

        $schemaTool->createSchema($metadatas);
        $this->beforeContainerDestroyed(function () use ($schemaTool, $metadatas) {
            $schemaTool->dropSchema($metadatas);
        });
    }
}
