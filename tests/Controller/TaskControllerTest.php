<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Entity\Task;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TaskControllerTest extends WebTestCase
{
    private ?\Symfony\Bundle\FrameworkBundle\KernelBrowser $client = null;

    private $entityManager = null;

    private ?User $user = null;

    private ?Task $task = null;

    public function setUp(): void
    {
        //self::ensureKernelShutdown();

        //parent::setUp();

        $this->client = static::createClient();

        $this->truncateEntities([
            User::class,
            Task::class
        ]);

        //$kernel = self::bootKernel();

        $container = static::getContainer();

        $this->entityManager = $container->get('doctrine')->getManager();

        //$this->entityManager = $this->client->getContainer()->get('doctrine.orm.entity_manager');

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
        //dump($this->client);
        //exit;

        //$this->login($this->client, $this->user);

        $this->client->loginUser($this->user);

        $urlGenerator = $this->client->getContainer()->get('router');

        $this->client->request(Request::METHOD_GET, $urlGenerator->generate('task_list'));

        //$this->client->request('GET', '/tasks');

        $this->assertResponseIsSuccessful();

        $this->assertSelectorExists('a', 'Créer une tâche');
    }

    public function testListActionNoLogin()
    {
        //$container = static::getContainer();

        $urlGenerator = $this->client->getContainer()->get('router');

        $this->client->request(Request::METHOD_GET, $urlGenerator->generate('task_list'));

        //$this->client->request(Request::METHOD_GET, '/tasks');

        $this->client->followRedirect();

        $this->assertResponseIsSuccessful();

        $this->assertSelectorTextNotContains('a', 'Créer une tâche');
    }

    public function testCreateAction()
    {
        $this->client->loginUser($this->user);

        $urlGenerator = $this->client->getContainer()->get('router');

        $crawler = $this->client->request(Request::METHOD_GET, $urlGenerator->generate('task_create'));

        $buttonCrawlerNode = $crawler->selectButton('Ajouter');

        $form = $buttonCrawlerNode->form([
            'task[title]' => 'Courses',
            'task[content]' => 'Aller au supermarché.'
        ]);

        $this->client->submit($form);

        $formValues = $form->getValues();

        $this->client->followRedirect();

        $this->assertResponseIsSuccessful();

        //$this->assertContains('La tâche a bien été ajoutée.', $this->client->getResponse()->getContent());

        $this->assertSelectorExists('.alert-success', 'La tâche a bien été ajoutée.');

        $task = $this->entityManager->getRepository(Task::class)->find(2);

        $this->assertEquals($formValues['task[title]'], $task->getTitle());
        $this->assertEquals($formValues['task[content]'], $task->getContent());
    }

    public function testCreateActionBlank()
    {
        $this->client->loginUser($this->user);

        $countTasksBeforeTest = $this->getCountTasks();

        $urlGenerator = $this->client->getContainer()->get('router');

        $crawler = $this->client->request(Request::METHOD_GET, $urlGenerator->generate('task_create'));

        $buttonCrawlerNode = $crawler->selectButton('Ajouter');

        $form = $buttonCrawlerNode->form([
            'task[title]' => '',
            'task[content]' => ''
        ]);

        $this->client->submit($form);

        $this->assertResponseIsSuccessful();

        //$this->assertNotContains('La tâche a bien été ajoutée.', $this->client->getResponse()->getContent());

        $this->assertSelectorTextNotContains('div', 'La tâche a bien été ajoutée.');

        $countTasksAfterTest = $this->getCountTasks();

        $this->assertEquals($countTasksBeforeTest, $countTasksAfterTest);
    }

    public function testEditAction()
    {
        $this->login($this->client, $this->user);

        $urlGenerator = $this->client->getContainer()->get('router');

        $crawler = $this->client->request(Request::METHOD_GET, $urlGenerator->generate('task_edit', ['id' => 1]));

        $buttonCrawlerNode = $crawler->selectButton('Modifier');

        $form = $buttonCrawlerNode->form([
            'task[title]' => 'Test edit',
            'task[content]' => 'Test edit.'
        ]);

        $this->client->submit($form);

        $formValues = $form->getValues();

        $this->client->followRedirect();

        $response = new Response();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $this->assertContains('La tâche a bien été modifiée.', $this->client->getResponse()->getContent());

        $task = $this->entityManager->getRepository('AppBundle:Task')->find(1);

        $this->assertEquals($formValues['task[title]'], $task->getTitle());
        $this->assertEquals($formValues['task[content]'], $task->getContent());
    }

    public function testEditActionBlank()
    {
        $this->login($this->client, $this->user);

        $urlGenerator = $this->client->getContainer()->get('router');

        $crawler = $this->client->request(Request::METHOD_GET, $urlGenerator->generate('task_edit', ['id' => 1]));

        $buttonCrawlerNode = $crawler->selectButton('Modifier');

        $form = $buttonCrawlerNode->form([
            'task[title]' => '',
            'task[content]' => ''
        ]);

        $this->client->submit($form);

        $formValues = $form->getValues();

        $response = new Response();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $this->assertNotContains('La tâche a bien été modifiée.', $this->client->getResponse()->getContent());

        $task = $this->entityManager->getRepository('AppBundle:Task')->find(1);

        $this->assertNotEmpty($task->getTitle());
        $this->assertNotEmpty($task->getContent());
    }

    public function testToggleTaskAction()
    {
        $this->login($this->client, $this->user);

        $urlGenerator = $this->client->getContainer()->get('router');

        $this->client->request(Request::METHOD_GET, $urlGenerator->generate('task_toggle', ['id' => 1]));

        $task = $this->entityManager->getRepository('AppBundle:Task')->find(1);

        $this->client->followRedirect();

        $response = new Response();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $this->assertContains('La tâche ' . $task->getTitle() . ' a bien été marquée comme faite.', $this->client->getResponse()->getContent());

        $this->assertTrue($task->getIsDone());
    }

    public function testDeleteTaskAction()
    {
        $this->login($this->client, $this->user);

        $urlGenerator = $this->client->getContainer()->get('router');

        $this->client->request(Request::METHOD_GET, $urlGenerator->generate('task_delete', ['id' => 1]));

        $this->client->followRedirect();

        $response = new Response();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $this->assertContains('La tâche a bien été supprimée.', $this->client->getResponse()->getContent());

        $task = $this->entityManager->getRepository('AppBundle:Task')->find(1);

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