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
use oat\tao\model\accessControl\ActionAccessControl;
use oat\tao\model\accessControl\PermissionChecker;
use oat\taoMediaManager\model\accessControl\MediaPermissionService;
use oat\taoMediaManager\model\classes\Copier\AssetCopier;
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
            ->set(AssetCopier::class, AssetCopier::class)
            ->public()
            ->args(
                [
                    // @todo Add parameters as-needed or remove the args() call
                ]
            );
    }
}
