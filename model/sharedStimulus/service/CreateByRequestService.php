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

namespace oat\taoMediaManager\model\sharedStimulus\service;

use oat\oatbox\service\ConfigurableService;
use oat\tao\model\http\response\ErrorJsonResponse;
use oat\tao\model\http\response\JsonResponseInterface;
use oat\tao\model\http\response\SuccessJsonResponse;
use oat\taoMediaManager\model\sharedStimulus\CreateCommand;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

class CreateByRequestService extends ConfigurableService
{
    public function create(ServerRequestInterface $request, ResponseInterface $response): JsonResponseInterface
    {
        try {
            $sharedStimulus = $this->getCreateService()
                ->create($this->createCommand($this->getParsedBody($request)));

            return new SuccessJsonResponse($sharedStimulus->jsonSerialize());
        } catch (Throwable $exception) {
            return new ErrorJsonResponse($exception->getCode(), $exception->getMessage());
        }
    }

    private function createCommand(array $parsedBody): CreateCommand
    {
        return new CreateCommand(
            $parsedBody['classUri'] ?? '',
            $parsedBody['name'] ?? null,
            $parsedBody['languageUri'] ?? null
        );
    }

    private function getParsedBody(ServerRequestInterface $request): array
    {
        return json_decode((string)$request->getBody(), true);
    }

    private function getCreateService(): CreateService
    {
        return $this->getServiceLocator()->get(CreateService::class);
    }
}
