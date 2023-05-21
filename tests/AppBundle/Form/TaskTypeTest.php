<?php

namespace Tests\AppBundle\Form;

use AppBundle\Entity\Task;
use AppBundle\Form\TaskType;
use Symfony\Component\Form\Extension\Core\CoreExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\ConstraintValidatorFactory;
use Symfony\Component\Validator\ValidatorBuilder;

class TaskTypeTest extends TypeTestCase
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
        $task = new Task();
        $form = $this->factory->create(TaskType::class, $task);

        $data = ['title' => 'Courses', 'content' => 'Aller au supermarché.'];

        $form->submit($data);

        $this->assertTrue($form->isValid());

        $this->assertEquals($data['title'], $task->getTitle());
        $this->assertEquals($data['content'], $task->getContent());
    }

    public function testFormValidityBlank()
    {
        $task = new Task();
        $form = $this->factory->create(TaskType::class, $task);

        $data = ['title' => '', 'content' => ''];

        $form->submit($data);

        $this->assertFalse($form->isValid());

        $this->assertEquals($data['title'], $task->getTitle());
        $this->assertEquals($data['content'], $task->getContent());
    }

    public function testFormValidityMaxTitle()
    {
        $task = new Task();
        $form = $this->factory->create(TaskType::class, $task);

        $data = ['title' => 'Quisque lorem turpis, gravida eget consequat at, tristique eget enim. Donec tristique tincidunt enim ut ultrices. Nunc egestas, enim vel facilisis sagittis, nisi justo feugiat sem, suscipit pellentesque orci nulla ac nisl. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos.',
            'content' => 'Aller au supermarché.'];

        $form->submit($data);

        $this->assertFalse($form->isValid());

        $this->assertEquals($data['title'], $task->getTitle());
        $this->assertEquals($data['content'], $task->getContent());
    }
}