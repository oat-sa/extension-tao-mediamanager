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
 * Copyright (c) 2022 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoMediaManager\migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\IrreversibleMigration;
use oat\tao\scripts\tools\migrations\AbstractMigration;
use oat\taoMediaManager\model\sharedStimulus\encoder\SharedStimulusMediaEncoder;
use oat\taoMediaManager\scripts\install\RegisterSharedStimulusMediaEncoder;

final class Version202111220850431888_taoMediaManager extends AbstractMigration
{
    public function getDescription(): string
    {
        return sprintf('Perform "%s" ', RegisterSharedStimulusMediaEncoder::class);
    }

    public function up(Schema $schema): void
    {
        $this->runAction(new RegisterSharedStimulusMediaEncoder(), ['service' => SharedStimulusMediaEncoder::class]);
    }

    public function down(Schema $schema): void
    {
        throw new IrreversibleMigration(
            'Reverting this migration requires a code change'
        );
    }
}
