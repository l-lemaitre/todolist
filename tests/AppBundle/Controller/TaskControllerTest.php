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

    public function testListActionNoLogin()
    {
        $client = static::createClient();

        $urlGenerator = $client->getContainer()->get('router');

        $client->request(Request::METHOD_GET, $urlGenerator->generate('task_list'));

        $client->followRedirect();

        $response = new Response();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $this->assertNotContains('Créer une tâche', $client->getResponse()->getContent());

        //$this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testListAction()
    {
        $client = static::createClient();

        $urlGenerator = $client->getContainer()->get('router');

        $userData = [
            'username' => 'User',
            'password' => '$2y$13$cx7WfZ3C24BccB0a9PuXCeyNCtPsxbMcCdUXh0ARBXap6HXgMiD.u',
            'email' => 'user@orange.fr'
        ];

        $user = new User();
        $user->setUsername($userData['username']);
        $user->setPassword($userData['password']);
        $user->setEmail($userData['email']);

        $manager = $client->getContainer()->get('doctrine.orm.entity_manager');
        $manager->persist($user);
        $manager->flush();

        $this->login($client, $user);

        $client->request(Request::METHOD_GET, $urlGenerator->generate('task_list'));

        $response = new Response();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $this->assertContains('Créer une tâche', $client->getResponse()->getContent());
    }
}