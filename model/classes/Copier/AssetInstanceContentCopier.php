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

namespace oat\taoMediaManager\model\classes\Copier;

use oat\tao\model\resources\Contract\InstanceContentCopierInterface;
use core_kernel_classes_Resource;

class AssetInstanceContentCopier implements InstanceContentCopierInterface
{
    public function copy(
        core_kernel_classes_Resource $instance,
        core_kernel_classes_Resource $destinationInstance
    ): void {
        // @todo Asset copying (i.e. copying files) not implemented
        // We may create a DummyInstanceContentCopier in core instead

        // @fixme Still we get an error while trying to read the files on the
        //        copied assets ("No file found for this media"), we'll probably
        //        need to "link" the files here (update some properties to point
        //        to the same files as the former asset)
    }
}
