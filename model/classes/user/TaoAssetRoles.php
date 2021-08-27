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

namespace oat\taoMediaManager\model\classes\user;

/**
 * @deprecated Use \oat\taoMediaManager\model\user\TaoAssetRoles
 */
interface TaoAssetRoles
{
    public const MEDIA_MANAGER = 'http://www.tao.lu/Ontologies/TAOMedia.rdf#MediaManagerRole';
    public const ASSET_CLASS_NAVIGATOR = 'http://www.tao.lu/Ontologies/TAOMedia.rdf#AssetClassNavigatorRole';
    public const ASSET_VIEWER = 'http://www.tao.lu/Ontologies/TAOMedia.rdf#AssetViewerRole';
    public const ASSET_EXPORTER = 'http://www.tao.lu/Ontologies/TAOMedia.rdf#AssetExporterRole';
    public const ASSET_PREVIEWER = 'http://www.tao.lu/Ontologies/TAOMedia.rdf#AssetPreviewerRole';
    public const ASSET_PROPERTIES_EDITOR = 'http://www.tao.lu/Ontologies/TAOMedia.rdf#AssetPropertiesEditorRole';
    public const ASSET_CONTENT_CREATOR = 'http://www.tao.lu/Ontologies/TAOMedia.rdf#AssetContentCreatorRole';
}
