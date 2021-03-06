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

namespace oat\taoMediaManager\controller;

use oat\oatbox\log\LoggerAwareTrait;
use oat\tao\model\http\HttpJsonResponseTrait;
use oat\taoMediaManager\model\relation\factory\QueryFactory;
use oat\taoMediaManager\model\relation\MediaRelationService;
use tao_actions_CommonModule;
use Throwable;

/**
 * @deprecated use tao_actions_ResourceRelations
 */
class MediaRelations extends tao_actions_CommonModule
{
    use LoggerAwareTrait;
    use HttpJsonResponseTrait;

    /**
     * @deprecated use tao_actions_ResourceRelations
     */
    public function relations(): void
    {
        try {
            $query = $this->getQueryFactory()
                ->createFindAllQueryByRequest($this->getPsrRequest());

            $collection = $this->getMediaRelationService()
                ->findRelations($query)
                ->jsonSerialize();

            $this->setSuccessJsonResponse($collection);
        } catch (Throwable $exception) {
            $this->logError(sprintf('Error getting media relation: %s, ', $exception->getMessage()));

            $this->setErrorJsonResponse($exception->getMessage(), $exception->getCode());
        }
    }

    private function getMediaRelationService(): MediaRelationService
    {
        return $this->getServiceLocator()->get(MediaRelationService::class);
    }

    private function getQueryFactory(): QueryFactory
    {
        return $this->getServiceLocator()->get(QueryFactory::class);
    }
}
