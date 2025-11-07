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
 * Copyright (c) 2025 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoMediaManager\scripts\install;

use oat\oatbox\extension\InstallAction;
use oat\taoMediaManager\model\MediaSource;
use oat\taoMediaManager\model\TaoMediaOntology;
use oat\taoQtiItem\model\service\CreatorConfigFactory;

class ConfigFactoryExtension extends InstallAction
{
    public function __invoke($params)
    {
        $serviceManager = $this->getServiceManager();

        if ($serviceManager->has(CreatorConfigFactory::SERVICE_ID)) {
            $creatorConfigFactory = $serviceManager->get(CreatorConfigFactory::SERVICE_ID);
        } else {
            $creatorConfigFactory = new CreatorConfigFactory();
        }

        $extendedProperties = $creatorConfigFactory->getOption(CreatorConfigFactory::OPTION_EXTENDED_PROPERTIES, []);
        $extendedControlEndpoints = $creatorConfigFactory->getOption(
            CreatorConfigFactory::OPTION_EXTENDED_CONTROL_ENDPOINTS,
            []
        );

        $extendedProperties = array_merge($extendedProperties, [
            'transcriptionMetadata' => TaoMediaOntology::PROPERTY_TRANSCRIPTION,
            'mediaManagerUriPrefix' => MediaSource::SCHEME_NAME,
        ]);

        $extendedControlEndpoints = array_merge($extendedControlEndpoints, [
            'resourceMetadataUrl' => ['tao', 'ResourceMetadata', 'get'],
        ]);

        $creatorConfigFactory->setOption(CreatorConfigFactory::OPTION_EXTENDED_PROPERTIES, $extendedProperties);
        $creatorConfigFactory->setOption(
            CreatorConfigFactory::OPTION_EXTENDED_CONTROL_ENDPOINTS,
            $extendedControlEndpoints
        );

        $serviceManager->register(CreatorConfigFactory::SERVICE_ID, $creatorConfigFactory);
    }
}
