<?php

namespace Tests\AppBundle\Form;

use AppBundle\Entity\User;
use AppBundle\Form\UserType;
use Symfony\Component\Form\Extension\Core\CoreExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\ConstraintValidatorFactory;
use Symfony\Component\Validator\ValidatorBuilder;

class UserTypeTest extends TypeTestCase
{
    public function getExtensions()
    {
        $validator = (new ValidatorBuilder())
            ->addYamlMapping('src/AppBundle/Resources/config/validation.yml')
            ->setConstraintValidatorFactory(new ConstraintValidatorFactory())
            ->getValidator();

        $extensions[] = new CoreExtension();
        $extensions[] = new ValidatorExtension($validator);

        return $extensions;
    }
    
    public function testFormValidityOk()
    {
        $user = new User();
        $form = $this->factory->create(UserType::class, $user);

        $data = ['username' => 'User', 'password' => [
            'first' => 'motdepasse',
            'second' => 'motdepasse'
        ], 'email' => 'user@orange.fr'];

        $form->submit($data);

        //$this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());

        $this->assertEquals($data['username'], $user->getUsername());
        $this->assertEquals($data['password']['first'], $user->getPassword());
        $this->assertEquals($data['email'], $user->getEmail());
    }

    public function testFormValidityBlank()
    {
        $user = new User();
        $form = $this->factory->create(UserType::class, $user);

        $data = [
            'username' => '',
            'password' => [
                'first' => '',
                'second' => ''
            ],
            'email' => ''
        ];

        $form->submit($data);

        //$this->assertTrue($form->isSynchronized());
        $this->assertFalse($form->isValid());

        $this->assertEquals($data['username'], $user->getUsername());
        $this->assertEquals($data['password']['first'], $user->getPassword());
        $this->assertEquals($data['email'], $user->getEmail());
    }

    public function testFormValidityMaxFields()
    {
        $user = new User();
        $form = $this->factory->create(UserType::class, $user);

        $data = [
            'username' => 'Nullamnullaturpisornaresedex',
            'password' => [
                'first' => 'Vestibulum et euismod erat. Maecenas porta mattis interdum. Fusce non.',
                'second' => 'Vestibulum et euismod erat. Maecenas porta mattis interdum. Fusce non.'
            ],
            'email' => 'aeneanluctusmagnavelportalaoreetdiamvelitluctusjusto@gmail.com'
        ];

        $form->submit($data);

        //$this->assertTrue($form->isSynchronized());
        $this->assertFalse($form->isValid());

        $this->assertEquals($data['username'], $user->getUsername());
        $this->assertEquals($data['password']['first'], $user->getPassword());
        $this->assertEquals($data['email'], $user->getEmail());
    }
}