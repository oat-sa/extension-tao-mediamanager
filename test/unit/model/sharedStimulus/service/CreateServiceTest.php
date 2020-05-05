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

namespace oat\taoMediaManager\test\unit\model\sharedStimulus\service;

use common_report_Report;
use core_kernel_classes_Class;
use oat\generis\model\data\Ontology;
use oat\generis\test\TestCase;
use oat\tao\model\upload\UploadService;
use oat\taoMediaManager\model\sharedStimulus\CreateCommand;
use oat\taoMediaManager\model\sharedStimulus\service\CreateService;
use oat\taoMediaManager\model\sharedStimulus\SharedStimulus;
use oat\taoMediaManager\model\SharedStimulusImporter;
use PHPUnit\Framework\MockObject\MockObject;

class CreateServiceTest extends TestCase
{
    private const URI = 'uri';
    private const CLASS_URI = 'uri';
    private const LANGUAGE_URI = 'uri';
    private const NAME = 'name';

    /** @var CreateService */
    private $service;

    /** @var Ontology|MockObject */
    private $ontology;

    /** @var UploadService|MockObject */
    private $uploadService;

    /** @var SharedStimulusImporter|MockObject */
    private $sharedStimulusImporter;

    public function setUp(): void
    {
        $this->ontology = $this->createMock(Ontology::class);
        $this->uploadService = $this->createMock(UploadService::class);
        $this->sharedStimulusImporter = $this->createMock(SharedStimulusImporter::class);
        $this->service = new CreateService();
        $this->service->setServiceLocator(
            $this->getServiceLocatorMock(
                [
                    Ontology::SERVICE_ID => $this->ontology,
                    UploadService::SERVICE_ID => $this->uploadService,
                    SharedStimulusImporter::class => $this->sharedStimulusImporter
                ]
            )
        );

        $kernelClass = $this->createMock(core_kernel_classes_Class::class);
        $kernelClass->method('getInstances')
            ->willReturn(1);

        $this->ontology
            ->method('getClass')
            ->willReturn($kernelClass);

        $childrenReport = $this->createMock(common_report_Report::class);
        $childrenReport->method('getData')
            ->willReturn(
                [
                    'uriResource' => self::URI
                ]
            );

        $importReport = $this->createMock(common_report_Report::class);
        $importReport->method('getChildren')
            ->willReturn([$childrenReport]);

        $this->sharedStimulusImporter
            ->method('import')
            ->willReturn($importReport);
    }

    public function testCreateSharedStimulus(): void
    {
        $command = new CreateCommand(
            self::CLASS_URI,
            self::NAME,
            self::LANGUAGE_URI
        );

        $createdSharedStimulus = $this->service->create($command);

        $this->assertEquals(
            new SharedStimulus(
                self::URI,
                self::NAME,
                self::LANGUAGE_URI
            ),
            $createdSharedStimulus
        );
    }
}
