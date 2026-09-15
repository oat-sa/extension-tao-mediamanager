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
 * Copyright (c) 2026 (original work) Open Assessment Technologies SA;
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
