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

declare(strict_types=1);

namespace oat\taoMediaManager\controller;

use common_session_SessionManager;
use oat\oatbox\log\LoggerAwareTrait;
use oat\tao\model\http\formatter\ResponseFormatter;
use oat\tao\model\http\response\ErrorJsonResponse;
use oat\tao\model\http\response\SuccessJsonResponse;
use oat\taoMediaManager\model\sharedStimulus\factory\CommandFactory;
use oat\taoMediaManager\model\sharedStimulus\factory\QueryFactory;
use oat\taoMediaManager\model\sharedStimulus\factory\UpdateFactory;
use oat\taoMediaManager\model\sharedStimulus\repository\SharedStimulusRepository;
use oat\taoMediaManager\model\sharedStimulus\service\CreateService;
use oat\taoMediaManager\model\sharedStimulus\service\UpdateService;
use tao_actions_CommonModule;
use Throwable;

class SharedStimulus extends tao_actions_CommonModule
{
    use LoggerAwareTrait;

    public function create(): void
    {
        $formatter = $this->getResponseFormatter()
            ->withJsonHeader();

        try {
            $command = $this->getCommandFactory()
                ->makeCreateCommandByRequest($this->getPsrRequest());

            $sharedStimulus = $this->getCreateService()
                ->create($command);

            $formatter->withBody(new SuccessJsonResponse($sharedStimulus->jsonSerialize()));
        } catch (Throwable $exception) {
            $this->logError(sprintf('Error creating Shared Stimulus: %s', $exception->getMessage()));

            $formatter->withStatusCode(400)
                ->withBody(new ErrorJsonResponse($exception->getCode(), $exception->getMessage()));
        }

        $this->setResponse($formatter->format($this->getPsrResponse()));
    }

    public function get(): void
    {
        $formatter = $this->getResponseFormatter()
            ->withJsonHeader();

        try {
            $command = $this->getQueryFactory()
                ->makeFindQueryByRequest($this->getPsrRequest());

            $sharedStimulus = $this->getSharedStimulusRepository()
                ->find($command);

            $formatter->withBody(new SuccessJsonResponse($sharedStimulus->jsonSerialize()));
        } catch (Throwable $exception) {
            $this->logError(sprintf('Error retrieving Shared Stimulus: %s', $exception->getMessage()));

            $formatter->withStatusCode(400)
                ->withBody(new ErrorJsonResponse($exception->getCode(), $exception->getMessage()));
        }

        $this->setResponse($formatter->format($this->getPsrResponse()));
    }

    public function update(): void
    {
        $formatter = $this->getResponseFormatter()
            ->withJsonHeader();

        try {
            $user = common_session_SessionManager::getSession()->getUser();

            $command = $this->getCommandFactory()
                ->makeUpdateCommandByRequest($this->getPsrRequest(), $user);

            $sharedStimulus = $this->getUpdateService()->update($command);

            $formatter->withBody(new SuccessJsonResponse([]));
        } catch (Throwable $exception) {
            $this->logError(sprintf('Error Updating Shared Stimulus: %s', $exception->getMessage()));

            $formatter->withStatusCode(400)
                ->withBody(new ErrorJsonResponse($exception->getCode(), $exception->getMessage()));
        }

        $this->setResponse($formatter->format($this->getPsrResponse()));
    }

    private function getResponseFormatter(): ResponseFormatter
    {
        return $this->getServiceLocator()->get(ResponseFormatter::class);
    }

    private function getCommandFactory(): CommandFactory
    {
        return $this->getServiceLocator()->get(CommandFactory::class);
    }

    private function getQueryFactory(): QueryFactory
    {
        return $this->getServiceLocator()->get(QueryFactory::class);
    }

    private function getCreateService(): CreateService
    {
        return $this->getServiceLocator()->get(CreateService::class);
    }

    private function getUpdateService(): UpdateService
    {
        return $this->getServiceLocator()->get(UpdateService::class);
    }

    private function getSharedStimulusRepository(): SharedStimulusRepository
    {
        return $this->getServiceLocator()->get(SharedStimulusRepository::class);
    }

}
