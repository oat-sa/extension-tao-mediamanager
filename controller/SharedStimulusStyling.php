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

use oat\taoMediaManager\model\sharedStimulus\css\handler\UploadStylesheetRequestHandler;
use oat\taoMediaManager\model\sharedStimulus\css\service\UploadStylesheetService;
use Throwable;
use tao_helpers_Http as HttpHelper;
use oat\oatbox\log\LoggerAwareTrait;
use tao_actions_CommonModule as CommonModule;
use oat\tao\model\http\formatter\ResponseFormatter;
use oat\tao\model\http\response\ErrorJsonResponse;
use oat\tao\model\http\response\SuccessJsonResponse;
use oat\taoMediaManager\model\sharedStimulus\css\service\LoadStylesheetService;
use oat\taoMediaManager\model\sharedStimulus\css\handler\LoadStylesheetHandler;
use oat\taoMediaManager\model\sharedStimulus\css\service\ListStylesheetsService;
use oat\taoMediaManager\model\sharedStimulus\css\handler\ListStylesheetsHandler;
use oat\taoMediaManager\model\sharedStimulus\css\service\LoadStylesheetClassesService;
use oat\taoMediaManager\model\sharedStimulus\css\service\SaveStylesheetClassesService;
use oat\taoMediaManager\model\sharedStimulus\css\handler\SaveStylesheetClassesHandler;
use oat\taoMediaManager\model\sharedStimulus\css\handler\LoadStylesheetClassesHandler;

class SharedStimulusStyling extends CommonModule
{
    use LoggerAwareTrait;

    public function save(
        ResponseFormatter $responseFormatter,
        SaveStylesheetClassesHandler $saveStylesheetClassesHandler,
        SaveStylesheetClassesService $saveStylesheetClassesService
    ): void {
        $formatter = $responseFormatter->withJsonHeader();

        try {
            $saveStylesheetClassesDTO = $saveStylesheetClassesHandler($this->getPsrRequest());
            $saveStylesheetClassesService->save($saveStylesheetClassesDTO);

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
        LoadStylesheetClassesHandler $loadStylesheetClassesHandler,
        LoadStylesheetClassesService $loadStylesheetClassesService
    ): void {
        $formatter = $responseFormatter->withJsonHeader();

        try {
            $loadStylesheetClassesDTO = $loadStylesheetClassesHandler($this->getPsrRequest());
            $data = $loadStylesheetClassesService->load($loadStylesheetClassesDTO);

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
        ListStylesheetsHandler $listStylesheetsHandler,
        ListStylesheetsService $listStylesheetsService
    ): void {
        $formatter = $responseFormatter->withJsonHeader();

        try {
            $listStylesheetsDTO = $listStylesheetsHandler($this->getPsrRequest());
            $data = $listStylesheetsService->getList($listStylesheetsDTO);

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
        LoadStylesheetHandler $loadStylesheetHandler,
        LoadStylesheetService $loadStylesheetService
    ): void {
        try {
            $loadStylesheetDTO = $loadStylesheetHandler($this->getPsrRequest());
            $stream = $loadStylesheetService->load($loadStylesheetDTO);

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

    public function upload(
        UploadStylesheetRequestHandler $uploadStylesheetHandler,
        UploadStylesheetService $uploadStylesheetService,
        ResponseFormatter $responseFormatter
    ): void {
        $formatter = $responseFormatter->withJsonHeader();

        try {
            $uploadedStylesheet = $uploadStylesheetHandler($this->getPsrRequest());
            $uploadStylesheetService->save($uploadedStylesheet);

            $formatter->withBody(new SuccessJsonResponse([]));
        } catch (Throwable $exception) {
            $this->logError(sprintf('Error uploading passage stylesheets: %s', $exception->getMessage()));

            $formatter
                ->withStatusCode(400)
                ->withBody(new ErrorJsonResponse($exception->getCode(), $exception->getMessage()));
        }

        $this->setResponse($formatter->format($this->getPsrResponse()));
    }
}
