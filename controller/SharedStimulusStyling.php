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

use oat\oatbox\log\LoggerAwareTrait;
use oat\tao\model\http\formatter\ResponseFormatter;
use oat\tao\model\http\response\ErrorJsonResponse;
use oat\tao\model\http\response\SuccessJsonResponse;
use oat\taoMediaManager\model\sharedStimulus\css\factory\CommandFactory;
use oat\taoMediaManager\model\sharedStimulus\css\service\LoadService;
use oat\taoMediaManager\model\sharedStimulus\css\service\SaveService;
use tao_actions_CommonModule;
use Throwable;

class SharedStimulusStyling extends tao_actions_CommonModule
{
    use LoggerAwareTrait;

    public function save(): void
    {
        $formatter = $this->getResponseFormatter()
            ->withJsonHeader();

        try {
            $command = $this->getCommandFactory()
                ->makeSaveCommandByRequest($this->getPsrRequest());

            $this->getSaveService()
                ->save($command);

            $formatter->withBody(new SuccessJsonResponse([]));
        } catch (Throwable $exception) {
            $this->logError(sprintf('Error saving passage styles: %s', $exception->getMessage()));

            $formatter->withStatusCode(400)
                ->withBody(new ErrorJsonResponse($exception->getCode(), $exception->getMessage()));
        }

        $this->setResponse($formatter->format($this->getPsrResponse()));
    }

    public function load(): void
    {
        $formatter = $this->getResponseFormatter()
            ->withJsonHeader();

        try {
            $command = $this->getCommandFactory()
                ->makeLoadCommandByRequest($this->getPsrRequest());

            $data = $this->getLoadService()
                ->load($command);

            $formatter->withBody(new SuccessJsonResponse($data));
        } catch (Throwable $exception) {
            $this->logError(sprintf('Error loading passage styles: %s', $exception->getMessage()));

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

    private function getSaveService(): SaveService
    {
        return $this->getServiceLocator()->get(SaveService::class);
    }

    private function getLoadService(): LoadService
    {
        return $this->getServiceLocator()->get(LoadService::class);
    }
}
