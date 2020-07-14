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
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\extension\script\ScriptAction;
use oat\tao\model\taskQueue\QueueDispatcherInterface;
use oat\tao\model\taskQueue\Task\CallbackTaskInterface;
use oat\taoMediaManager\model\relation\task\ItemToMedia;

class RebuildRelationship extends ScriptAction
{
    use OntologyAwareTrait;

    protected function provideOptions()
    {
        return [
            'chunkSize' => [
                'prefix' => 'c',
                'longPrefix' => 'chunkSize',
                'required' => false,
                'cast' => 'integer',
                'defaultValue' => 100,
                'description' => 'Amount of items provided into taskqueue to procceed with'
            ],
            'recoveryMode' => [
                'prefix' => 'r',
                'longPrefix' => 'recoveryMode',
                'required' => false,
                'cast' => 'boolean',
                'defaultValue' => false,
                'description' => 'Starts recovery by resuming from the last chunk'
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
            'end' => [
                'prefix' => 'e',
                'longPrefix' => 'end',
                'required' => false,
                'cast' => 'integer',
                'defaultValue' => 10000,
                'description' => 'Sliding window end range'
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
        $end = $this->getOption('end');
        $queue = $this->getOption('queue');
        $isRecovery = $this->getOption('recoveryMode');

        $this->addTaskBroker($queue);

        if ($isRecovery) {
            $start = $this->getLastChunkStart();
        }

        $taskClass = ItemToMedia::class;

        try {
            $this->spawnTask($start, $end, $chunkSize, $taskClass);
        } catch (\Throwable $e) {
            return common_report_Report::createFailure(
                $e->getMessage()
            );
        }

        return common_report_Report::createSuccess(
            sprintf(
                "Operation against %d items took %fsec and %dMb",
                1,
                (time() - $startedAt) / 60,
                memory_get_peak_usage(true) / 1024 / 1024
            )
        );
    }

    protected function provideUsage()
    {
        return [
            'prefix' => 'h',
            'longPrefix' => 'help',
            'description' => 'Prints the help.'
        ];
    }

    private function spawnTask($start, $end, int $chunkSize, string $taskClass): CallbackTaskInterface
    {
        /** @var QueueDispatcherInterface $queueDispatcher */
        $queueDispatcher = $this->getServiceLocator()->get(QueueDispatcherInterface::SERVICE_ID);
        return $queueDispatcher->createTask(
            new $taskClass(),
            [$start, $end, $chunkSize],
            sprintf(
                'Relation recovery for %s started from %s to %s',
                $taskClass,
                $start,
                $end
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
}
