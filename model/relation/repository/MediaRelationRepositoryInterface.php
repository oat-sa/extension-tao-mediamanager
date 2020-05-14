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

namespace oat\taoMediaManager\model\relation\repository;

use oat\taoMediaManager\model\relation\MediaRelation;
use oat\taoMediaManager\model\relation\MediaRelationCollection;
use oat\taoMediaManager\model\relation\repository\query\FindAllQuery;

/**
 * Interface to abstract media relation.
 *
 * Once implemented, the service is configured based on self::SERVICE_ID
 *
 * @package oat\taoMediaManager\model\relation\repository
 */
interface MediaRelationRepositoryInterface
{
    public const SERVICE_ID = 'taoMediaManager/MediaRelationRepository';

    /**
     * Find all RelationMedia based on query object
     *
     * In case of no relation found, return empty array
     *
     * @param FindAllQuery $query
     * @return MediaRelationCollection
     */
    public function findAll(FindAllQuery $query): MediaRelationCollection;

    /**
     * Persist MediaRelation
     *
     * @param MediaRelation $relation
     * @return bool
     */
    public function save(MediaRelation $relation): void;

    /**
     * Remove MediaRelation
     *
     * @param MediaRelation $relation
     * @return bool
     */
    public function remove(MediaRelation $relation): void;
}
