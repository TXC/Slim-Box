<?php

declare(strict_types=1);

namespace TXC\Box\Testing;

use Doctrine\ORM\EntityManagerInterface;

trait DatabaseMigration
{
    use WithContainer;

    protected function runDatabaseMigrations(): void
    {
        $entityManager = $this->getContainer()->get(EntityManagerInterface::class);

        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($entityManager);
        $metadatas = $entityManager->getMetadataFactory()->getAllMetadata();

        $schemaTool->updateSchema($metadatas, true);
        //$this->beforeApplicationDestroyed(function () use ($schemaTool, $metadatas) {
        //    $schemaTool->dropSchema($metadatas);
        //});
    }
}
