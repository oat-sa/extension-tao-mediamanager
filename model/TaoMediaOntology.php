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

namespace oat\taoMediaManager\model;

interface TaoMediaOntology
{
    public const CLASS_URI_MEDIA_ROOT = 'http://www.tao.lu/Ontologies/TAOMedia.rdf#Media';

    public const PROPERTY_LINK = 'http://www.tao.lu/Ontologies/TAOMedia.rdf#Link';

    public const PROPERTY_LANGUAGE = 'http://www.tao.lu/Ontologies/TAOMedia.rdf#Language';

    public const PROPERTY_ALT_TEXT = 'http://www.tao.lu/Ontologies/TAOMedia.rdf#AltText';

    public const PROPERTY_MD5 = 'http://www.tao.lu/Ontologies/TAOMedia.rdf#md5';

    public const PROPERTY_MIME_TYPE = 'http://www.tao.lu/Ontologies/TAOMedia.rdf#mimeType';
    public const PROPERTY_TRANSCRIPTION = 'http://www.tao.lu/Ontologies/TAOMedia.rdf#Transcription';
}
