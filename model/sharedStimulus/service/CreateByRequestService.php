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

use common_Exception;
use common_exception_Error;
use ErrorException;
use FileNotFoundException;
use oat\oatbox\service\ConfigurableService;
use oat\taoMediaManager\model\sharedStimulus\CreateCommand;
use oat\taoMediaManager\model\sharedStimulus\SharedStimulus;
use Psr\Http\Message\ServerRequestInterface;

class CreateByRequestService extends ConfigurableService
{
    /**
     * @param ServerRequestInterface $request
     * @return SharedStimulus
     * @throws ErrorException
     * @throws FileNotFoundException
     * @throws common_Exception
     * @throws common_exception_Error
     */
    public function create(ServerRequestInterface $request): SharedStimulus
    {
        return $this->getCreateService()
            ->create($this->createCommand($this->getParsedBody($request)));
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
