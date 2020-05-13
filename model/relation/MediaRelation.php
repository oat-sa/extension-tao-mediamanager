<?php

declare(strict_types=1);

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
 *
 */

namespace oat\taoMediaManager\model\relation;

use InvalidArgumentException;
use JsonSerializable;

class MediaRelation implements JsonSerializable
{
    public const ITEM_TYPE = 'item';
    public const ASSET_TYPE = 'asset';

    protected $id;

    protected $label;

    protected $type;

    public function __construct(string $type, $id, ?string $label)
    {
        if (!in_array($type, [self::ASSET_TYPE, self::ITEM_TYPE])) {
            throw new InvalidArgumentException('Media relation type should be `item` or `asset`');
        }
        $this->type = $type;
        $this->id = $id;
        $this->label = $label;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getLabel()
    {
        return $this->label;
    }

    public function jsonSerialize()
    {
        return [
            'type' => $this->type,
            'id' => $this->id,
            'label' => $this->label ?? '',
        ];
    }
}
