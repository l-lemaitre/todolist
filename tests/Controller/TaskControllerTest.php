<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Entity\Task;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

class TaskControllerTest extends WebTestCase
{
    private ?KernelBrowser $client = null;

    private $entityManager = null;

    private ?Task $task = null;

    private ?User $user = null;

    public function setUp(): void
    {
        $this->client = static::createClient();

        $this->truncateEntities([
            User::class,
            Task::class
        ]);

        $container = static::getContainer();

        $this->entityManager = $container->get('doctrine')->getManager();

        $userData = [
            'username' => 'User',
            'password' => '$2y$13$cx7WfZ3C24BccB0a9PuXCeyNCtPsxbMcCdUXh0ARBXap6HXgMiD.u',
            'email' => 'user@orange.fr'
        ];
        $this->user = new User();
        $this->user->setUsername($userData['username']);
        $this->user->setPassword($userData['password']);
        $this->user->setEmail($userData['email']);
        $this->entityManager->persist($this->user);

        $taskData = [
            'title' => 'Test',
            'content' => 'Test.'
        ];
        $this->task = new Task();
        $this->task->setTitle($taskData['title']);
        $this->task->setContent($taskData['content']);
        $this->entityManager->persist($this->task);

        $this->entityManager->flush();
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
            'task[title]' => 'Courses',
            'task[content]' => 'Aller au supermarché.'
        ]);

        $this->client->submit($form);

        $formValues = $form->getValues();

        $this->client->followRedirect();

        $this->assertResponseIsSuccessful();

        $this->assertStringContainsString('La tâche a bien été ajoutée.', $this->client->getResponse()->getContent());

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

        $this->assertStringNotContainsString('La tâche a bien été ajoutée.', $this->client->getResponse()->getContent());

        $countTasksAfterTest = $this->getCountTasks();

        $this->assertEquals($countTasksBeforeTest, $countTasksAfterTest);
    }

    public function testEditAction()
    {
        $this->client->loginUser($this->user);

        $urlGenerator = $this->client->getContainer()->get('router');

        $crawler = $this->client->request(Request::METHOD_GET, $urlGenerator->generate('app_task_edit', ['id' => $this->task->getId()]));

        $buttonCrawlerNode = $crawler->selectButton('Modifier');

        $form = $buttonCrawlerNode->form([
            'task[title]' => 'Test edit',
            'task[content]' => 'Test edit.'
        ]);

        $this->client->submit($form);

        $formValues = $form->getValues();

        $this->client->followRedirect();

        $this->assertResponseIsSuccessful();

        $this->assertStringContainsString('La tâche a bien été modifiée.', $this->client->getResponse()->getContent());

        $task = $this->entityManager->getRepository(Task::class)->find($this->task->getId());

        $this->assertEquals($formValues['task[title]'], $task->getTitle());
        $this->assertEquals($formValues['task[content]'], $task->getContent());
    }

    public function testEditActionBlank()
    {
        $this->client->loginUser($this->user);

        $urlGenerator = $this->client->getContainer()->get('router');

        $crawler = $this->client->request(Request::METHOD_GET, $urlGenerator->generate('app_task_edit', ['id' => $this->task->getId()]));

        $buttonCrawlerNode = $crawler->selectButton('Modifier');

        $form = $buttonCrawlerNode->form([
            'task[title]' => '',
            'task[content]' => ''
        ]);

        $this->client->submit($form);

        $this->assertResponseIsSuccessful();

        $this->assertStringNotContainsString('La tâche a bien été modifiée.', $this->client->getResponse()->getContent());

        $task = $this->entityManager->getRepository(Task::class)->find($this->task->getId());

        $this->assertNotEmpty($task->getTitle());
        $this->assertNotEmpty($task->getContent());
    }

    public function testToggleTaskAction()
    {
        $this->client->loginUser($this->user);

        $urlGenerator = $this->client->getContainer()->get('router');

        $this->client->request(Request::METHOD_GET, $urlGenerator->generate('app_task_toggle', ['id' => $this->task->getId()]));

        $task = $this->entityManager->getRepository(Task::class)->find($this->task->getId());

        $this->client->followRedirect();

        $this->assertResponseIsSuccessful();

        $this->assertStringContainsString('La tâche ' . $task->getTitle() . ' a bien été marquée comme terminée.', $this->client->getResponse()->getContent());

        $this->assertTrue($task->getIsDone());
    }

    public function testDeleteTaskAction()
    {
        $this->client->loginUser($this->user);

        $urlGenerator = $this->client->getContainer()->get('router');

        $this->client->request(Request::METHOD_GET, $urlGenerator->generate('app_task_delete', ['id' => $this->task->getId()]));

        $this->client->followRedirect();

        $this->assertResponseIsSuccessful();

        $this->assertStringContainsString('La tâche a bien été supprimée.', $this->client->getResponse()->getContent());

        $task = $this->entityManager->getRepository(Task::class)->find(1);

        $this->assertNull($task);
    }

    public function getCountTasks()
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder->select('count(task.id)');
        $queryBuilder->from(Task::class,'task');

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    private function getEntityManager()
    {
        $container = static::getContainer();

        return $container->get('doctrine')->getManager();
    }

    private function truncateEntities(array $entities): void
    {
        $connection = $this->getEntityManager()->getConnection();
        $databasePlatform = $connection->getDatabasePlatform();
        if ($databasePlatform->supportsForeignKeyConstraints()) {
            $connection->query('SET FOREIGN_KEY_CHECKS=0');
        }
        foreach ($entities as $entity) {
            $query = $databasePlatform->getTruncateTableSQL(
                $this->getEntityManager()->getClassMetadata($entity)->getTableName()
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

        $taskEntity = $this->entityManager->merge($this->task);
        $this->entityManager->remove($taskEntity);
        $this->entityManager->flush();

        $this->entityManager->close();
        $this->entityManager = null;
    }
}