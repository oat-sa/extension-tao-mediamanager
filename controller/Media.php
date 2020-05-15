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

use InvalidArgumentException;
use oat\oatbox\log\LoggerAwareTrait;
use oat\tao\model\http\formatter\ResponseFormatter;
use oat\tao\model\http\response\ErrorJsonResponse;
use oat\tao\model\http\response\SuccessJsonResponse;
use oat\taoMediaManager\model\relation\MediaRelationService;
use tao_actions_CommonModule;
use Throwable;

class Media extends tao_actions_CommonModule
{
    use LoggerAwareTrait;

    public function relations(): void
    {
        $formatter = $this->getResponseFormatter()
            ->withJsonHeader();

        try {
            $sourceId = $this->getPsrRequest()->getQueryParams()['sourceId'] ?? null;

            if (empty($sourceId)) {
                throw new InvalidArgumentException(sprintf('Parameter sourceId must be provided'));
            }

            $collection = $this->getMediaRelationService()
                ->getMediaRelation((string)$sourceId)
                ->jsonSerialize();

            $formatter->withBody(new SuccessJsonResponse($collection));
        } catch (Throwable $exception) {
            $this->logError(sprintf('Error getting media relation: %s, ', $exception->getMessage()));

            $formatter
                ->withStatusCode(400)
                ->withBody(new ErrorJsonResponse(400, $exception->getMessage()));
        }

        $this->setResponse($formatter->format($this->getPsrResponse()));
    }

    private function getResponseFormatter(): ResponseFormatter
    {
        return $this->getServiceLocator()->get(ResponseFormatter::class);
    }

    private function getMediaRelationService(): MediaRelationService
    {
        return $this->getServiceLocator()->get(MediaRelationService::class);
    }
}
