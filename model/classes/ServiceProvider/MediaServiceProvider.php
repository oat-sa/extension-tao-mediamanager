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
 * Copyright (c) 2021 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoMediaManager\model\classes\ServiceProvider;

use oat\generis\model\DependencyInjection\ContainerServiceProviderInterface;
use oat\oatbox\log\LoggerService;
use oat\tao\model\accessControl\ActionAccessControl;
use oat\tao\model\accessControl\PermissionChecker;
use oat\tao\model\resources\Service\ClassCopierProxy;
use oat\tao\model\resources\Service\ClassPropertyCopier;
use oat\tao\model\resources\Service\InstanceCopier as TaoCoreInstanceCopier;
use oat\tao\model\resources\Service\RootClassesListService;
use oat\tao\model\TaoOntology;
use oat\tao\test\integration\ServiceTest;
use oat\taoItems\model\Copier\ClassCopier;
use oat\taoMediaManager\model\accessControl\MediaPermissionService;
use oat\taoMediaManager\model\classes\Copier\AssetClassCopier;
use oat\taoMediaManager\model\classes\Copier\AssetInstanceContentCopier;
use oat\taoMediaManager\model\classes\Copier\AssetInstanceCopier;
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
            ->set(MediaPermissionService::class, MediaPermissionService::class)
            ->public()
            ->args(
                [
                    service(ActionAccessControl::class),
                    service(PermissionChecker::class),
                ]
            );

        $services
            ->set(AssetInstanceContentCopier::class, AssetInstanceContentCopier::class)
            ->public();

        $services
            ->set(AssetInstanceCopier::class, AssetInstanceCopier::class)
            ->public()
            ->args(
                [
                    service(TaoCoreInstanceCopier::class),
                    service(AssetInstanceContentCopier::class),
                ]
            );

        $services
            ->set(MediaClassSpecification::class, MediaClassSpecification::class)
            ->public();

        $services
            ->set(AssetClassCopier::class, AssetClassCopier::class)
            ->public()
            ->args(
                [
                    service(LoggerService::SERVICE_ID),
                    service(RootClassesListService::class),
                    service(MediaClassSpecification::class),
                    service(ClassPropertyCopier::class),
                    service(AssetInstanceCopier::class),
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
