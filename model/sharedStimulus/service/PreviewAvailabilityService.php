<?php

/**
 * SPDX-FileCopyrightText: 2026 Open Assessment Technologies S.A.
 * Copyright (C) 2026 (original work) Open Assessment Technologies S.A.
 *
 * SPDX-License-Identifier: AGPL-3.0-only OR LicenseRef-TAO-Commercial-License
 */

declare(strict_types=1);

namespace oat\taoMediaManager\model\sharedStimulus\service;

use oat\oatbox\log\LoggerAwareTrait;
use oat\oatbox\service\ConfigurableService;
use oat\taoMediaManager\model\sharedStimulus\FindQuery;
use oat\taoMediaManager\model\sharedStimulus\parser\JsonQtiAttributeParser;
use oat\taoMediaManager\model\sharedStimulus\repository\SharedStimulusRepository;

class PreviewAvailabilityService extends ConfigurableService
{
    use LoggerAwareTrait;

    public function hasPreviewContent(string $uri): bool
    {
        try {
            $sharedStimulus = $this->getSharedStimulusRepository()->find(new FindQuery($uri));
            $parsedBody = $this->getSharedStimulusAttributesParser()->parse($sharedStimulus);
        } catch (\Throwable $exception) {
            $this->logWarning(sprintf(
                'Unable to determine shared stimulus preview content for "%s": %s',
                $uri,
                $exception->getMessage()
            ));

            return false;
        }

        $body = $parsedBody['body'] ?? null;

        if (!is_array($body)) {
            return false;
        }

        return trim((string) ($body['body'] ?? '')) !== '';
    }

    private function getSharedStimulusRepository(): SharedStimulusRepository
    {
        return $this->getServiceLocator()->get(SharedStimulusRepository::class);
    }

    private function getSharedStimulusAttributesParser(): JsonQtiAttributeParser
    {
        return $this->getServiceLocator()->get(JsonQtiAttributeParser::class);
    }
}
