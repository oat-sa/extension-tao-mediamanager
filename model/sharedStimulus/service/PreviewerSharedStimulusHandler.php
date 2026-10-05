<?php

/**
 * SPDX-FileCopyrightText: 2026 Open Assessment Technologies S.A.
 * Copyright (C) 2026 (original work) Open Assessment Technologies S.A.
 *
 * SPDX-License-Identifier: AGPL-3.0-only OR LicenseRef-TAO-Commercial-License
 */

declare(strict_types=1);

namespace oat\taoMediaManager\model\sharedStimulus\service;

use core_kernel_classes_Resource;
use oat\taoMediaManager\model\sharedStimulus\css\dto\ListStylesheets as ListSharedStimulusStylesheets;
use oat\taoMediaManager\model\sharedStimulus\css\dto\LoadStylesheet as LoadSharedStimulusStylesheet;
use oat\taoMediaManager\model\sharedStimulus\css\service\ListStylesheetsService;
use oat\taoMediaManager\model\sharedStimulus\css\service\LoadStylesheetService;
use oat\taoMediaManager\model\sharedStimulus\FindQuery;
use oat\taoMediaManager\model\sharedStimulus\parser\JsonQtiAttributeParser;
use oat\taoMediaManager\model\sharedStimulus\repository\SharedStimulusRepository;
use oat\taoMediaManager\model\sharedStimulus\specification\SharedStimulusResourceSpecification;
use oat\taoMediaManager\model\validation\RequestValidator;
use oat\taoQtiTestPreviewer\models\SharedStimulusPreviewHandlerInterface;
use stdClass;

class PreviewerSharedStimulusHandler implements SharedStimulusPreviewHandlerInterface
{
    public function __construct(
        private SharedStimulusRepository $sharedStimulusRepository,
        private JsonQtiAttributeParser $sharedStimulusAttributesParser,
        private SharedStimulusResourceSpecification $sharedStimulusResourceSpecification,
        private ListStylesheetsService $listStylesheetsService,
        private LoadStylesheetService $loadStylesheetService
    ) {
    }

    public function supports(core_kernel_classes_Resource $item): bool
    {
        return $this->sharedStimulusResourceSpecification->isSatisfiedBy($item);
    }

    public function buildResponse(core_kernel_classes_Resource $item, string $baseUrl): array
    {
        $sharedStimulus = $this->sharedStimulusRepository->find(new FindQuery($item->getUri()));
        $parsedBody = $this->sharedStimulusAttributesParser->parse($sharedStimulus);
        $sharedStimulusData = $sharedStimulus->jsonSerialize();
        $identifier = $this->extractIdentifier($item->getUri());
        $body = is_array($parsedBody['body'] ?? null)
            ? $parsedBody['body']
            : [
                'serial' => 'container_' . $identifier,
                'body' => '',
                'elements' => [],
            ];
        $bodySerial = $parsedBody['serial'] ?? ('container_' . $identifier);

        return [
            'baseUrl' => $baseUrl,
            'content' => [
                'type' => 'qti',
                'data' => [
                    'identifier' => $identifier,
                    'serial' => 'item_' . $identifier,
                    'qtiClass' => 'assessmentItem',
                    'attributes' => array_filter([
                        'identifier' => $identifier,
                        'title' => $sharedStimulusData['name'] ?? '',
                        'xml:lang' => $parsedBody['attributes']['xml:lang'] ?? null,
                        'class' => $parsedBody['attributes']['class'] ?? null,
                    ], static fn($value): bool => $value !== null),
                    'body' => $body,
                    'namespaces' => new stdClass(),
                    'stylesheets' => $this->getSharedStimulusStylesheets($item->getUri()),
                    'outcomes' => [],
                    'response' => new stdClass(),
                    'responses' => new stdClass(),
                    'feedbacks' => [],
                    'responseProcessing' => [
                        'attributes' => new stdClass(),
                        'qtiClass' => 'responseProcessing',
                        'responseRules' => [],
                        'serial' => 'response_' . $bodySerial,
                    ],
                ],
                'assets' => [],
            ],
        ];
    }

    public function loadAssetStream(core_kernel_classes_Resource $item, string $path)
    {
        RequestValidator::securityCheckPath($path);

        if ($path === '' || !str_starts_with($path, 'css/')) {
            return null;
        }

        return $this->loadStylesheetService->load(
            new LoadSharedStimulusStylesheet($item->getUri(), basename($path))
        );
    }

    private function getSharedStimulusStylesheets(string $itemUri): array
    {
        $stylesheets = $this->listStylesheetsService
            ->getList(new ListSharedStimulusStylesheets($itemUri));

        $result = [];
        foreach ($stylesheets['children'] ?? [] as $index => $stylesheet) {
            if (!isset($stylesheet['name'])) {
                continue;
            }

            $serial = sprintf('preview_%s', $index);
            $result[$serial] = [
                'qtiClass' => 'stylesheet',
                'attributes' => [
                    'href' => 'css/' . $stylesheet['name'],
                    'media' => 'all',
                    'title' => '',
                    'type' => 'text/css',
                ],
                'serial' => $serial,
            ];
        }

        return $result;
    }

    private function extractIdentifier(string $uri): string
    {
        $position = strrpos($uri, '#');

        return $position === false ? md5($uri) : substr($uri, $position + 1);
    }
}
