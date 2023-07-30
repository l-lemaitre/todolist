<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManager;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserControllerTest extends WebTestCase
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

    public function testListAction()
    {
        $this->client->loginUser($this->user);

        $urlGenerator = $this->client->getContainer()->get('router');

        $crawler = $this->client->request(Request::METHOD_GET, $urlGenerator->generate('app_user_list'));

        $this->assertResponseIsSuccessful();

        $this->assertSelectorTextContains('h1', 'Liste des utilisateurs');

        $countUsers = $this->getCountUsers();

        $this->assertCount($countUsers, $crawler->filter('.btn.btn-success.btn-sm'));
    }

    public function testListActionNotLoggedIn()
    {
        $urlGenerator = $this->client->getContainer()->get('router');

        $crawler = $this->client->request(Request::METHOD_GET, $urlGenerator->generate('app_user_list'));

        $this->client->followRedirect();

        $this->assertResponseIsSuccessful();

        $this->assertStringNotContainsString('Liste des utilisateurs',
            $this->client->getResponse()->getContent());

        $countUsers = $this->getCountUsers();

        $this->assertNotCount($countUsers, $crawler->filter('.btn-success'));
    }

    public function testListActionNotAuthorizedUserRoleUser()
    {
        $userRoleUSer = $this->entityManager->getRepository(User::class)->find(2);

        $this->client->loginUser($userRoleUSer);

        $urlGenerator = $this->client->getContainer()->get('router');

        $this->client->request(Request::METHOD_GET, $urlGenerator->generate('app_user_list'));

        $this->client->followRedirect();

        $this->assertResponseIsSuccessful();

        $this->assertStringContainsString(
            'Accès refusé. Vous n&#039;avez pas les droits suffisants pour afficher la liste des utilisateurs.',
            $this->client->getResponse()->getContent());
    }

    public function testCreateAction()
    {
        $urlGenerator = $this->client->getContainer()->get('router');

        $crawler = $this->client->request(Request::METHOD_GET, $urlGenerator->generate('app_user_create'));

        $buttonCrawlerNode = $crawler->selectButton('Ajouter');

        $form = $buttonCrawlerNode->form([
            'user[username]' => 'User3',
            'user[password][first]' => 'motdepasse',
            'user[password][second]' => 'motdepasse',
            'user[email]' => 'user3@orange.fr'
        ]);

        $this->client->submit($form);

        $formValues = $form->getValues();

        $this->client->followRedirects();

        $this->assertResponseRedirects();

        $user = $this->entityManager->getRepository(User::class)->find(3);

        $passwordVerification = $this->client->getContainer()->get(UserPasswordHasherInterface::class)
            ->isPasswordValid($user, $formValues['user[password][first]']);

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

        $this->assertStringNotContainsString('L&#039;utilisateur a bien été ajouté.',
            $this->client->getResponse()->getContent());

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

        $this->assertStringNotContainsString('L&#039;utilisateur a bien été ajouté.',
            $this->client->getResponse()->getContent());

        $countUsersAfterTest = $this->getCountUsers();

        $this->assertEquals($countUsersBeforeTest, $countUsersAfterTest);
    }

    public function testCreateActionNotAuthorizedUserRoleUser()
    {
        $userRoleUSer = $this->entityManager->getRepository(User::class)->find(2);

        $this->client->loginUser($userRoleUSer);

        $urlGenerator = $this->client->getContainer()->get('router');

        $this->client->request(Request::METHOD_GET, $urlGenerator->generate('app_user_create'));

        $this->client->followRedirects();

        $this->assertResponseRedirects();

        $this->assertStringNotContainsString('L&#039;utilisateur a bien été ajouté.',
            $this->client->getResponse()->getContent());
    }

    public function testEditAction()
    {
        $this->client->loginUser($this->user);

        $urlGenerator = $this->client->getContainer()->get('router');

        $crawler = $this->client->request(Request::METHOD_GET, $urlGenerator->generate('app_user_edit',
            ['id' => $this->user->getId()]));

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

        $this->assertStringContainsString('L&#039;utilisateur a bien été modifié.',
            $this->client->getResponse()->getContent());

        $user = $this->entityManager->getRepository(User::class)->find($this->user->getId());

        $passwordVerification = $this->client->getContainer()->get(UserPasswordHasherInterface::class)
            ->isPasswordValid($user, $formValues['user[password][first]']);

        $this->assertEquals($formValues['user[username]'], $user->getUsername());
        $this->assertTrue($passwordVerification);
        $this->assertEquals($formValues['user[email]'], $user->getEmail());
    }

    public function testEditActionBlank()
    {
        $this->client->loginUser($this->user);

        $urlGenerator = $this->client->getContainer()->get('router');

        $crawler = $this->client->request(Request::METHOD_GET, $urlGenerator->generate('app_user_edit',
            ['id' => $this->user->getId()]));

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

        $this->assertStringNotContainsString('L&#039;utilisateur a bien été modifié.',
            $this->client->getResponse()->getContent());

        $user = $this->entityManager->getRepository(User::class)->find($this->user->getId());

        $passwordVerification = $this->client->getContainer()->get(UserPasswordHasherInterface::class)
            ->isPasswordValid($user, $formValues['user[password][first]']);

        $this->assertNotEmpty($user->getUsername());
        $this->assertFalse($passwordVerification);
        $this->assertNotEmpty($user->getEmail());
    }

    public function testEditActionBeyondMaxFields()
    {
        $this->client->loginUser($this->user);

        $urlGenerator = $this->client->getContainer()->get('router');

        $crawler = $this->client->request(Request::METHOD_GET, $urlGenerator->generate('app_user_edit',
            ['id' => $this->user->getId()]));

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

        $this->assertStringNotContainsString('L&#039;utilisateur a bien été modifié.',
            $this->client->getResponse()->getContent());

        $user = $this->entityManager->getRepository(User::class)->find($this->user->getId());

        $passwordVerification = $this->client->getContainer()->get(UserPasswordHasherInterface::class)
            ->isPasswordValid($user, $formValues['user[password][first]']);

        $this->assertNotEquals($formValues['user[username]'], $user->getUsername());
        $this->assertFalse($passwordVerification);
        $this->assertNotEquals($formValues['user[email]'], $user->getEmail());
    }

    public function testEditActionNotAuthorizedUserRoleUser()
    {
        $userRoleUSer = $this->entityManager->getRepository(User::class)->find(2);

        $this->client->loginUser($userRoleUSer);

        $urlGenerator = $this->client->getContainer()->get('router');

        $this->client->request(Request::METHOD_GET, $urlGenerator->generate('app_user_edit',
            ['id' => $this->user->getId()]));

        $this->client->followRedirect();

        $this->assertResponseIsSuccessful();

        $this->assertStringContainsString(
            'Accès refusé. L&#039;utilisateur ne peut pas être modifié.',
            $this->client->getResponse()->getContent());
    }

    public function testDeleteAction()
    {
        $this->client->loginUser($this->user);

        $urlGenerator = $this->client->getContainer()->get('router');

        $this->client->request(Request::METHOD_GET, $urlGenerator->generate('app_user_delete', ['id' => 2]));

        $this->client->followRedirect();

        $this->assertResponseIsSuccessful();

        $this->assertStringContainsString('L&#039;utilisateur a bien été supprimé.',
            $this->client->getResponse()->getContent());

        $userRemoved = $this->entityManager->getRepository(User::class)->find(2);

        $this->assertNull($userRemoved);
    }

    public function testDeleteActionNotAuthorizedUserRoleUser()
    {
        $userRoleUSer = $this->entityManager->getRepository(User::class)->find(2);

        $this->client->loginUser($userRoleUSer);

        $urlGenerator = $this->client->getContainer()->get('router');

        $this->client->request(Request::METHOD_GET,
            $urlGenerator->generate('app_user_delete', ['id' => $this->user->getId()]));

        $this->client->followRedirect();

        $this->assertResponseIsSuccessful();

        $this->assertStringContainsString('Accès refusé. L&#039;utilisateur ne peut pas être supprimé.',
            $this->client->getResponse()->getContent());

        $userRemoved = $this->entityManager->getRepository(User::class)->find($this->user->getId());

        $this->assertNotNull($userRemoved);
    }

    public function getCountUsers()
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder->select('count(user.id)');
        $queryBuilder->from(User::class,'user');

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