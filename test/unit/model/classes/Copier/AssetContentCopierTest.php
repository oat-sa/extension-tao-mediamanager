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
 * Copyright (c) 2022 (original work) Open Assessment Technologies SA.
 */

declare(strict_types=1);

namespace oat\taoMediaManager\test\unit\model;

use oat\tao\model\resources\Contract\ClassPropertyCopierInterface;
use oat\tao\model\resources\Contract\RootClassesListServiceInterface;
use oat\taoMediaManager\model\classes\Copier\AssetClassCopier;
use oat\taoMediaManager\model\Specification\MediaClassSpecification;
use oat\taoMediaManager\model\TaoMediaOntology;
use oat\taoItems\model\Copier\ItemClassCopier;
use oat\tao\model\resources\Contract\ClassCopierInterface;
use core_kernel_classes_Class;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use InvalidArgumentException;

class AssetClassCopierTest extends TestCase
{
    /** @var ClassCopierInterface|MockObject */
    private $taoClassCopier;

    /** @var core_kernel_classes_Class|MockObject */
    private $source;

    /** @var core_kernel_classes_Class|MockObject */
    private $target;

    /** @var ItemClassCopier */
    private $sut;

    protected function setUp(): void
    {
        $this->taoClassCopier = $this->createMock(ClassCopierInterface::class);
        $this->source = $this->createMock(core_kernel_classes_Class::class);
        $this->target = $this->createMock(core_kernel_classes_Class::class);

        $sharedStimulusSpecification = $this->createMock(
            SharedStimulusResourceSpecification::class
        );
        $sharedStimulusSpecification
            ->method('isSatisfiedBy')
            ->willReturnCallback(function (core_kernel_classes_Resource $asset) {
                return in_array(
                    $asset->getUri(),
                    [
                        'http://asset.root/1',
                        'http://asset.root/1/1',
                        'http://asset.root/2',
                        'http://asset.root/2/1',
                        'http://asset.root/1/c1',
                        'http://asset.root/1/c2',
                    ]
                );
            });

        $this->sut = new AssetContentCopier(

            $this->getRootClassesListServiceMock(),
            $sharedStimulusSpecification,
            $this->taoClassCopier
        );
    }

    public function testSomething(): void
    {
        // @todo
    }
}
