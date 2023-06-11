<?php

namespace Tests\AppBundle\Controller;

use AppBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\AppBundle\NeedLogin;

class TaskControllerTest extends WebTestCase
{
    use NeedLogin;

    private $user;

    private $manager;

    public function setUp()
    {
        $client = static::createClient();

        $userData = [
            'username' => 'User',
            'password' => '$2y$13$cx7WfZ3C24BccB0a9PuXCeyNCtPsxbMcCdUXh0ARBXap6HXgMiD.u',
            'email' => 'user@orange.fr'
        ];

        $this->user = new User();
        $this->user->setUsername($userData['username']);
        $this->user->setPassword($userData['password']);
        $this->user->setEmail($userData['email']);

        $this->manager = $client->getContainer()->get('doctrine.orm.entity_manager');
        $this->manager->persist($this->user);
        $this->manager->flush();
    }

    public function testListAction()
    {
        $client = static::createClient();

        $this->login($client, $this->user);

        $urlGenerator = $client->getContainer()->get('router');

        $client->request(Request::METHOD_GET, $urlGenerator->generate('task_list'));

        $response = new Response();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $this->assertContains('Créer une tâche', $client->getResponse()->getContent());
    }

    public function testListActionNoLogin()
    {
        $client = static::createClient();

        $urlGenerator = $client->getContainer()->get('router');

        $client->request(Request::METHOD_GET, $urlGenerator->generate('task_list'));

        $client->followRedirect();

        $response = new Response();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $this->assertNotContains('Créer une tâche', $client->getResponse()->getContent());
    }

    public function testCreateAction()
    {
        $client = static::createClient();

        $this->login($client, $this->user);

        $urlGenerator = $client->getContainer()->get('router');

        $crawler = $client->request(Request::METHOD_GET, $urlGenerator->generate('task_create'));

        $buttonCrawlerNode = $crawler->selectButton('Ajouter');

        $form = $buttonCrawlerNode->form([
            'task[title]' => 'Courses',
            'task[content]' => 'Aller au supermarché.'
        ]);

        $client->submit($form);

        $formValues = $form->getValues();

        $client->followRedirect();

        $response = new Response();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $this->assertContains('La tâche a bien été ajoutée.', $client->getResponse()->getContent());

        $this->assertEquals('Courses', $formValues['task[title]']);
        $this->assertEquals('Aller au supermarché.', $formValues['task[content]']);
    }

    public function testCreateActionBlank()
    {
        $client = static::createClient();

        $this->login($client, $this->user);

        $urlGenerator = $client->getContainer()->get('router');

        $crawler = $client->request(Request::METHOD_GET, $urlGenerator->generate('task_create'));

        $buttonCrawlerNode = $crawler->selectButton('Ajouter');

        $form = $buttonCrawlerNode->form([
            'task[title]' => '',
            'task[content]' => ''
        ]);

        $client->submit($form);

        $formValues = $form->getValues();

        $response = new Response();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $this->assertNotContains('La tâche a bien été ajoutée.', $client->getResponse()->getContent());

        $this->assertEquals('', $formValues['task[title]']);
        $this->assertEquals('', $formValues['task[content]']);
    }

    public function testEditAction()
    {
        $client = static::createClient();

        $this->login($client, $this->user);

        $urlGenerator = $client->getContainer()->get('router');

        $crawler = $client->request(Request::METHOD_GET, $urlGenerator->generate('task_edit', ['id' => 1]));

        $buttonCrawlerNode = $crawler->selectButton('Modifier');

        $form = $buttonCrawlerNode->form([
            'task[title]' => 'Test edit',
            'task[content]' => 'Test edit.'
        ]);

        $client->submit($form);

        $formValues = $form->getValues();

        $client->followRedirect();

        $response = new Response();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $this->assertContains('La tâche a bien été modifiée.', $client->getResponse()->getContent());

        $this->assertEquals('Test edit', $formValues['task[title]']);
        $this->assertEquals('Test edit.', $formValues['task[content]']);
    }

    public function testEditActionBlank()
    {
        $client = static::createClient();

        $this->login($client, $this->user);

        $urlGenerator = $client->getContainer()->get('router');

        $crawler = $client->request(Request::METHOD_GET, $urlGenerator->generate('task_edit', ['id' => 1]));

        $buttonCrawlerNode = $crawler->selectButton('Modifier');

        $form = $buttonCrawlerNode->form([
            'task[title]' => '',
            'task[content]' => ''
        ]);

        $client->submit($form);

        $formValues = $form->getValues();

        $response = new Response();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $this->assertNotContains('La tâche a bien été modifiée.', $client->getResponse()->getContent());

        $this->assertEquals('', $formValues['task[title]']);
        $this->assertEquals('', $formValues['task[content]']);
    }

    public function testToggleTaskAction()
    {
        $client = static::createClient();

        $this->login($client, $this->user);

        $urlGenerator = $client->getContainer()->get('router');

        $client->request(Request::METHOD_GET, $urlGenerator->generate('task_toggle', ['id' => 1]));

        $task = $this->manager->getRepository('AppBundle:Task')->find(1);

        $client->followRedirect();

        $response = new Response();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $this->assertContains('La tâche ' . $task->getTitle() . ' a bien été marquée comme faite.', $client->getResponse()->getContent());
    }

    public function testDeleteTaskAction()
    {
        $client = static::createClient();

        $this->login($client, $this->user);

        $urlGenerator = $client->getContainer()->get('router');

        $client->request(Request::METHOD_GET, $urlGenerator->generate('task_delete', ['id' => 1]));

        $client->followRedirect();

        $response = new Response();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $this->assertContains('La tâche a bien été supprimée.', $client->getResponse()->getContent());
    }

    public function tearDown()
    {
        $userEntity = $this->manager->merge($this->user);
        $this->manager->remove($userEntity);
        $this->manager->flush();
    }
}