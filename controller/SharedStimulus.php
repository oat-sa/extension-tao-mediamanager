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
use oat\tao\model\http\formatter\ResponseFormatter;
use oat\tao\model\http\response\ErrorJsonResponse;
use oat\tao\model\http\response\SuccessJsonResponse;
use oat\taoMediaManager\model\MediaService;
use oat\taoMediaManager\model\sharedStimulus\factory\CommandFactory;
use oat\taoMediaManager\model\sharedStimulus\service\CreateService;
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
                ->createByRequest($this->getPsrRequest());

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
        // @TODO Check proper response to be added:
        $formatter = $this->getResponseFormatter()
            ->withJsonHeader();

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

    private function getCreateService(): CreateService
    {
        return $this->getServiceLocator()->get(CreateService::class);
    }

    protected function getClassService()
    {
        return MediaService::singleton();
    }
}
