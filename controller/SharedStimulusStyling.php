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
 * Copyright (c) 2021 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoMediaManager\controller;

use Throwable;
use tao_helpers_Http as HttpHelper;
use oat\oatbox\log\LoggerAwareTrait;
use tao_actions_CommonModule as CommonModule;
use oat\tao\model\http\formatter\ResponseFormatter;
use oat\tao\model\http\response\ErrorJsonResponse;
use oat\tao\model\http\response\SuccessJsonResponse;
use oat\taoMediaManager\model\sharedStimulus\css\service\LoadService;
use oat\taoMediaManager\model\sharedStimulus\css\service\SaveService;
use oat\taoMediaManager\model\sharedStimulus\css\factory\CommandFactory;
use oat\taoMediaManager\model\sharedStimulus\css\service\StylesheetService;

class SharedStimulusStyling extends CommonModule
{
    use LoggerAwareTrait;

    public function save(
        ResponseFormatter $responseFormatter,
        CommandFactory $commandFactory,
        SaveService $saveService
    ): void {
        $formatter = $responseFormatter->withJsonHeader();

        try {
            $command = $commandFactory->makeSaveCommandByRequest($this->getPsrRequest());
            $saveService->save($command);

            $formatter->withBody(new SuccessJsonResponse([]));
        } catch (Throwable $exception) {
            $this->logError(sprintf('Error saving passage styles: %s', $exception->getMessage()));

            $formatter
                ->withStatusCode(400)
                ->withBody(new ErrorJsonResponse($exception->getCode(), $exception->getMessage()));
        }

        $this->setResponse($formatter->format($this->getPsrResponse()));
    }

    public function load(
        ResponseFormatter $responseFormatter,
        CommandFactory $commandFactory,
        LoadService $loadService
    ): void {
        $formatter = $responseFormatter->withJsonHeader();

        try {
            $command = $commandFactory->makeLoadCommandByRequest($this->getPsrRequest());
            $data = $loadService->load($command);

            $formatter->withBody(new SuccessJsonResponse($data));
        } catch (Throwable $exception) {
            $this->logError(sprintf('Error loading passage styles: %s', $exception->getMessage()));

            $formatter
                ->withStatusCode(400)
                ->withBody(new ErrorJsonResponse($exception->getCode(), $exception->getMessage()));
        }

        $this->setResponse($formatter->format($this->getPsrResponse()));
    }

    public function getStylesheets(
        ResponseFormatter $responseFormatter,
        CommandFactory $commandFactory,
        StylesheetService $stylesheetsService
    ): void {
        $formatter = $responseFormatter->withJsonHeader();

        try {
            $command = $commandFactory->makeGetStylesheetsCommandByRequest($this->getPsrRequest());
            $data = $stylesheetsService->getList($command);

            $formatter->withBody(new SuccessJsonResponse($data));
        } catch (Throwable $exception) {
            $this->logError(sprintf('Error loading passage stylesheets: %s', $exception->getMessage()));

            $formatter
                ->withStatusCode(400)
                ->withBody(new ErrorJsonResponse($exception->getCode(), $exception->getMessage()));
        }

        $this->setResponse($formatter->format($this->getPsrResponse()));
    }

    public function loadStylesheet(
        ResponseFormatter $responseFormatter,
        CommandFactory $commandFactory,
        StylesheetService $stylesheetsService
    ): void {
        try {
            $command = $commandFactory->makeLoadStylesheetCommandByRequest($this->getPsrRequest());
            $stream = $stylesheetsService->load($command);

            HttpHelper::returnStream($stream, 'text/css');
        } catch (Throwable $exception) {
            $this->logError(sprintf('Error loading passage stylesheet: %s', $exception->getMessage()));

            $formatter = $responseFormatter->withJsonHeader();
            $formatter
                ->withStatusCode(400)
                ->withBody(new ErrorJsonResponse($exception->getCode(), $exception->getMessage()));

            $this->setResponse($formatter->format($this->getPsrResponse()));
        }
    }
}
