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
 * Copyright (c) 2021-2022 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoMediaManager\model\classes\ServiceProvider;

use oat\generis\model\data\Ontology;
use oat\generis\model\DependencyInjection\ContainerServiceProviderInterface;
use oat\oatbox\log\LoggerService;
use oat\tao\model\accessControl\ActionAccessControl;
use oat\tao\model\accessControl\PermissionChecker;
use oat\tao\model\resources\Service\ClassCopierProxy;
use oat\tao\model\resources\Service\ClassMetadataCopier;
use oat\tao\model\resources\Service\ClassMetadataMapper;
use oat\tao\model\resources\Service\ClassPropertyCopier;
use oat\tao\model\resources\Service\InstanceCopier;
use oat\tao\model\resources\Service\InstanceMetadataCopier;
use oat\tao\model\resources\Service\RootClassesListService;
use oat\taoItems\model\Copier\ClassCopier;
use oat\tao\model\resources\Service\ClassCopier as TaoClassCopier;
use oat\taoMediaManager\model\accessControl\MediaPermissionService;
use oat\taoMediaManager\model\classes\Copier\AssetClassCopier;
use oat\taoMediaManager\model\classes\Copier\AssetContentCopier;
use oat\taoMediaManager\model\classes\Copier\AssetInstanceMetadataCopier;
use oat\taoMediaManager\model\fileManagement\FileManagement;
use oat\taoMediaManager\model\fileManagement\FileSourceUnserializer;
use oat\taoMediaManager\model\MediaService;
use oat\taoMediaManager\model\sharedStimulus\css\repository\StylesheetRepository;
use oat\taoMediaManager\model\sharedStimulus\css\service\ListStylesheetsService;
use oat\taoMediaManager\model\sharedStimulus\factory\CommandFactory;
use oat\taoMediaManager\model\sharedStimulus\service\CopyService;
use oat\taoMediaManager\model\sharedStimulus\service\StoreService;
use oat\taoMediaManager\model\sharedStimulus\specification\SharedStimulusResourceSpecification;
use oat\taoMediaManager\model\TaoMediaOntology;
use oat\taoMediaManager\model\Specification\MediaClassSpecification;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

class MediaServiceProvider implements ContainerServiceProviderInterface
{
    public function __invoke(ContainerConfigurator $configurator): void
    {
        $services = $configurator->services();

        $services
            ->set(CopyService::class, CopyService::class)
            ->public()
            ->args(
                [
                    service(Ontology::SERVICE_ID),
                    service(MediaService::class),
                    service(StoreService::class),
                    service(ListStylesheetsService::class),
                    service(StylesheetRepository::class),
                    service(FileSourceUnserializer::class),
                    service(FileManagement::SERVICE_ID),
                ]
            );

        $services
            ->set(MediaPermissionService::class, MediaPermissionService::class)
            ->public()
            ->args(
                [
                    service(ActionAccessControl::class),
                    service(PermissionChecker::class),
                ]
            );

        $services
            ->set(MediaClassSpecification::class, MediaClassSpecification::class)
            ->public();

        $services
            ->get(InstanceMetadataCopier::class)
            ->call(
                'addPropertyUriToBlacklist',
                [
                    TaoMediaOntology::PROPERTY_LINK
                ]
            );

        $services
            ->set(AssetInstanceMetadataCopier::class, AssetInstanceMetadataCopier::class)
            ->args(
                [
                    service(InstanceMetadataCopier::class),
                ]
            );

        $services
            ->set(AssetContentCopier::class, AssetContentCopier::class)
            ->args(
                [
                    service(SharedStimulusResourceSpecification::class),
                    service(CommandFactory::class),
                    service(CopyService::class),
                    DEFAULT_LANG,
                ]
            );

        $services
            ->set(InstanceCopier::class . '::ASSETS', InstanceCopier::class)
            ->args(
                [
                    service(AssetInstanceMetadataCopier::class),
                ]
            )
            ->call(
                'withInstanceContentCopier',
                [
                    service(AssetContentCopier::class),
                ]
            );

        $services
            ->set(TaoClassCopier::class . '::ASSETS', TaoClassCopier::class)
            ->share(false)
            ->args(
                [
                    service(RootClassesListService::class),
                    service(ClassMetadataCopier::class),
                    service(InstanceCopier::class . '::ASSETS'),
                    service(ClassMetadataMapper::class),
                ]
            );

        $services
            ->set(AssetClassCopier::class, AssetClassCopier::class)
            ->public()
            ->args(
                [
                    service(LoggerService::SERVICE_ID),
                    service(RootClassesListService::class),
                    service(MediaClassSpecification::class),
                    service(TaoClassCopier::class . '::ASSETS'),
                ]
            );

        $services
            ->get(ClassCopierProxy::class)
            ->call(
                'addClassCopier',
                [
                    TaoMediaOntology::CLASS_URI_MEDIA_ROOT,
                    service(AssetClassCopier::class),
                ]
            );
    }
}
