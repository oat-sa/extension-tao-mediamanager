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

use oat\tao\model\resources\Contract\InstanceMetadataCopierInterface;
use oat\taoMediaManager\model\classes\Copier\AssetInstanceMetadataCopier;
use oat\taoMediaManager\model\TaoMediaOntology;
use core_kernel_classes_Literal;
use core_kernel_classes_Property;
use core_kernel_classes_Resource;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AssetInstanceMetadataCopierTest extends TestCase
{
    private const LANGUAGE_CODE = 'fr-CA';
    private const LANGUAGE_URI = 'http://www.tao.lu/Ontologies/TAO.rdf#Langfr-CA';

    /** @var AssetInstanceMetadataCopier */
    private $sut;

    /** @var core_kernel_classes_Resource|MockObject */
    private $source;

    /** @var core_kernel_classes_Resource|MockObject */
    private $target;

    /** @var core_kernel_classes_Property|MockObject */
    private $propertyAltText;

    /** @var core_kernel_classes_Property|MockObject */
    private $propertyLanguage;

    /** @var core_kernel_classes_Property|MockObject */
    private $propertyLink;

    /** @var core_kernel_classes_Property|MockObject */
    private $propertyMD5;

    /** @var core_kernel_classes_Property|MockObject */
    private $propertyMime;

    public function setUp(): void
    {
        $this->propertyAltText = $this->mockLgDependentProperty(
            'Alt Text',
            TaoMediaOntology::PROPERTY_ALT_TEXT
        );
        $this->propertyLanguage = $this->mockLgDependentProperty(
            'Language',
            TaoMediaOntology::PROPERTY_LANGUAGE
        );
        $this->propertyLink = $this->mockProperty(
            '123456789abcdef123456.mp4',
            TaoMediaOntology::PROPERTY_LINK
        );
        $this->propertyMD5 = $this->mockProperty(
            'c38cd6d9c873bf072d9753d730f87ce',
            TaoMediaOntology::PROPERTY_MD5
        );
        $this->propertyMime = $this->mockProperty(
            'video/mp4',
            TaoMediaOntology::PROPERTY_MIME_TYPE
        );

        $this->target = $this->mockResource('http://test.resources/target');
        $this->source = $this->mockResource('http://test.resources/source');

        $this->source
            ->method('getProperty')
            ->willReturnMap([
                [TaoMediaOntology::PROPERTY_ALT_TEXT, $this->propertyAltText],
                [TaoMediaOntology::PROPERTY_LANGUAGE, $this->propertyLanguage],
                [TaoMediaOntology::PROPERTY_LINK, $this->propertyLink],
                [TaoMediaOntology::PROPERTY_MD5, $this->propertyMD5],
                [TaoMediaOntology::PROPERTY_MIME_TYPE, $this->propertyMime],
            ]);

        $this->source
            ->method('getUsedLanguages')
            ->willReturnCallback(function (core_kernel_classes_Property $p): array {
                switch ($p->getUri()) {
                    case TaoMediaOntology::PROPERTY_ALT_TEXT:
                    case TaoMediaOntology::PROPERTY_LANGUAGE:
                        return [self::LANGUAGE_CODE];
                }

                // Called getUsedLanguages for a non-lg dependent property
                //
                $this->fail(
                    "Unexpected call to getUsedLanguages for {$p->getUri()}"
                );
            });

        $nestedCopier = $this->createMock(InstanceMetadataCopierInterface::class);

        $nestedCopier
            ->expects($this->once())
            ->method('copy')
            ->with($this->source, $this->target);

        $this->sut = new AssetInstanceMetadataCopier($nestedCopier);
    }

    public function testTargetPropertiesAreSet(): void
    {
        $this->source
            ->method('getPropertyValuesCollection')
            ->willReturnCallback(
                function (core_kernel_classes_Property $p, array $opts) {
                    return $this->getValueCollectionForProperty($p, $opts);
                }
            );

        $this->target
            ->expects($this->exactly(3))
            ->method('setPropertyValue')
            ->withConsecutive(
                [$this->propertyMD5, 'c38cd6d9c873bf072d9753d730f87ce'],
                [$this->propertyMime, 'video/mp4'],
                [$this->propertyLink, '123456789abcdef123456.mp4']
            );

        $this->target
            ->expects($this->exactly(2))
            ->method('setPropertyValueByLg')
            ->withConsecutive(
                [$this->propertyAltText, 'Alt Text', self::LANGUAGE_CODE],
                [$this->propertyLanguage, self::LANGUAGE_URI, self::LANGUAGE_CODE]
            );

        $this->sut->copy($this->source, $this->target);
    }

    /**
     * @return core_kernel_classes_Literal[]|core_kernel_classes_Resource[]|MockObject[]|string[]
     */
    private function getValueCollectionForProperty(
        core_kernel_classes_Property $p,
        array $opts
    ): array {
        switch ($p->getUri()) {
            case TaoMediaOntology::PROPERTY_ALT_TEXT:
                $this->assertTrue($opts === ['lg' => self::LANGUAGE_CODE]);

                return ['Alt Text'];

            case TaoMediaOntology::PROPERTY_LANGUAGE:
                $this->assertTrue($opts === ['lg' => self::LANGUAGE_CODE]);

                return [$this->mockResource(self::LANGUAGE_URI)];

            case TaoMediaOntology::PROPERTY_MIME_TYPE:
                $this->assertTrue($opts === []);

                return [$this->mockLiteral('video/mp4')];

            case TaoMediaOntology::PROPERTY_MD5:
                $this->assertTrue($opts === []);

                return [
                    $this->mockLiteral('c38cd6d9c873bf072d9753d730f87ce')
                ];

            case TaoMediaOntology::PROPERTY_LINK:
                $this->assertTrue($opts === []);

                return [
                    $this->mockLiteral('123456789abcdef123456.mp4')
                ];
        }

        // Called getUsedLanguages for a non-lg dependent property
        //
        $this->fail("Unexpected call to getUsedLanguages for {$p->getUri()}");
    }

    /**
     * @return core_kernel_classes_Property|MockObject
     */
    private function mockLgDependentProperty(string $label, string $uri): MockObject
    {
        return $this->createConfiguredMock(
            core_kernel_classes_Property::class,
            [
                'getLabel' => $label,
                'getUri' => $uri,
                'isLgDependent' => true,
            ]
        );
    }

    /**
     * @return core_kernel_classes_Property|MockObject
     */
    private function mockProperty(string $label, string $uri): MockObject
    {
        return $this->createConfiguredMock(
            core_kernel_classes_Property::class,
            [
                'getLabel' => $label,
                'getUri' => $uri,
                'isLgDependent' => false,
            ]
        );
    }

    /**
     * @return core_kernel_classes_Resource|MockObject
     */
    private function mockResource(string $uri): MockObject
    {
        return $this->createConfiguredMock(
            core_kernel_classes_Resource::class,
            ['exists' => true, 'getUri' => $uri]
        );
    }

    /**
     * @return core_kernel_classes_Literal|MockObject
     */
    private function mockLiteral(string $value): MockObject
    {
        return $this->createConfiguredMock(
            core_kernel_classes_Literal::class,
            ['__toString' => $value]
        );
    }
}
