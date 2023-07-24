<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DefaultControllerTest extends WebTestCase
{
    public function testIndexAction()
    {
        $client = static::createClient();

        $client->request('GET', '/');

        $client->followRedirect();

        $this->assertResponseIsSuccessful();

        $this->assertStringContainsString('To Do List app', $client->getResponse()->getContent());
    }
}
