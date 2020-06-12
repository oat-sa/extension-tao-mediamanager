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
 */

declare(strict_types=1);

namespace oat\taoMediaManager\model\relation\event;

use oat\oatbox\event\EventManager;
use oat\oatbox\filesystem\File;
use oat\oatbox\service\ConfigurableService;
use oat\taoMediaManager\model\MediaService;
use oat\taoMediaManager\model\sharedStimulus\parser\SharedStimulusMediaExtractor;
use tao_helpers_File;

class MediaSavedEventDispatcher extends ConfigurableService
{
    public function dispatchFromContent(string $id, string $mimeType, string $content): void
    {
        $elementIds = $this->isSharedStimulus($mimeType)
            ? $this->getSharedStimulusExtractor()->extractMediaIdentifiers($content)
            : [];

        $this->getEventManager()->trigger(new MediaSavedEvent($id, $elementIds));
    }

    /**
     * @param string|File $fileSource
     */
    public function dispatchFromFile(string $id, $fileSource, string $mimeType = null): void
    {
        if (!$mimeType) {
            $mimeType = $fileSource instanceof File ? $fileSource->getMimeType() : tao_helpers_File::getMimeType($fileSource);
        }

        if ($this->isSharedStimulus($mimeType)) {
            $content = $fileSource instanceof File ? $fileSource->read() : file_get_contents($fileSource);
        } else {
            $content = '';
        }

        $this->dispatchFromContent($id, $mimeType, $content);
    }

    private function isSharedStimulus($mimeType): bool
    {
        return $mimeType === MediaService::SHARED_STIMULUS_MIME_TYPE;
    }

    private function getEventManager(): EventManager
    {
        return $this->getServiceLocator()->get(EventManager::SERVICE_ID);
    }

    private function getSharedStimulusExtractor(): SharedStimulusMediaExtractor
    {
        return $this->getServiceLocator()->get(SharedStimulusMediaExtractor::class);
    }
}
