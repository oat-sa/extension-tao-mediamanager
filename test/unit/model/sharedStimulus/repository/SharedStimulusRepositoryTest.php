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

namespace oat\taoMediaManager\test\unit\model\sharedStimulus\repository;

use core_kernel_classes_Literal;
use core_kernel_classes_Property;
use core_kernel_classes_Resource;
use oat\generis\model\data\Ontology;
use oat\generis\test\TestCase;
use oat\taoMediaManager\model\fileManagement\FileManagement;
use oat\taoMediaManager\model\sharedStimulus\FindQuery;
use oat\taoMediaManager\model\sharedStimulus\repository\SharedStimulusRepository;
use oat\taoMediaManager\model\sharedStimulus\SharedStimulus;
use PHPUnit\Framework\MockObject\MockObject;

class SharedStimulusRepositoryTest extends TestCase
{
    private const URI = 'uri';
    private const LANGUAGE_URI = 'uri';
    private const NAME = 'name';
    private const CONTENT = 'content';

    /** @var SharedStimulusRepository */
    private $repository;

    /** @var Ontology|MockObject */
    private $ontology;

    /** @var FileManagement|MockObject */
    private $fileManagement;

    public function setUp(): void
    {
        $this->ontology = $this->createMock(Ontology::class);
        $this->fileManagement = $this->createMock(FileManagement::class);
        $this->repository = new SharedStimulusRepository();
        $this->repository->setServiceLocator(
            $this->getServiceLocatorMock(
                [
                    Ontology::SERVICE_ID => $this->ontology,
                    FileManagement::SERVICE_ID => $this->fileManagement,
                ]
            )
        );
    }

    public function testFindSharedStimulus(): void
    {
        $resource = $this->createMock(core_kernel_classes_Resource::class);

        $language = $this->createMock(core_kernel_classes_Resource::class);
        $language->method('getUri')
            ->willReturn(self::LANGUAGE_URI);

        $this->ontology
            ->method('getResource')
            ->willReturn($resource);

        $resource->method('getProperty')
            ->willReturn($this->createMock(core_kernel_classes_Property::class));

        $resource->method('getUniquePropertyValue')
            ->willReturnOnConsecutiveCalls()
            ->willReturn(
                new core_kernel_classes_Literal(self::NAME),
                $language,
                new core_kernel_classes_Literal(self::CONTENT)
            );

        $this->fileManagement
            ->method('getFileStream')
            ->willReturn(self::CONTENT);

        $this->assertEquals(
            new SharedStimulus(
                self::URI,
                self::NAME,
                self::LANGUAGE_URI,
                self::CONTENT
            ),
            $this->repository->find(new FindQuery(self::URI))
        );
    }
}
