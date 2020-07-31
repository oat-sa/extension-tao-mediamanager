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


namespace oat\taoMediaManager\model\relation\service;


use common_persistence_Persistence;
use common_persistence_sql_QueryIterator;
use common_persistence_SqlPersistence;
use core_kernel_persistence_Exception;
use Doctrine\DBAL\Connection;
use oat\generis\model\OntologyAwareTrait;
use oat\generis\model\OntologyRdf;
use oat\oatbox\service\ConfigurableService;
use oat\tao\model\task\migration\ResourceResultUnit;
use oat\tao\model\task\migration\ResultUnitCollection;
use oat\tao\model\task\migration\service\ResultSearcherInterface;

abstract class AbstractRdsSearcher extends ConfigurableService implements ResultSearcherInterface
{
    use OntologyAwareTrait;

    abstract protected function getTargetClasses(): array;

    public function search(int $start, int $end, int $max): ResultUnitCollection
    {
        $results = $this->getPersistenceIterator($start, $max, $end);
        $resultUnitCollection = new ResultUnitCollection();
        foreach ($results as $result){
            $resultUnitCollection->add(new ResourceResultUnit($this->getResource($result['subject'])));
        }

        return $resultUnitCollection;
    }

    private function getPersistenceIterator(int $start, int $end, int $max): iterable
    {
        /** @var common_persistence_Persistence $persistence */
        $persistence = $this->getModel()->getPersistence();

        if (!($persistence instanceof common_persistence_SqlPersistence)) {
            throw new core_kernel_persistence_Exception(
                'Persistence implementation has to be common_persistence_SqlPersistence instance'
            );
        }

        $query = 'SELECT id, subject FROM statements WHERE (id BETWEEN :start AND :end) AND predicate = :predicate AND object IN (:class) ORDER BY id';
        $type['class'] = Connection::PARAM_STR_ARRAY;

        return new common_persistence_sql_QueryIterator(
            $persistence,
            $query,
            $this->getFilterArray($start, $end, $max),
            $type
        );
    }

    private function getFilterArray(int $start, int $end): array
    {
        return [
            'start' => $start,
            'end' => $end,
            'predicate' => OntologyRdf::RDF_TYPE,
            'class' => array_unique($this->getTargetClasses()),
        ];

    }
}
