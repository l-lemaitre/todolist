<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Entity\Task;
use Doctrine\ORM\EntityManager;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

class TaskControllerTest extends WebTestCase
{
    private ?KernelBrowser $client = null;

    protected AbstractDatabaseTool $databaseTool;

    private ?EntityManager $entityManager = null;

    private ?Task $task = null;

    private ?User $user = null;

    public function setUp(): void
    {
        $this->client = static::createClient();

        $container = static::getContainer();

        $this->entityManager = $container->get('doctrine')->getManager();

        $this->truncateEntities([
            User::class,
            Task::class
        ]);

        $this->databaseTool = $this->client->getContainer()->get(DatabaseToolCollection::class)->get();

        $this->databaseTool->loadFixtures([
            'App\DataFixtures\UserFixtures',
            'App\DataFixtures\TaskFixtures'
        ]);

        $this->user = $this->entityManager->getRepository(User::class)->find(1);
        $this->task = $this->entityManager->getRepository(Task::class)->find(1);
    }

    public function testListAction()
    {
        $this->client->loginUser($this->user);

        $urlGenerator = $this->client->getContainer()->get('router');

        $crawler = $this->client->request(Request::METHOD_GET, $urlGenerator->generate('app_task_list'));

        $this->assertResponseIsSuccessful();

        $this->assertStringContainsString('Créer une tâche', $this->client->getResponse()->getContent());

        $countTasks = $this->getCountTasks();

        $this->assertCount($countTasks, $crawler->filter('.thumbnail'));
    }

    public function testListActionNotLoggedIn()
    {
        $urlGenerator = $this->client->getContainer()->get('router');

        $crawler = $this->client->request(Request::METHOD_GET, $urlGenerator->generate('app_task_list'));

        $this->client->followRedirect();

        $this->assertResponseIsSuccessful();

        $this->assertStringNotContainsString('Créer une tâche', $this->client->getResponse()->getContent());

        $countTasks = $this->getCountTasks();

        $this->assertNotCount($countTasks, $crawler->filter('.thumbnail'));
    }

    public function testCreateAction()
    {
        $this->client->loginUser($this->user);

        $urlGenerator = $this->client->getContainer()->get('router');

        $crawler = $this->client->request(Request::METHOD_GET, $urlGenerator->generate('app_task_create'));

        $buttonCrawlerNode = $crawler->selectButton('Ajouter');

        $form = $buttonCrawlerNode->form([
            'task[title]' => 'Lieu des courses',
            'task[content]' => 'Aller au supermarché.'
        ]);

        $this->client->submit($form);

        $formValues = $form->getValues();

        $this->client->followRedirect();

        $this->assertResponseIsSuccessful();

        $this->assertStringContainsString('La tâche a bien été ajoutée.',
            $this->client->getResponse()->getContent());

        $task = $this->entityManager->getRepository(Task::class)->find(2);

        $this->assertEquals($formValues['task[title]'], $task->getTitle());
        $this->assertEquals($formValues['task[content]'], $task->getContent());
    }

    public function testCreateActionBlank()
    {
        $this->client->loginUser($this->user);

        $countTasksBeforeTest = $this->getCountTasks();

        $urlGenerator = $this->client->getContainer()->get('router');

        $crawler = $this->client->request(Request::METHOD_GET, $urlGenerator->generate('app_task_create'));

        $buttonCrawlerNode = $crawler->selectButton('Ajouter');

        $form = $buttonCrawlerNode->form([
            'task[title]' => '',
            'task[content]' => ''
        ]);

        $this->client->submit($form);

        $this->assertResponseIsSuccessful();

        $this->assertStringNotContainsString('La tâche a bien été ajoutée.',
            $this->client->getResponse()->getContent());

        $countTasksAfterTest = $this->getCountTasks();

        $this->assertEquals($countTasksBeforeTest, $countTasksAfterTest);
    }

    public function testEditAction()
    {
        $this->client->loginUser($this->user);

        $urlGenerator = $this->client->getContainer()->get('router');

        $crawler = $this->client->request(Request::METHOD_GET,
            $urlGenerator->generate('app_task_edit', ['id' => $this->task->getId()]));

        $buttonCrawlerNode = $crawler->selectButton('Modifier');

        $form = $buttonCrawlerNode->form([
            'task[title]' => 'Lieu des courses 2',
            'task[content]' => 'Aller au supermarché. 2'
        ]);

        $this->client->submit($form);

        $formValues = $form->getValues();

        $this->client->followRedirect();

        $this->assertResponseIsSuccessful();

        $this->assertStringContainsString('La tâche a été modifiée avec succès.',
            $this->client->getResponse()->getContent());

        $task = $this->entityManager->getRepository(Task::class)->find($this->task->getId());

        $this->assertEquals($formValues['task[title]'], $task->getTitle());
        $this->assertEquals($formValues['task[content]'], $task->getContent());
    }

    public function testEditActionBlank()
    {
        $this->client->loginUser($this->user);

        $urlGenerator = $this->client->getContainer()->get('router');

        $crawler = $this->client->request(Request::METHOD_GET,
            $urlGenerator->generate('app_task_edit', ['id' => $this->task->getId()]));

        $buttonCrawlerNode = $crawler->selectButton('Modifier');

        $form = $buttonCrawlerNode->form([
            'task[title]' => '',
            'task[content]' => ''
        ]);

        $this->client->submit($form);

        $this->assertResponseIsSuccessful();

        $this->assertStringNotContainsString('La tâche a bien été modifiée.',
            $this->client->getResponse()->getContent());

        $task = $this->entityManager->getRepository(Task::class)->find($this->task->getId());

        $this->assertNotEmpty($task->getTitle());
        $this->assertNotEmpty($task->getContent());
    }

    public function testToggleAction()
    {
        $this->client->loginUser($this->user);

        $urlGenerator = $this->client->getContainer()->get('router');

        $this->client->request(Request::METHOD_GET,
            $urlGenerator->generate('app_task_toggle', ['id' => $this->task->getId()]));

        $this->client->followRedirect();

        $this->assertResponseIsSuccessful();

        $this->assertStringContainsString(
            'La tâche ' . $this->task->getTitle() . ' a bien été indiquée comme terminée.',
            $this->client->getResponse()->getContent());

        $this->assertTrue($this->task->getIsDone() == 1);
    }

    public function testDeleteAction()
    {
        $this->client->loginUser($this->user);

        $urlGenerator = $this->client->getContainer()->get('router');

        $this->client->request(Request::METHOD_GET, $urlGenerator->generate('app_task_delete',
            ['id' => $this->task->getId()]));

        $task = $this->entityManager->getRepository(Task::class)->find(1);

        $this->assertNull($task);
    }

    public function testDeleteActionNotAuthorized()
    {
        $this->client->loginUser($this->user);

        $urlGenerator = $this->client->getContainer()->get('router');

        $this->client->request(Request::METHOD_GET, $urlGenerator->generate('app_task_delete', ['id' => 2]));

        $this->client->followRedirect();

        $this->assertResponseIsSuccessful();

        $this->assertStringContainsString('Accès refusé. La tâche ne peut pas être supprimée.',
            $this->client->getResponse()->getContent());

        $task = $this->entityManager->getRepository(Task::class)->find(2);

        $this->assertNotNull($task);
    }

    public function testDeleteActionAnonymousUserRoleAdmin()
    {
        $this->client->loginUser($this->user);

        $urlGenerator = $this->client->getContainer()->get('router');

        $this->client->request(Request::METHOD_GET, $urlGenerator->generate('app_task_delete', ['id' => 3]));

        $task = $this->entityManager->getRepository(Task::class)->find(3);

        $this->assertNull($task);
    }

    public function testDeleteActionAnonymousNotAuthorizedUserRoleUser()
    {
        $userRoleUSer = $this->entityManager->getRepository(User::class)->find(2);

        $this->client->loginUser($userRoleUSer);

        $urlGenerator = $this->client->getContainer()->get('router');

        $this->client->request(Request::METHOD_GET, $urlGenerator->generate('app_task_delete', ['id' => 3]));

        $this->client->followRedirect();

        $this->assertResponseIsSuccessful();

        $this->assertStringContainsString('Accès refusé. La tâche ne peut pas être supprimée.',
            $this->client->getResponse()->getContent());

        $task = $this->entityManager->getRepository(Task::class)->find(3);

        $this->assertNotNull($task);
    }

    public function getCountTasks()
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder->select('count(task.id)');
        $queryBuilder->from(Task::class,'task');

        return $queryBuilder->getQuery()->getSingleScalarResult();
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