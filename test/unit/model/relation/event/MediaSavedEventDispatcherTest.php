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

namespace oat\taoMediaManager\test\unit\model\relation\event;

use oat\generis\test\TestCase;
use oat\oatbox\event\EventManager;
use oat\oatbox\filesystem\File;
use oat\taoMediaManager\model\MediaService;
use oat\taoMediaManager\model\relation\event\MediaSavedEvent;
use oat\taoMediaManager\model\relation\event\MediaSavedEventDispatcher;
use oat\taoMediaManager\model\sharedStimulus\parser\SharedStimulusMediaExtractor;

class MediaSavedEventDispatcherTest extends TestCase
{
    /** @var MediaSavedEventDispatcher */
    private $subject;

    /** @var EventManager */
    private $eventManager;

    /** @var SharedStimulusMediaExtractor */
    private $sharedStimulusExtractor;

    protected function setUp(): void
    {
        $this->eventManager = $this->createMock(EventManager::class);
        $this->sharedStimulusExtractor = $this->createMock(SharedStimulusMediaExtractor::class);
        $this->subject = new MediaSavedEventDispatcher();
        $this->subject->setServiceLocator($this->getServiceLocatorMock([
            EventManager::SERVICE_ID => $this->eventManager,
            SharedStimulusMediaExtractor::class => $this->sharedStimulusExtractor,
        ]));
    }

    public function testDispatchFromContentWithNonSharedStimulus(): void
    {
        $id = 'fixture-id';
        $mimeType = 'fixture-mimetype';
        $content = 'fixture-content';

        $expectedIds = [];

        $this->mockTriggerMediaSavedEvent($id, $expectedIds);

        $this->subject->dispatchFromContent($id, $mimeType, $content);
    }

    public function testDispatchFromContent(): void
    {
        $id = 'fixture-id';
        $mimeType = MediaService::SHARED_STIMULUS_MIME_TYPE;
        $content = 'fixture-content';

        $fixtureIds = ['media-1', 'media-2'];

        $this->sharedStimulusExtractor
            ->expects($this->once())
            ->method('extractMediaIdentifiers')
            ->with($content)
            ->willReturn($fixtureIds);

        $this->mockTriggerMediaSavedEvent($id, $fixtureIds);

        $this->subject->dispatchFromContent($id, $mimeType, $content);
    }

    public function testDispatchFromFileWithMimeType(): void
    {
        $id = 'fixture-id';
        $fileSource = 'fixture-path';
        $mimeType = 'fixture-mimetype';

        $expectedIds = [];

        $this->mockTriggerMediaSavedEvent($id, $expectedIds);

        $this->subject->dispatchFromFile($id, $fileSource, $mimeType);
    }

    public function testDispatchFromFileWithFile(): void
    {
        $id = 'fixture-id';
        $mimeType = 'fixture-mimetype';
        $fileSource = $this->createMock(File::class);
        $fileSource->expects($this->once())
            ->method('getMimeType')
            ->willReturn($mimeType);

        $expectedIds = [];

        $this->mockTriggerMediaSavedEvent($id, $expectedIds);

        $this->subject->dispatchFromFile($id, $fileSource, '');
    }

    public function testDispatchFromFileWithSharedStimulusFile(): void
    {
        $id = 'fixture-id';

        $fixtureIds = ['media-1', 'media-2'];

        $fileSource = $this->createMock(File::class);
        $fileSource->expects($this->once())
            ->method('getMimeType')
            ->willReturn(MediaService::SHARED_STIMULUS_MIME_TYPE);

        $fileSource->expects($this->once())
            ->method('read')
            ->willReturn('content');

        $this->sharedStimulusExtractor
            ->expects($this->once())
            ->method('extractMediaIdentifiers')
            ->with('content')
            ->willReturn($fixtureIds);

        $this->mockTriggerMediaSavedEvent($id, $fixtureIds);

        $this->subject->dispatchFromFile($id, $fileSource, '');
    }

    private function mockTriggerMediaSavedEvent(string $id, array $referenceIds): void
    {
        $this->eventManager
            ->expects($this->once())
            ->method('trigger')
            ->with(
                $this->callback(
                    function($event) use ($id, $referenceIds) {
                        return ($event instanceof MediaSavedEvent)
                            && ($event->getMediaId() == $id)
                            && ($event->getReferencedMediaIds() == $referenceIds);
                    }
                )
            );
    }
}
