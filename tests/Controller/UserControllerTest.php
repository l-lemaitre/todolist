<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserControllerTest extends WebTestCase
{
    private ?KernelBrowser $client = null;

    private $entityManager = null;

    private ?User $user = null;

    public function setUp(): void
    {
        $this->client = static::createClient();

        $this->truncateEntities([
            User::class
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
        $this->entityManager->flush();
    }

    public function testListAction()
    {
        $this->client->loginUser($this->user);

        $urlGenerator = $this->client->getContainer()->get('router');

        $crawler = $this->client->request(Request::METHOD_GET, $urlGenerator->generate('app_user_list'));

        $this->assertResponseIsSuccessful();

        $this->assertSelectorTextContains('h1', 'Liste des utilisateurs');

        $countUsers = $this->getCountUsers();

        $this->assertCount($countUsers, $crawler->filter('.btn-success'));
    }

    public function testListActionNotLoggedIn()
    {
        $urlGenerator = $this->client->getContainer()->get('router');

        $crawler = $this->client->request(Request::METHOD_GET, $urlGenerator->generate('app_user_list'));

        $this->client->followRedirect();

        $this->assertResponseIsSuccessful();

        $this->assertStringNotContainsString('Liste des utilisateurs', $this->client->getResponse()->getContent());

        $countUsers = $this->getCountUsers();

        $this->assertNotCount($countUsers, $crawler->filter('.btn-success'));
    }

    public function testCreateAction()
    {
        $urlGenerator = $this->client->getContainer()->get('router');

        $crawler = $this->client->request(Request::METHOD_GET, $urlGenerator->generate('app_user_create'));

        $buttonCrawlerNode = $crawler->selectButton('Ajouter');

        $form = $buttonCrawlerNode->form([
            'user[username]' => 'User2',
            'user[password][first]' => 'motdepasse',
            'user[password][second]' => 'motdepasse',
            'user[email]' => 'user2@orange.fr'
        ]);

        $this->client->submit($form);

        $formValues = $form->getValues();

        $this->client->followRedirects();

        $this->assertResponseRedirects();

        $user = $this->entityManager->getRepository(User::class)->find(2);

        $passwordVerification = $this->client->getContainer()->get(UserPasswordHasherInterface::class)->isPasswordValid($user, $formValues['user[password][first]']);

        $this->assertEquals($formValues['user[username]'], $user->getUsername());
        $this->assertTrue($passwordVerification);
        $this->assertEquals($formValues['user[email]'], $user->getEmail());
    }

    public function testCreateActionBlank()
    {
        $countUsersBeforeTest = $this->getCountUsers();

        $urlGenerator = $this->client->getContainer()->get('router');

        $crawler = $this->client->request(Request::METHOD_GET, $urlGenerator->generate('app_user_create'));

        $buttonCrawlerNode = $crawler->selectButton('Ajouter');

        $form = $buttonCrawlerNode->form([
            'user[username]' => '',
            'user[password][first]' => '',
            'user[password][second]' => '',
            'user[email]' => ''
        ]);

        $this->client->submit($form);

        $this->assertResponseIsSuccessful();

        $this->assertStringNotContainsString('L&#039;utilisateur a bien été ajouté.', $this->client->getResponse()->getContent());

        $countUsersAfterTest = $this->getCountUsers();

        $this->assertEquals($countUsersBeforeTest, $countUsersAfterTest);
    }

    public function testCreateActionBeyondMaxFields()
    {
        $countUsersBeforeTest = $this->getCountUsers();

        $urlGenerator = $this->client->getContainer()->get('router');

        $crawler = $this->client->request(Request::METHOD_GET, $urlGenerator->generate('app_user_create'));

        $buttonCrawlerNode = $crawler->selectButton('Ajouter');

        $form = $buttonCrawlerNode->form([
            'user[username]' => 'Nullamnullaturpisornaresedex',
            'user[password][first]' => 'Vestibulum et euismod erat. Maecenas porta mattis interdum. Fusce non.',
            'user[password][second]' => 'Vestibulum et euismod erat. Maecenas porta mattis interdum. Fusce non.',
            'user[email]' => 'aeneanluctusmagnavelportalaoreetdiamvelitluctusjusto@gmail.com'
        ]);

        $this->client->submit($form);

        $this->assertResponseIsSuccessful();

        $this->assertStringNotContainsString('L&#039;utilisateur a bien été ajouté.', $this->client->getResponse()->getContent());

        $countUsersAfterTest = $this->getCountUsers();

        $this->assertEquals($countUsersBeforeTest, $countUsersAfterTest);
    }

    public function testEditAction()
    {
        $this->client->loginUser($this->user);

        $urlGenerator = $this->client->getContainer()->get('router');

        $crawler = $this->client->request(Request::METHOD_GET, $urlGenerator->generate('app_user_edit', ['id' => 1]));

        $buttonCrawlerNode = $crawler->selectButton('Modifier');

        $form = $buttonCrawlerNode->form([
            'user[username]' => 'User Edit',
            'user[password][first]' => 'motdepasseedit',
            'user[password][second]' => 'motdepasseedit',
            'user[email]' => 'useredit@orange.fr'
        ]);

        $this->client->submit($form);

        $formValues = $form->getValues();

        $this->client->followRedirect();

        $this->assertResponseIsSuccessful();

        $this->assertStringContainsString('L&#039;utilisateur a bien été modifié.', $this->client->getResponse()->getContent());

        $user = $this->entityManager->getRepository(User::class)->find(1);

        $passwordVerification = $this->client->getContainer()->get(UserPasswordHasherInterface::class)->isPasswordValid($user, $formValues['user[password][first]']);

        $this->assertEquals($formValues['user[username]'], $user->getUsername());
        $this->assertTrue($passwordVerification);
        $this->assertEquals($formValues['user[email]'], $user->getEmail());
    }

    public function testEditActionBlank()
    {
        $this->client->loginUser($this->user);

        $urlGenerator = $this->client->getContainer()->get('router');

        $crawler = $this->client->request(Request::METHOD_GET, $urlGenerator->generate('app_user_edit', ['id' => 1]));

        $buttonCrawlerNode = $crawler->selectButton('Modifier');

        $form = $buttonCrawlerNode->form([
            'user[username]' => '',
            'user[password][first]' => '',
            'user[password][second]' => '',
            'user[email]' => ''
        ]);

        $this->client->submit($form);

        $formValues = $form->getValues();

        $this->assertResponseIsSuccessful();

        $this->assertStringNotContainsString('L&#039;utilisateur a bien été modifié.', $this->client->getResponse()->getContent());

        $user = $this->entityManager->getRepository(User::class)->find(1);

        $passwordVerification = $this->client->getContainer()->get(UserPasswordHasherInterface::class)->isPasswordValid($user, $formValues['user[password][first]']);

        $this->assertNotEmpty($user->getUsername());
        $this->assertFalse($passwordVerification);
        $this->assertNotEmpty($user->getEmail());
    }

    public function testEditActionBeyondMaxFields()
    {
        $this->client->loginUser($this->user);

        $urlGenerator = $this->client->getContainer()->get('router');

        $crawler = $this->client->request(Request::METHOD_GET, $urlGenerator->generate('app_user_edit', ['id' => 1]));

        $buttonCrawlerNode = $crawler->selectButton('Modifier');

        $form = $buttonCrawlerNode->form([
            'user[username]' => 'Nullamnullaturpisornaresedex',
            'user[password][first]' => 'Vestibulum et euismod erat. Maecenas porta mattis interdum. Fusce non.',
            'user[password][second]' => 'Vestibulum et euismod erat. Maecenas porta mattis interdum. Fusce non.',
            'user[email]' => 'aeneanluctusmagnavelportalaoreetdiamvelitluctusjusto@gmail.com'
        ]);

        $this->client->submit($form);

        $formValues = $form->getValues();

        $this->assertResponseIsSuccessful();

        $this->assertStringNotContainsString('L&#039;utilisateur a bien été modifié.', $this->client->getResponse()->getContent());

        $user = $this->entityManager->getRepository(User::class)->find(1);

        $passwordVerification = $this->client->getContainer()->get(UserPasswordHasherInterface::class)->isPasswordValid($user, $formValues['user[password][first]']);

        $this->assertNotEquals($formValues['user[username]'], $user->getUsername());
        $this->assertFalse($passwordVerification);
        $this->assertNotEquals($formValues['user[email]'], $user->getEmail());
    }

    public function testDeleteUserAction()
    {
        $userData = [
            'username' => 'User2',
            'password' => 'motdepasse',
            'email' => 'user2@orange.fr'
        ];
        $user = new User();
        $user->setUsername($userData['username']);
        $user->setPassword($userData['password']);
        $user->setEmail($userData['email']);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->client->loginUser($this->user);

        $urlGenerator = $this->client->getContainer()->get('router');

        $this->client->request(Request::METHOD_GET, $urlGenerator->generate('app_user_delete', ['id' => 2]));

        $this->client->followRedirect();

        $this->assertResponseIsSuccessful();

        $this->assertStringContainsString('L&#039;utilisateur a bien été supprimé.', $this->client->getResponse()->getContent());

        $userRemoved = $this->entityManager->getRepository(User::class)->find(2);

        $this->assertNull($userRemoved);
    }

    public function getCountUsers()
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder->select('count(user.id)');
        $queryBuilder->from(User::class,'user');

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

        $this->entityManager->close();
        $this->entityManager = null;
    }
}