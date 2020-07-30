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

use common_persistence_sql_QueryIterator;
use Doctrine\DBAL\Connection;
use Iterator;
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\service\ConfigurableService;
use oat\tao\model\TaoOntology;
use oat\tao\model\task\migration\service\ResultSearcherInterface;
use oat\tao\model\task\migration\service\ResultUnitCollection;
use oat\tao\model\task\migration\MigrationConfig;

class ItemToMediaSearcher extends ConfigurableService implements ResultSearcherInterface
{
    use OntologyAwareTrait;

    public function search(MigrationConfig $filter): ResultUnitCollection
    {
        $persistence = $this->getModel()->getPersistence();

        $query = 'SELECT id, subject FROM statements WHERE (id BETWEEN :start AND :end) AND predicate = :predicate AND object IN (:class) ORDER BY id';

        //$types['class'] = Connection::PARAM_STR_ARRAY;

        $types['class'] = array_merge(
            [
                TaoOntology::CLASS_URI_ITEM,
            ],
            array_keys($this->getClass(TaoOntology::CLASS_URI_ITEM)->getSubClasses(true))
        );

        //return new common_persistence_sql_QueryIterator($persistence, $query, $filter->getParameters(), $types);
        //@TODO Iterate and populate collection
        return new ResultUnitCollection();
    }
}
