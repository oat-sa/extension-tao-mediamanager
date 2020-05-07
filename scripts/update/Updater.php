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
 * Copyright (c) 2014 (original work) Open Assessment Technologies SA;
 *
 *
 */

namespace oat\taoMediaManager\scripts\update;

use oat\tao\scripts\update\OntologyUpdater;

class Updater extends \common_ext_ExtensionUpdater
{
    /**
     * @param string $initialVersion
     * @return string|void
     * @throws \common_exception_NotImplemented
     */
    public function update($initialVersion)
    {
        if ($this->isBetween('0.0.0', '0.2.5')) {
            throw new \common_exception_NotImplemented('Updates from versions prior to Tao 3.1 are not longer supported, please update to Tao 3.1 first');
        }

        $this->skip('0.3.0', '9.3.0');

        if ($this->isVersion('9.3.0')) {
            OntologyUpdater::syncModels();
            $this->setVersion('10.0.0');
        }

        $this->skip('10.0.0', '10.1.0');
    }
}
