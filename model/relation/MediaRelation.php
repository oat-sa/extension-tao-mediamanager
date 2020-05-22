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

class MediaRelation implements JsonSerializable
{
    /** @var string */
    public const ITEM_TYPE = 'item';

    /** @var string */
    public const MEDIA_TYPE = 'media';

    /** @var string */
    private $id;

    /** @var string */
    private $sourceId;

    /** @var string */
    private $label;

    /** @var string */
    private $type;

    /**
     * @throws InvalidArgumentException
     */
    public function __construct(string $type, string $id, string $label = null)
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

    public function isMedia(): bool
    {
        return $this->type === self::MEDIA_TYPE;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function withSourceId(string $sourceId): self
    {
        $this->sourceId = $sourceId;

        return $this;
    }

    public function getSourceId(): string
    {
        return $this->sourceId;
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
