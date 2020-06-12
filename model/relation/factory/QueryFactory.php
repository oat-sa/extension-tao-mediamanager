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

namespace oat\taoMediaManager\model\relation\factory;

use InvalidArgumentException;
use oat\oatbox\service\ConfigurableService;
use oat\taoMediaManager\model\relation\repository\MediaRelationRepositoryInterface;
use oat\taoMediaManager\model\relation\repository\query\FindAllQuery;
use Psr\Http\Message\ServerRequestInterface;

class QueryFactory extends ConfigurableService
{
    private const SOURCE_ID = 'sourceId';
    private const CLASS_ID = 'classId';

    public function createFindAllQueryByRequest(ServerRequestInterface $request): FindAllQuery
    {
        $sourceId = $request->getQueryParams()[self::SOURCE_ID] ?? null;
        $classId = $request->getQueryParams()[self::CLASS_ID] ?? null;

        if ($sourceId === null && $classId === null) {
            throw new InvalidArgumentException(sprintf('Parameter sourceId or classId must be provided'));
        }

        return new FindAllQuery($sourceId, $classId);
    }

    private function getMediaRelationRepository(): MediaRelationRepositoryInterface
    {
        return $this->getServiceLocator()->get(MediaRelationRepositoryInterface::SERVICE_ID);
    }
}
