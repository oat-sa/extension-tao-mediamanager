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
 *
 */

namespace oat\taoMediaManager\controller;

use oat\oatbox\log\LoggerAwareTrait;
use oat\taoMediaManager\model\MediaService;
use oat\taoMediaManager\model\sharedStimulus\CreateCommand;
use oat\taoMediaManager\model\sharedStimulus\service\CreateByRequestService;
use oat\taoMediaManager\model\sharedStimulus\service\CreateService;
use tao_actions_SaSModule;
use tao_helpers_Uri;
use Throwable;

class SharedStimulus extends tao_actions_SaSModule
{
    use LoggerAwareTrait;

    public function create(): void
    {
        $this->isJsonRequest()
            ? $this->createFromApiRequest()
            : $this->createFromFormRequest();
    }

    private function createFromApiRequest(): void
    {
        $response = $this->getCreateByRequestService()
            ->create($this->getPsrRequest(), $this->getPsrResponse());

        $responseContent = json_decode((string)$response->getBody(), true);

        if ($response->getStatusCode() !== 200) {
            $this->logError(
                sprintf(
                    'Error creating Shared Stimulus: %s',
                    $responseContent['message'] ?? 'Internal error'
                )
            );
        }

        $this->returnJson($responseContent, $response->getStatusCode());
    }

    /*
     * @TODO This whole method and template must be removed if FE uses API call instead.
     */
    private function createFromFormRequest(): void
    {
        try {
            $this->getCreateService()
                ->create(new CreateCommand(tao_helpers_Uri::decode($this->getRequestParameter('classUri'))));

            $this->setData('message', __('Instance saved'));
        } catch (Throwable $e) {
            $this->logError(sprintf('Error creating Shared Stimulus: %s', $e->getMessage()));
            $this->setData('error', __('Error creating Shared Stimulus'));
        }

        $this->setView('sharedStimulus/create.tpl');
    }

    private function isJsonRequest(): bool
    {
        return current($this->getPsrRequest()->getHeader('content-type')) === 'application/json';
    }

    private function getCreateByRequestService(): CreateByRequestService
    {
        return $this->getServiceLocator()->get(CreateByRequestService::class);
    }

    private function getCreateService(): CreateService
    {
        return $this->getServiceLocator()->get(CreateService::class);
    }

    protected function getClassService()
    {
        return MediaService::singleton();
    }
}
