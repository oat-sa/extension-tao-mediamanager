<?php

/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA
 */

declare(strict_types=1);

namespace oat\taoMediaManager\scripts;

use common_report_Report;
use InvalidArgumentException;
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\extension\script\ScriptAction;
use oat\tao\model\taskQueue\QueueDispatcherInterface;
use oat\tao\model\taskQueue\Task\CallbackTaskInterface;
use oat\tao\model\taskQueue\TaskLogActionTrait;
use oat\tao\model\taskQueue\TaskLogInterface;
use oat\taoMediaManager\model\relation\task\ItemToMediaRelationshipTask;
use oat\taoMediaManager\model\relation\task\MediaToMediaRelationTask;
use RuntimeException;
use Throwable;

class RebuildRelationship extends ScriptAction
{
    use OntologyAwareTrait;
    use TaskLogActionTrait;

    private const TARGET_ITEMS = 'item';
    private const TARGET_MEDIA = 'media';

    protected function provideOptions()
    {
        return [
            'chunkSize' => [
                'prefix' => 'c',
                'longPrefix' => 'chunkSize',
                'required' => false,
                'cast' => 'integer',
                'defaultValue' => 10000,
                'description' => 'Amount of `statements` rows provided into taskqueue to proceeded with'
            ],
            'pickSize' => [
                'prefix' => 'p',
                'longPrefix' => 'pickSize',
                'required' => false,
                'cast' => 'integer',
                'defaultValue' => 0,
                'description' => 'Amount of items proceed in chunk (for test purposes)'
            ],
            'recoveryMode' => [
                'prefix' => 'r',
                'longPrefix' => 'recoveryMode',
                'required' => false,
                'cast' => 'boolean',
                'defaultValue' => false,
                'description' => 'Starts recovery by resuming from the last chunk'
            ],
            'repeat' => [
                'prefix' => 'rp',
                'longPrefix' => 'repeat',
                'required' => false,
                'cast' => 'boolean',
                'defaultValue' => true,
                'description' => 'Scan all the records to the very end'
            ],
            'start' => [
                'prefix' => 's',
                'longPrefix' => 'start',
                'required' => false,
                'cast' => 'integer',
                'defaultValue' => 1,
                'description' => 'Sliding window start range'
            ],
            'queue' => [
                'prefix' => 'q',
                'longPrefix' => 'queue',
                'required' => false,
                'cast' => 'string',
                'defaultValue' => 'default',
                'description' => 'Define task queue broker name to work at'
            ],

            'target' => [
                'prefix' => 't',
                'longPrefix' => 'target',
                'required' => false,
                'cast' => 'string',
                'defaultValue' => self::TARGET_ITEMS,
                'description' => sprintf(
                    'Define what type of triples we are working with. Allowed mode are "%s" and "%s"',
                    self::TARGET_ITEMS,
                    self::TARGET_MEDIA
                )
            ],
        ];
    }

    protected function provideDescription()
    {
    }

    protected function run()
    {
        $startedAt = time();

        $chunkSize = $this->getOption('chunkSize');
        $start = $this->getOption('start');
        $isRecovery = $this->getOption('recoveryMode');
        $pickSize = $this->getOption('pickSize');
        $repeat = $this->getOption('repeat');
        $queue = $this->getOption('queue');

        $taskClass = $this->detectTargetClass($this->getOption('target'));

        $this->addTaskBroker($queue);

        if ($isRecovery) {
            $start = $this->getLastChunkStart();
        }

        try {
            $task = $this->spawnTask($start, $chunkSize, $pickSize, $taskClass, $repeat);

            $taskLogEntity = $this->getTaskLogEntity($task->getId());
            $taskReport = $taskLogEntity->getReport();

            if (0 === strcasecmp($taskLogEntity->getStatus()->getLabel(), TaskLogInterface::STATUS_FAILED)) {
                throw new RuntimeException('task failed please refer logs');
            }
        } catch (Throwable $e) {
            return common_report_Report::createFailure($e->getMessage());
        }

        $report = common_report_Report::createSuccess(
            sprintf(
                "Operation against took %fsec and %dMb",
                (time() - $startedAt),
                memory_get_peak_usage(true) / 1024 / 1024
            )
        );

        if (null !== $taskReport) {
            $report->add($taskReport);
        }

        return $report;
    }

    protected function provideUsage()
    {
        return [
            'prefix' => 'h',
            'longPrefix' => 'help',
            'description' => 'Prints the help.'
        ];
    }

    private function spawnTask(
        $start,
        int $chunkSize,
        int $pickSize,
        string $taskClass,
        bool $repeat = true
    ): CallbackTaskInterface {
        /** @var QueueDispatcherInterface $queueDispatcher */
        $queueDispatcher = $this->getServiceLocator()->get(QueueDispatcherInterface::SERVICE_ID);
        return $queueDispatcher->createTask(
            new $taskClass(),
            [$start, $chunkSize, $pickSize, $repeat],
            sprintf(
                'Relation recovery for %s started from %s with chunk size %s',
                $taskClass,
                $start,
                $chunkSize
            )
        );
    }

    private function getLastChunkStart(): int
    {
        return 100;
    }

    private function addTaskBroker(string $queue)
    {
    }

    protected function returnJson($data, $httpStatus = 200)
    {
    }

    private function detectTargetClass(string $target): string
    {
        if (self::TARGET_ITEMS === $target) {
            return ItemToMediaRelationshipTask::class;
        }

        if (self::TARGET_ITEMS === $target) {
            return MediaToMediaRelationTask::class;
        }
        throw new InvalidArgumentException('Incorrect target please run script with -h flag');
    }
}
