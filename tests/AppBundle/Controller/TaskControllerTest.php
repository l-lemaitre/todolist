<?php

namespace Tests\AppBundle\Controller;

use App\AppBundle\Entity\User;
use App\AppBundle\Entity\Task;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\AppBundle\Service\NeedLogin;

class TaskControllerTest extends WebTestCase
{
    use NeedLogin;

    private $client = null;

    private $manager = null;

    private $user = null;

    public function setUp()
    {
        self::bootKernel();

        $this->truncateEntities([
            User::class,
            Task::class
        ]);

        $this->client = static::createClient();

        $this->manager = $this->client->getContainer()->get('doctrine.orm.entity_manager');

        $userData = [
            'username' => 'User',
            'password' => '$2y$13$cx7WfZ3C24BccB0a9PuXCeyNCtPsxbMcCdUXh0ARBXap6HXgMiD.u',
            'email' => 'user@orange.fr'
        ];
        $this->user = new User();
        $this->user->setUsername($userData['username']);
        $this->user->setPassword($userData['password']);
        $this->user->setEmail($userData['email']);
        $this->manager->persist($this->user);

        $taskData = [
            'title' => 'Test',
            'content' => 'Test.'
        ];
        $task = new Task();
        $task->setTitle($taskData['title']);
        $task->setContent($taskData['content']);
        $this->manager->persist($task);

        $this->manager->flush();
    }

    public function testListAction()
    {
        $this->login($this->client, $this->user);

        $urlGenerator = $this->client->getContainer()->get('router');

        $this->client->request(Request::METHOD_GET, $urlGenerator->generate('task_list'));

        $response = new Response();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $this->assertContains('Créer une tâche', $this->client->getResponse()->getContent());
    }

    public function testListActionNoLogin()
    {
        $urlGenerator = $this->client->getContainer()->get('router');

        $this->client->request(Request::METHOD_GET, $urlGenerator->generate('task_list'));

        $this->client->followRedirect();

        $response = new Response();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $this->assertNotContains('Créer une tâche', $this->client->getResponse()->getContent());
    }

    public function testCreateAction()
    {
        $this->login($this->client, $this->user);

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

        $response = new Response();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $this->assertContains('La tâche a bien été ajoutée.', $this->client->getResponse()->getContent());

        $task = $this->manager->getRepository('AppBundle:Task')->find(2);

        $this->assertEquals($formValues['task[title]'], $task->getTitle());
        $this->assertEquals($formValues['task[content]'], $task->getContent());
    }

    public function testCreateActionBlank()
    {
        $this->login($this->client, $this->user);

        $countTasksBeforeTest = $this->getCountTasks();

        $urlGenerator = $this->client->getContainer()->get('router');

        $crawler = $this->client->request(Request::METHOD_GET, $urlGenerator->generate('task_create'));

        $buttonCrawlerNode = $crawler->selectButton('Ajouter');

        $form = $buttonCrawlerNode->form([
            'task[title]' => '',
            'task[content]' => ''
        ]);

        $this->client->submit($form);

        $formValues = $form->getValues();

        $response = new Response();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $this->assertNotContains('La tâche a bien été ajoutée.', $this->client->getResponse()->getContent());

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

        $task = $this->manager->getRepository('AppBundle:Task')->find(1);

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

        $task = $this->manager->getRepository('AppBundle:Task')->find(1);

        $this->assertNotEmpty($task->getTitle());
        $this->assertNotEmpty($task->getContent());
    }

    public function testToggleTaskAction()
    {
        $this->login($this->client, $this->user);

        $urlGenerator = $this->client->getContainer()->get('router');

        $this->client->request(Request::METHOD_GET, $urlGenerator->generate('task_toggle', ['id' => 1]));

        $task = $this->manager->getRepository('AppBundle:Task')->find(1);

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

        $task = $this->manager->getRepository('AppBundle:Task')->find(1);

        $this->assertNull($task);
    }

    public function getCountTasks()
    {
        $queryBuilder = $this->manager->createQueryBuilder();
        $queryBuilder->select('count(task.id)');
        $queryBuilder->from('AppBundle:Task','task');

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    private function getEntityManager()
    {
        return self::$kernel->getContainer()
            ->get('doctrine')
            ->getManager();
    }

    private function truncateEntities(array $entities)
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

    public function tearDown()
    {
        $userEntity = $this->manager->merge($this->user);
        $this->manager->remove($userEntity);
        $this->manager->flush();
    }
}