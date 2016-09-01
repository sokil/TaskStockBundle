<?php

namespace Sokil\TaskStockBundle\Notification\Message;

use Sokil\Diff\Change;
use Sokil\Diff\Renderer;
use Sokil\NotificationBundle\Message\MessageInterface;
use Sokil\TaskStockBundle\Entity\Task;
use Sokil\TaskStockBundle\State\TaskStateHandlerBuilder;
use Sokil\UserBundle\Entity\User;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface as TemplateEngineInterface;

class FixtureTaskChangeMessageBuilderTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateFixture()
    {
        // state handler
        $stateConfiguration = yaml_parse('
            id: 0
            states:
                new:
                  label: task_status_new
                  initial: true
                  transitions:
                    to_in_progress:
                      resultingState: in_progress
                      label: task_transiotion_open
                      icon: glyphicon glyphicon-play
                    to_rejected:
                      resultingState: rejected
                      label: task_transiotion_reject
                      icon: glyphicon glyphicon-ban-circle
                in_progress:
                  label: task_status_in_progress
                  transitions:
                    to_resolved:
                      resultingState: resolved
                      label: task_transiotion_resolve
                      icon: glyphicon glyphicon-ok
                    to_rejected:
                      resultingState: rejected
                      label: task_transiotion_reject
                      icon: glyphicon glyphicon-ban-circle
        ');

        $taskStateHandlerBuilder = $this
            ->getMockBuilder(TaskStateHandlerBuilder::class)
            ->disableOriginalConstructor()
            ->setMethods(['getTaskStateConfiguration'])
            ->getMock();

        $taskStateHandlerBuilder
            ->expects($this->once())
            ->method('getTaskStateConfiguration')
            ->will($this->returnValue($stateConfiguration));


        // translator mock
        $translator = $this
            ->getMockBuilder(TranslatorInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['trans', 'transChoice', 'getLocale', 'setLocale'])
            ->getMock();

        $translator
            ->expects($this->any())
            ->method('trans')
            ->will($this->returnArgument(0));

        // template
        $template = $this
            ->getMockBuilder(TemplateEngineInterface::class)
            ->setMethods(['render', 'renderResponse', 'exists', 'supports'])
            ->getMock();

        $template
            ->expects($this->once())
            ->method('render')
            ->with(
                $this->equalTo('TaskStockBundle:EmailMessageProvider:task.change.html.twig'),
                $this->callback(function($argument) {
                    if (empty ($argument['task']) || empty ($argument['user']) || empty ($argument['changes'])) {
                        return false;
                    }

                    if (!($argument['task'] instanceof Task)) {
                        return false;
                    }

                    if (!($argument['user'] instanceof User)) {
                        return false;
                    }

                    if (!is_array($argument['changes'])) {
                        return false;
                    }

                    $this->assertSame(array (
                        'project' =>
                            array (
                                'oldValue' => 'Old project',
                                'newValue' => 'New project',
                                'diff' => '<del>Old project</del>' . PHP_EOL . '<ins>New project</ins>',
                            ),
                        'name' =>
                            array (
                                'oldValue' => 'Old task name',
                                'newValue' => 'New task name',
                                'diff' => '<del>Old task name</del>' . PHP_EOL . '<ins>New task name</ins>',
                            ),
                        'amount' =>
                            array (
                                'oldValue' => 10,
                                'newValue' => 42.200000000000003,
                                'diff' => '<del>10</del>' . PHP_EOL . '<ins>42.2</ins>',
                            ),
                        'date' =>
                            array (
                                'oldValue' => '14.10.2014 00:00:00',
                                'newValue' => '14.10.2014 00:00:00',
                                'diff' => '14.10.2014 00:00:00',
                            ),
                        'owner' =>
                            array (
                                'oldValue' => NULL,
                                'newValue' => 'User name',
                                'diff' => '<del></del>' . PHP_EOL . '<ins>User name</ins>',
                            ),
                        'assignee' =>
                            array (
                                'oldValue' => NULL,
                                'newValue' => 'User name',
                                'diff' => '<del></del>' . PHP_EOL . '<ins>User name</ins>',
                            ),
                        'category' =>
                            array (
                                'oldValue' => NULL,
                                'newValue' => NULL,
                                'diff' => '',
                            ),
                        'location' =>
                            array (
                                'oldValue' => 'Old location',
                                'newValue' => 'New location',
                                'diff' => '<del>Old location</del>' . PHP_EOL . '<ins>New location</ins>',
                            ),
                        'description' =>
                            array (
                                'oldValue' => 'Old task description',
                                'newValue' => 'New task description',
                                'diff' => '<del>Old task description</del>' . PHP_EOL . '<ins>New task description</ins>',
                            ),
                        'stateName' =>
                            array (
                                'oldValue' => 'task_status_new',
                                'newValue' => 'task_status_in_progress',
                                'diff' => '<del>task_status_new</del>' . PHP_EOL . '<ins>task_status_in_progress</ins>',
                            ),
                    ), $argument['changes']);

                    return true;
                })
            );

        // message builder
        $messageBuilder = new TaskChangeMessageBuilder(
            $template,
            $translator,
            new Renderer(),
            $taskStateHandlerBuilder
        );

        $messageBuilderFixture = new FixtureTaskChangeMessageBuilder($messageBuilder);

        // message
        $message = $messageBuilderFixture->createFixture();

        // test
        $this->assertInstanceOf(TaskChangeMessage::class, $message);
        $message->getBody();

    }
}