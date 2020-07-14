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


use common_exception_MissingParameter;
use core_kernel_classes_Resource;
use Doctrine\DBAL\Connection;
use oat\generis\model\OntologyAwareTrait;
use oat\generis\model\OntologyRdf;
use oat\generis\persistence\PersistenceManager;
use oat\oatbox\action\Action;
use oat\tao\model\TaoOntology;
use oat\tao\model\taskQueue\Task\TaskAwareInterface;
use oat\tao\model\taskQueue\Task\TaskAwareTrait;
use oat\taoQtiItem\model\qti\event\UpdatedItemEventDispatcher;
use oat\taoQtiItem\model\qti\Service;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

class ItemToMedia implements Action, ServiceLocatorAwareInterface, TaskAwareInterface
{
    use ServiceLocatorAwareTrait;
    use OntologyAwareTrait;
    use TaskAwareTrait;

    private $affected;

    public function __invoke($params)
    {
        if (count($params) < 3) {
            throw new common_exception_MissingParameter();
        }
        $start = array_shift($params);
        $end = array_shift($params);
        $chunkSize = array_shift($params);

        $itemClasses = $this->getTargetClasses();

        $iterator = $this->getIterator($itemClasses, $start, $end);

        iterator_apply(
            $iterator,
            [$this, 'debugRelationship'],
            [$iterator, $chunkSize, $this->affected]
        );
    }

    private function updateRelationship(core_kernel_classes_Resource $resource): void
    {
        $item = $this->getQtiService()->getDataItemByRdfItem($resource);
        if ($item) {
            $this->getUpdatedItemEventDispatcher()->dispatch($item, $resource);
        }
    }

    private function getUpdatedItemEventDispatcher(): UpdatedItemEventDispatcher
    {
        return $this->getServiceLocator()->get(UpdatedItemEventDispatcher::class);
    }

    private function getQtiService(): Service
    {
        return $this->getServiceLocator()->get(Service::class);
    }

    private function getTargetClasses(): array
    {
        return $this->getClass(TaoOntology::CLASS_URI_ITEM)->getSubClasses(true);
    }

    private function getIterator(array $itemClasses, $start, $end): common_persistence_sql_QueryIterator
    {
        /** @var  PersistenceManager $persistence */
        $persistence = $this->getServiceLocator()->get(PersistenceManager::SERVICE_ID);
        $default = $persistence->getPersistenceById('default');

        $q = 'SELECT * FROM statements WHERE (id BETWEEN :start AND :end) AND predicate = :predicate AND object IN (:class) ORDER BY id';
        $params = [
            'start' => $start,
            'end' => $end,
            'predicate' => OntologyRdf::RDF_TYPE,
            'class' => array_keys($itemClasses)
        ];

        $types['class'] = Connection::PARAM_STR_ARRAY;

        $iterator = new common_persistence_sql_QueryIterator($default, $q, $params, $types);
        return $iterator;
    }


    private function debugRelationship(Iterator $iterator): bool
    {
        $item = $iterator->current();

        echo sprintf(
            '%s %s %s %s',
            ++$this->affected,
            $item['id'],
            $item['subject'],
            PHP_EOL
        );

        $this->updateRelationship($this->getResource($item['subject']));

        $is = $this->affected % 3;
        if ($is) {
            throw  new \RuntimeException('aa');
        }

        $isOver = $this->affected < $this->chunkSize * 2;

        return $isOver;
    }

}