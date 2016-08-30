<?php

namespace Sokil\TaskStockBundle\Notification\Message;

use Sokil\Diff\Change;
use Sokil\Diff\Renderer;
use Sokil\TaskStockBundle\Entity\Task;
use Sokil\TaskStockBundle\State\TaskStateHandlerBuilder;
use Sokil\UserBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface as TemplateEngineInterface;
use Symfony\Component\Translation\TranslatorInterface;

class TaskChangeMessageTest extends \PHPUnit_Framework_TestCase
{
    public function testGetSerializedChangesDataProvider()
    {
        return [
            [
                'name',
                new Change('old name', 'new name'),
                [
                    'oldValue' => 'old name',
                    'newValue' => 'new name',
                    'diff' => '<del>old name</del>' . PHP_EOL . '<ins>new name</ins>',
                ],
            ],
            [
                'date',
                new Change(
                    new \DateTime('2016-01-01 23:20:21'),
                    new \DateTime('2016-08-11 21:14:12')
                ),
                [
                    'oldValue' => '01.01.2016 23:20:21',
                    'newValue' => '11.08.2016 21:14:12',
                    'diff' => '<del>01.01.2016 23:20:21</del>' . PHP_EOL . '<ins>11.08.2016 21:14:12</ins>',
                ],
            ],
            [
                'stateName',
                new Change('new', 'in_progress'),
                [
                    'oldValue' => 'task_status_new',
                    'newValue' => 'task_status_in_progress',
                    'diff' => '<del>task_status_new</del>' . PHP_EOL . '<ins>task_status_in_progress</ins>',
                ],
            ],
        ];
    }

    /**
     * @dataProvider testGetSerializedChangesDataProvider
     */
    public function testGetSerializedChanges(
        $fieldName,
        $change,
        $expectedSerializedChange
    ) {
        // user
        $user = new User();

        // task
        $task = new Task();

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

        // message
        $message = new TaskChangeMessage(
            $this->getMockBuilder(TemplateEngineInterface::class)->getMock(),
            $translator,
            new Renderer(),
            $taskStateHandlerBuilder->build($task),
            $user,
            $task,
            [
                $fieldName => $change,
            ]
        );

        // test
        $messageReflection = new \ReflectionClass($message);
        $getSerializedChangesMethod = $messageReflection->getMethod('getSerializedChanges');
        $getSerializedChangesMethod->setAccessible(true);
        $actualSerializedChanges = $getSerializedChangesMethod->invoke($message, 'uk');

        $this->assertSame(
            [
                $fieldName => $expectedSerializedChange,
            ],
            $actualSerializedChanges
        );
    }
}