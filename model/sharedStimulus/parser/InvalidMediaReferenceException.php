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

namespace oat\taoMediaManager\model\sharedStimulus\parser;

use common_exception_UserReadableException as UserReadableException;
use LogicException;

class InvalidMediaReferenceException extends LogicException implements UserReadableException
{
    /** @var string */
    private $referencedMedia;

    public function __construct($referencedMedia)
    {
        $this->referencedMedia = $referencedMedia;
        $this->message = $this->getUserMessage();
    }

    public function getUserMessage()
    {
        return sprintf('The referenced media "%s" does not exist', $this->referencedMedia);
    }
}
