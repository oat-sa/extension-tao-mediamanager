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
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoMediaManager\model\relation\task;

use common_persistence_KvDriver;
use common_persistence_sql_QueryIterator;
use common_report_Report;
use Doctrine\DBAL\Connection;
use Iterator;
use oat\generis\model\OntologyAwareTrait;
use oat\generis\model\OntologyRdf;
use oat\generis\persistence\PersistenceManager;
use oat\oatbox\action\Action;
use oat\tao\model\taskQueue\QueueDispatcherInterface;
use oat\tao\model\taskQueue\Task\CallbackTaskInterface;
use oat\tao\model\taskQueue\Task\TaskAwareInterface;
use oat\tao\model\taskQueue\Task\TaskAwareTrait;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

abstract class AbstractRelationshipTask implements Action, ServiceLocatorAwareInterface, TaskAwareInterface
{
    use ServiceLocatorAwareTrait;
    use OntologyAwareTrait;
    use TaskAwareTrait;

    public const CACHE_KEY =  '::_last_known';

    /** @var int */
    protected $affected;
    /** @var int */
    protected $pickSize;
    /** @var common_report_Report */
    protected $anomalies;

    protected function getLastRowNumber(): int
    {
        $persistence = $this->getModel()->getPersistence();
        $platform = $persistence->getPlatForm();
        $query = $platform->getQueryBuilder()
            ->select('MAX(id)')
            ->from('statements');

        $results = $query->execute()->fetchColumn();

        return (int)$results;
    }

    protected function selfRepeat($start, int $chunkSize, int $pickSize, bool $repeat): CallbackTaskInterface
    {
        /** @var QueueDispatcherInterface $queueDispatcher */
        $queueDispatcher = $this->getServiceLocator()->get(QueueDispatcherInterface::SERVICE_ID);
        return $queueDispatcher->createTask(
            new static(),
            [$start, $chunkSize, $pickSize, $repeat],
            sprintf(
                'Relation recovery for %s started from %s with chunk size of %s',
                self::class,
                $start,
                $chunkSize
            )
        );
    }

    protected function getIterator(array $itemClasses, $start, $end): common_persistence_sql_QueryIterator
    {
        $persistence = $this->getModel()->getPersistence();

        $query = 'SELECT id, subject FROM statements WHERE (id BETWEEN :start AND :end) AND predicate = :predicate AND object IN (:class) ORDER BY id';
        $params = [
            'start' => $start,
            'end' => $end,
            'predicate' => OntologyRdf::RDF_TYPE,
            'class' => array_keys($itemClasses)
        ];

        $types['class'] = Connection::PARAM_STR_ARRAY;

        return new common_persistence_sql_QueryIterator($persistence, $query, $params, $types);
    }

    public function __invoke($params)
    {
        if (count($params) < 4) {
            throw new common_exception_MissingParameter();
        }
        $start = array_shift($params);
        $chunkSize = array_shift($params);
        $this->pickSize = $pickSize = array_shift($params);
        $repeat = (bool)array_shift($params);

        $max = $this->getLastRowNumber();

        $this->keepCurrentPosition($start);

        $end = $start + $chunkSize;

        if ($end >= $max) {
            $end = $max;
        }

        $itemClasses = $this->getTargetClasses();

        $iterator = $this->getIterator($itemClasses, $start, $end);

        $this->initAnomaliesCollector();

        iterator_apply(
            $iterator,
            [$this, 'applyProcessor'],
            [$iterator, $pickSize, $this->affected]
        );

        if ($repeat) {
            $nStart = $end + 1;
            if ($nStart + $chunkSize <= $max) {
                $this->selfRepeat($nStart, $chunkSize, $pickSize, $repeat);
            }
        }

        $report = common_report_Report::createSuccess(
            sprintf("Items in range from %s to %s proceeded in amount of %s", $start, $end, $this->affected)
        );

        $report->add($this->anomalies);

        return $report;
    }

    abstract protected function getTargetClasses(): array;

    abstract protected function applyProcessor(Iterator $iterator): bool;

    private function initAnomaliesCollector(): void
    {
        $this->anomalies = common_report_Report::createInfo('Anomalies list');
    }

    protected function addAnomaly(string $id, string $uri, string $reason): void
    {
        $this->anomalies->add(new common_report_Report(common_report_Report::TYPE_WARNING, $reason, [$id, $uri]));
    }

    private function keepCurrentPosition(int $param): void
    {
        /** @var common_persistence_KvDriver $persistence */
        $persistence = $this->getServiceLocator()->get(PersistenceManager::SERVICE_ID)->getPersistenceById('default_kv');
        $persistence->set(static::class . static::CACHE_KEY, $param);
    }
}
