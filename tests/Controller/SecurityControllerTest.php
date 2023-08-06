<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManager;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SecurityControllerTest extends WebTestCase
{
    private ?KernelBrowser $client = null;

    protected AbstractDatabaseTool $databaseTool;

    private ?EntityManager $entityManager;

    private ?User $user = null;

    public function setUp(): void
    {
        $this->client = static::createClient();

        $container = static::getContainer();

        $this->entityManager = $container->get('doctrine')->getManager();

        $this->truncateEntities([
            User::class
        ]);

        $this->databaseTool = $this->client->getContainer()->get(DatabaseToolCollection::class)->get();

        $this->databaseTool->loadFixtures([
            'App\DataFixtures\UserFixtures'
        ]);

        $this->user = $this->entityManager->getRepository(User::class)->find(1);
    }

    public function testLoginActionLogged()
    {
        $this->client->loginUser($this->user);

        $this->client->request('GET', '/login');

        $this->client->followRedirect();

        $this->assertResponseIsSuccessful();

        $this->assertStringNotContainsString('Se connecter', $this->client->getResponse()->getContent());
    }

    private function truncateEntities(array $entities): void
    {
        $connection = $this->entityManager->getConnection();
        $databasePlatform = $connection->getDatabasePlatform();
        if ($databasePlatform->supportsForeignKeyConstraints()) {
            $connection->query('SET FOREIGN_KEY_CHECKS=0');
        }
        foreach ($entities as $entity) {
            $query = $databasePlatform->getTruncateTableSQL(
                $this->entityManager->getClassMetadata($entity)->getTableName()
            );
            $connection->executeUpdate($query);
        }
        if ($databasePlatform->supportsForeignKeyConstraints()) {
            $connection->query('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $userEntity = $this->entityManager->merge($this->user);
        $this->entityManager->remove($userEntity);
        $this->entityManager->flush();

        $this->entityManager->close();
        $this->entityManager = null;
    }
}
