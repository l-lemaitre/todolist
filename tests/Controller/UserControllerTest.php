<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Tests\Service\NeedLogin;

class UserControllerTest extends WebTestCase
{
    use NeedLogin;

    private $client = null;

    private $manager = null;

    private $user = null;

    public function setUp(): void
    {
        self::bootKernel();

        $this->truncateEntities([
            User::class
        ]);

        $this->client = static::createClient();

        $userData = [
            'username' => 'User',
            'password' => '$2y$13$cx7WfZ3C24BccB0a9PuXCeyNCtPsxbMcCdUXh0ARBXap6HXgMiD.u',
            'email' => 'user@orange.fr'
        ];

        $this->user = new User();
        $this->user->setUsername($userData['username']);
        $this->user->setPassword($userData['password']);
        $this->user->setEmail($userData['email']);

        $this->manager = $this->client->getContainer()->get('doctrine.orm.entity_manager');
        $this->manager->persist($this->user);
        $this->manager->flush();
    }

    public function testListAction()
    {
        $urlGenerator = $this->client->getContainer()->get('router');

        $this->client->request(Request::METHOD_GET, $urlGenerator->generate('user_list'));

        $response = new Response();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $this->assertContains('Liste des utilisateurs', $this->client->getResponse()->getContent());
    }

    public function testCreateAction()
    {
        $urlGenerator = $this->client->getContainer()->get('router');

        $crawler = $this->client->request(Request::METHOD_GET, $urlGenerator->generate('user_create'));

        $buttonCrawlerNode = $crawler->selectButton('Ajouter');

        $form = $buttonCrawlerNode->form([
            'user[username]' => 'User2',
            'user[password][first]' => 'motdepasse',
            'user[password][second]' => 'motdepasse',
            'user[email]' => 'user2@orange.fr'
        ]);

        $this->client->submit($form);

        $formValues = $form->getValues();

        $this->client->followRedirect();

        $response = new Response();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $this->assertContains('L&#039;utilisateur a bien été ajouté.', $this->client->getResponse()->getContent());

        $user = $this->manager->getRepository('AppBundle:User')->find(2);

        $passwordVerification = $this->client->getContainer()->get('security.password_encoder')->isPasswordValid($user, $formValues['user[password][first]'], $user->getSalt());

        $this->assertEquals($formValues['user[username]'], $user->getUsername());
        $this->assertTrue($passwordVerification);
        $this->assertEquals($formValues['user[email]'], $user->getEmail());
    }

    public function testCreateActionBlank()
    {
        $countUsersBeforeTest = $this->getCountUsers();

        $urlGenerator = $this->client->getContainer()->get('router');

        $crawler = $this->client->request(Request::METHOD_GET, $urlGenerator->generate('user_create'));

        $buttonCrawlerNode = $crawler->selectButton('Ajouter');

        $form = $buttonCrawlerNode->form([
            'user[username]' => '',
            'user[password][first]' => '',
            'user[password][second]' => '',
            'user[email]' => ''
        ]);

        $this->client->submit($form);

        $formValues = $form->getValues();

        $response = new Response();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $this->assertNotContains('L&#039;utilisateur a bien été ajouté.', $this->client->getResponse()->getContent());

        $countUsersAfterTest = $this->getCountUsers();

        $this->assertEquals($countUsersBeforeTest, $countUsersAfterTest);
    }

    public function testCreateActionBeyondMaxFields()
    {
        $countUsersBeforeTest = $this->getCountUsers();

        $urlGenerator = $this->client->getContainer()->get('router');

        $crawler = $this->client->request(Request::METHOD_GET, $urlGenerator->generate('user_create'));

        $buttonCrawlerNode = $crawler->selectButton('Ajouter');

        $form = $buttonCrawlerNode->form([
            'user[username]' => 'Nullamnullaturpisornaresedex',
            'user[password][first]' => 'Vestibulum et euismod erat. Maecenas porta mattis interdum. Fusce non.',
            'user[password][second]' => 'Vestibulum et euismod erat. Maecenas porta mattis interdum. Fusce non.',
            'user[email]' => 'aeneanluctusmagnavelportalaoreetdiamvelitluctusjusto@gmail.com'
        ]);

        $this->client->submit($form);

        $formValues = $form->getValues();

        $response = new Response();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $this->assertNotContains('L&#039;utilisateur a bien été ajouté.', $this->client->getResponse()->getContent());

        $countUsersAfterTest = $this->getCountUsers();

        $this->assertEquals($countUsersBeforeTest, $countUsersAfterTest);
    }

    public function testEditAction()
    {
        $urlGenerator = $this->client->getContainer()->get('router');

        $crawler = $this->client->request(Request::METHOD_GET, $urlGenerator->generate('user_edit', ['id' => 1]));

        $buttonCrawlerNode = $crawler->selectButton('Modifier');

        $form = $buttonCrawlerNode->form([
            'user[username]' => 'Ludovic Edit',
            'user[password][first]' => 'motdepasseedit',
            'user[password][second]' => 'motdepasseedit',
            'user[email]' => 'ludoviclemaitreedit@orange.fr'
        ]);

        $this->client->submit($form);

        $formValues = $form->getValues();

        $this->client->followRedirect();

        $response = new Response();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $this->assertContains('L&#039;utilisateur a bien été modifié.', $this->client->getResponse()->getContent());

        $user = $this->manager->getRepository('AppBundle:User')->find(1);

        $passwordVerification = $this->client->getContainer()->get('security.password_encoder')->isPasswordValid($user, $formValues['user[password][first]'], $user->getSalt());

        $this->assertEquals($formValues['user[username]'], $user->getUsername());
        $this->assertTrue($passwordVerification);
        $this->assertEquals($formValues['user[email]'], $user->getEmail());
    }

    public function testEditActionBlank()
    {
        $urlGenerator = $this->client->getContainer()->get('router');

        $crawler = $this->client->request(Request::METHOD_GET, $urlGenerator->generate('user_edit', ['id' => 1]));

        $buttonCrawlerNode = $crawler->selectButton('Modifier');

        $form = $buttonCrawlerNode->form([
            'user[username]' => '',
            'user[password][first]' => '',
            'user[password][second]' => '',
            'user[email]' => ''
        ]);

        $this->client->submit($form);

        $formValues = $form->getValues();

        $response = new Response();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $this->assertNotContains('L&#039;utilisateur a bien été modifié.', $this->client->getResponse()->getContent());

        $user = $this->manager->getRepository('AppBundle:User')->find(1);

        $passwordVerification = $this->client->getContainer()->get('security.password_encoder')->isPasswordValid($user, $formValues['user[password][first]'], $user->getSalt());

        $this->assertNotEmpty($user->getUsername());
        $this->assertFalse($passwordVerification);
        $this->assertNotEmpty($user->getEmail());
    }

    public function testEditActionBeyondMaxFields()
    {
        $urlGenerator = $this->client->getContainer()->get('router');

        $crawler = $this->client->request(Request::METHOD_GET, $urlGenerator->generate('user_edit', ['id' => 1]));

        $buttonCrawlerNode = $crawler->selectButton('Modifier');

        $form = $buttonCrawlerNode->form([
            'user[username]' => 'Nullamnullaturpisornaresedex',
            'user[password][first]' => 'Vestibulum et euismod erat. Maecenas porta mattis interdum. Fusce non.',
            'user[password][second]' => 'Vestibulum et euismod erat. Maecenas porta mattis interdum. Fusce non.',
            'user[email]' => 'aeneanluctusmagnavelportalaoreetdiamvelitluctusjusto@gmail.com'
        ]);

        $this->client->submit($form);

        $formValues = $form->getValues();

        $response = new Response();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $this->assertNotContains('L&#039;utilisateur a bien été modifié.', $this->client->getResponse()->getContent());

        $user = $this->manager->getRepository('AppBundle:User')->find(1);

        $passwordVerification = $this->client->getContainer()->get('security.password_encoder')->isPasswordValid($user, $formValues['user[password][first]'], $user->getSalt());

        $this->assertNotEquals($formValues['user[username]'], $user->getUsername());
        $this->assertFalse($passwordVerification);
        $this->assertNotEquals($formValues['user[email]'], $user->getEmail());
    }

    public function getCountUsers()
    {
        $queryBuilder = $this->manager->createQueryBuilder();
        $queryBuilder->select('count(user.id)');
        $queryBuilder->from('AppBundle:User','user');

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    private function getEntityManager()
    {
        return self::$kernel->getContainer()
            ->get('doctrine')
            ->getManager();
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
        $userEntity = $this->manager->merge($this->user);
        $this->manager->remove($userEntity);
        $this->manager->flush();
    }
}