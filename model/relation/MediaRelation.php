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

namespace oat\taoMediaManager\model\relation;

use InvalidArgumentException;
use JsonSerializable;

/**
 * This object is the representation of medias/items used in another media
 */
class MediaRelation implements JsonSerializable
{
    /** @var string  */
    public const ITEM_TYPE = 'item';

    /** @var string  */
    public const MEDIA_TYPE = 'media';

    /** @var string  */
    protected $id;

    /** @var string  */
    protected $label;

    /** @var string  */
    protected $type;

    /**
     * MediaRelation constructor.
     *
     * @param string $type
     * @param $id
     * @param string|null $label
     * @throws InvalidArgumentException if type is not `item` or `asset`
     */
    public function __construct(string $type, string $id, ?string $label = null)
    {
        if (!in_array($type, [self::MEDIA_TYPE, self::ITEM_TYPE])) {
            throw new InvalidArgumentException('Media relation type should be `item` or `media`');
        }
        $this->type = $type;
        $this->id = $id;
        $this->label = $label;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function jsonSerialize(): array
    {
        return [
            'type' => $this->type,
            'id' => $this->id,
            'label' => $this->label ?? '',
        ];
    }
}
