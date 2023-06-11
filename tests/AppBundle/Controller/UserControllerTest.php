<?php

namespace Tests\AppBundle\Controller;

use AppBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\AppBundle\NeedLogin;

class UserControllerTest extends WebTestCase
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

        //$this->login($client, $this->user);

        $urlGenerator = $client->getContainer()->get('router');

        $client->request(Request::METHOD_GET, $urlGenerator->generate('user_list'));

        $response = new Response();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $this->assertContains('Liste des utilisateurs', $client->getResponse()->getContent());
    }

    public function testCreateAction()
    {
        $client = static::createClient();

        //$this->login($client, $this->user);

        $urlGenerator = $client->getContainer()->get('router');

        $crawler = $client->request(Request::METHOD_GET, $urlGenerator->generate('user_create'));

        $buttonCrawlerNode = $crawler->selectButton('Ajouter');

        $form = $buttonCrawlerNode->form([
            'user[username]' => 'User2',
            'user[password][first]' => 'motdepasse',
            'user[password][second]' => 'motdepasse',
            'user[email]' => 'user2@orange.fr'
        ]);

        $client->submit($form);

        $formValues = $form->getValues();

        $client->followRedirect();

        $response = new Response();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $this->assertContains('L&#039;utilisateur a bien été ajouté.', $client->getResponse()->getContent());

        $this->assertEquals('User2', $formValues['user[username]']);
        $this->assertEquals('motdepasse', $formValues['user[password][first]']);
        $this->assertEquals('motdepasse', $formValues['user[password][second]']);
        $this->assertEquals('user2@orange.fr', $formValues['user[email]']);
    }

    public function testCreateActionBlank()
    {
        $client = static::createClient();

        $urlGenerator = $client->getContainer()->get('router');

        $crawler = $client->request(Request::METHOD_GET, $urlGenerator->generate('user_create'));

        $buttonCrawlerNode = $crawler->selectButton('Ajouter');

        $form = $buttonCrawlerNode->form([
            'user[username]' => '',
            'user[password][first]' => '',
            'user[password][second]' => '',
            'user[email]' => ''
        ]);

        $client->submit($form);

        $formValues = $form->getValues();

        $response = new Response();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $this->assertNotContains('L&#039;utilisateur a bien été ajouté.', $client->getResponse()->getContent());

        $this->assertEquals('', $formValues['user[username]']);
        $this->assertEquals('', $formValues['user[password][first]']);
        $this->assertEquals('', $formValues['user[password][second]']);
        $this->assertEquals('', $formValues['user[email]']);
    }

    public function testCreateActionMaxFields()
    {
        $client = static::createClient();

        $urlGenerator = $client->getContainer()->get('router');

        $crawler = $client->request(Request::METHOD_GET, $urlGenerator->generate('user_create'));

        $buttonCrawlerNode = $crawler->selectButton('Ajouter');

        $form = $buttonCrawlerNode->form([
            'user[username]' => 'Nullamnullaturpisornaresedex',
            'user[password][first]' => 'Vestibulum et euismod erat. Maecenas porta mattis interdum. Fusce non.',
            'user[password][second]' => 'Vestibulum et euismod erat. Maecenas porta mattis interdum. Fusce non.',
            'user[email]' => 'aeneanluctusmagnavelportalaoreetdiamvelitluctusjusto@gmail.com'
        ]);

        $client->submit($form);

        $formValues = $form->getValues();

        $response = new Response();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $this->assertNotContains('L&#039;utilisateur a bien été modifié.', $client->getResponse()->getContent());

        $this->assertEquals('Nullamnullaturpisornaresedex', $formValues['user[username]']);
        $this->assertEquals('Vestibulum et euismod erat. Maecenas porta mattis interdum. Fusce non.', $formValues['user[password][first]']);
        $this->assertEquals('Vestibulum et euismod erat. Maecenas porta mattis interdum. Fusce non.', $formValues['user[password][second]']);
        $this->assertEquals('aeneanluctusmagnavelportalaoreetdiamvelitluctusjusto@gmail.com', $formValues['user[email]']);
    }

    public function testEditAction()
    {
        $client = static::createClient();

        //$this->login($client, $this->user);

        $urlGenerator = $client->getContainer()->get('router');

        $crawler = $client->request(Request::METHOD_GET, $urlGenerator->generate('user_edit', ['id' => 3]));

        $buttonCrawlerNode = $crawler->selectButton('Modifier');

        $form = $buttonCrawlerNode->form([
            'user[username]' => 'Ludovic Edit',
            'user[password][first]' => 'motdepasse',
            'user[password][second]' => 'motdepasse',
            'user[email]' => 'ludoviclemaitreedit@orange.fr'
        ]);

        $client->submit($form);

        $formValues = $form->getValues();

        $client->followRedirect();

        $response = new Response();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $this->assertContains('L&#039;utilisateur a bien été modifié.', $client->getResponse()->getContent());

        $this->assertEquals('Ludovic Edit', $formValues['user[username]']);
        $this->assertEquals('motdepasse', $formValues['user[password][first]']);
        $this->assertEquals('motdepasse', $formValues['user[password][second]']);
        $this->assertEquals('ludoviclemaitreedit@orange.fr', $formValues['user[email]']);
    }

    public function testEditActionBlank()
    {
        $client = static::createClient();

        $urlGenerator = $client->getContainer()->get('router');

        $crawler = $client->request(Request::METHOD_GET, $urlGenerator->generate('user_edit', ['id' => 3]));

        $buttonCrawlerNode = $crawler->selectButton('Modifier');

        $form = $buttonCrawlerNode->form([
            'user[username]' => '',
            'user[password][first]' => '',
            'user[password][second]' => '',
            'user[email]' => ''
        ]);

        $client->submit($form);

        $formValues = $form->getValues();

        $response = new Response();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $this->assertNotContains('L&#039;utilisateur a bien été modifié.', $client->getResponse()->getContent());

        $this->assertEquals('', $formValues['user[username]']);
        $this->assertEquals('', $formValues['user[password][first]']);
        $this->assertEquals('', $formValues['user[password][second]']);
        $this->assertEquals('', $formValues['user[email]']);
    }

    public function testEditActionMaxFields()
    {
        $client = static::createClient();

        $urlGenerator = $client->getContainer()->get('router');

        $crawler = $client->request(Request::METHOD_GET, $urlGenerator->generate('user_edit', ['id' => 3]));

        $buttonCrawlerNode = $crawler->selectButton('Modifier');

        $form = $buttonCrawlerNode->form([
            'user[username]' => 'Nullamnullaturpisornaresedex',
            'user[password][first]' => 'Vestibulum et euismod erat. Maecenas porta mattis interdum. Fusce non.',
            'user[password][second]' => 'Vestibulum et euismod erat. Maecenas porta mattis interdum. Fusce non.',
            'user[email]' => 'aeneanluctusmagnavelportalaoreetdiamvelitluctusjusto@gmail.com'
        ]);

        $client->submit($form);

        $formValues = $form->getValues();

        $response = new Response();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $this->assertNotContains('L&#039;utilisateur a bien été modifié.', $client->getResponse()->getContent());

        $this->assertEquals('Nullamnullaturpisornaresedex', $formValues['user[username]']);
        $this->assertEquals('Vestibulum et euismod erat. Maecenas porta mattis interdum. Fusce non.', $formValues['user[password][first]']);
        $this->assertEquals('Vestibulum et euismod erat. Maecenas porta mattis interdum. Fusce non.', $formValues['user[password][second]']);
        $this->assertEquals('aeneanluctusmagnavelportalaoreetdiamvelitluctusjusto@gmail.com', $formValues['user[email]']);
    }

    public function tearDown()
    {
        $userEntity = $this->manager->merge($this->user);
        $this->manager->remove($userEntity);
        $this->manager->flush();
    }
}