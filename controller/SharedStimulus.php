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
 * Copyright (c) 2020-2021 (original work) Open Assessment Technologies SA;
 *
 */

declare(strict_types=1);

namespace oat\taoMediaManager\controller;

use common_session_SessionManager;
use oat\oatbox\log\LoggerAwareTrait;
use oat\tao\model\accessControl\data\DataAccessControl;
use oat\tao\model\http\formatter\ResponseFormatter;
use oat\tao\model\http\response\ErrorJsonResponse;
use oat\tao\model\http\response\SuccessJsonResponse;
use oat\tao\model\resources\ResourceAccessDeniedException;
use oat\taoMediaManager\model\sharedStimulus\factory\CommandFactory;
use oat\taoMediaManager\model\sharedStimulus\factory\QueryFactory;
use oat\taoMediaManager\model\sharedStimulus\parser\JsonQtiAttributeParser;
use oat\taoMediaManager\model\sharedStimulus\repository\SharedStimulusRepository;
use oat\taoMediaManager\model\sharedStimulus\service\CreateService;
use oat\taoMediaManager\model\sharedStimulus\SharedStimulus as SharedStimulusObject;
use oat\taoMediaManager\model\sharedStimulus\service\PatchService;
use tao_actions_SaSModule;
use Throwable;

class SharedStimulus extends tao_actions_SaSModule
{
    use LoggerAwareTrait;

    private const PERMISSION_READ = 'READ';

    /**
     * @requiresRight classUri WRITE
     */
    public function create(): void
    {
        $formatter = $this->getResponseFormatter()
            ->withJsonHeader();

        try {
            $command = $this->getCommandFactory()
                ->makeCreateCommandByRequest($this->getPsrRequest());

            $this->validateWritePermission($command->getClassId());

            $sharedStimulus = $this->getCreateService()
                ->create($command);

            $this->renderSharedStimulus($formatter, $sharedStimulus);
        } catch (Throwable $exception) {
            $this->logError(sprintf('Error creating Shared Stimulus: %s', $exception->getMessage()));

            $formatter->withStatusCode(400)
                ->withBody(new ErrorJsonResponse($exception->getCode(), $exception->getMessage()));
        }

        $this->setResponse($formatter->format($this->getPsrResponse()));
    }

    /**
     * @requiresRight id READ
     */
    public function get(): void
    {
        $formatter = $this->getResponseFormatter()
            ->withJsonHeader();

        try {
            $command = $this->getQueryFactory()
                ->makeFindQueryByRequest($this->getPsrRequest());

            $this->validateReadPermission($command->getId());

            $sharedStimulus = $this->getSharedStimulusRepository()
                ->find($command);

            $this->renderSharedStimulus($formatter, $sharedStimulus);
        } catch (Throwable $exception) {
            $this->logError(sprintf('Error retrieving Shared Stimulus: %s', $exception->getMessage()));

            $formatter->withStatusCode(400)
                ->withBody(new ErrorJsonResponse($exception->getCode(), $exception->getMessage()));
        }

        $this->setResponse($formatter->format($this->getPsrResponse()));
    }

    /**
     * @requiresRight id WRITE
     */
    public function patch(): void
    {
        $formatter = $this->getResponseFormatter()
            ->withJsonHeader();

        try {
            $request = $this->getPsrRequest();

            $user = common_session_SessionManager::getSession()->getUser();
            $id = $request->getQueryParams()['id'];
            $body = json_decode((string)$request->getBody(), true)['body'];

            $this->validateWritePermission($id);

            $command = $this->getCommandFactory()->makePatchCommand($id, $body, $user);

            $this->getPatchService()->patch($command);

            $formatter->withBody(new SuccessJsonResponse([]));
        } catch (Throwable $exception) {
            $this->logError(sprintf('Error Updating Shared Stimulus: %s', $exception->getMessage()));

            $formatter->withStatusCode(400)
                ->withBody(new ErrorJsonResponse($exception->getCode(), $exception->getMessage()));
        }

        $this->setResponse($formatter->format($this->getPsrResponse()));
    }

    private function renderSharedStimulus(ResponseFormatter $formatter, SharedStimulusObject $sharedStimulus): void
    {
        $data = $sharedStimulus->jsonSerialize();
        if (isset($data['body'])) {
            $data['body'] = $this->getSharedStimulusAttributesParser()->parse($sharedStimulus);
        }
        $data['permissions'] = $this->getPreviewPermission();
        $formatter->withBody(new SuccessJsonResponse($data));
    }

    private function getPreviewPermission(): array
    {
        if ($this->hasAccess(MediaManager::class, 'getFile')) {
            return [self::PERMISSION_READ];
        }
        return [];
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

    private function getPatchService(): PatchService
    {
        return $this->getServiceLocator()->get(PatchService::class);
    }

    private function getSharedStimulusRepository(): SharedStimulusRepository
    {
        return $this->getServiceLocator()->get(SharedStimulusRepository::class);
    }

    private function getSharedStimulusAttributesParser(): JsonQtiAttributeParser
    {
        return $this->getServiceLocator()->get(JsonQtiAttributeParser::class);
    }

    private function validateWritePermission(string $resourceId): void
    {
        if (!$this->hasWriteAccess($resourceId)) {
            throw new ResourceAccessDeniedException($resourceId);
        }
    }

    private function validateReadPermission(string $resourceId): void
    {
        $user = $this->getSession()->getUser();

        $hasReadAccess = (new DataAccessControl())->hasPrivileges($user, [$resourceId => 'READ']);

        if (!$hasReadAccess) {
            throw new ResourceAccessDeniedException($resourceId);
        }
    }
}
