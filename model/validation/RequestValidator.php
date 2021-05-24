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

namespace oat\taoMediaManager\model\validation;

use tao_helpers_File as FileHelper;
use common_exception_Error as ErrorException;
use common_exception_MissingParameter as MissingParameterException;

class RequestValidator
{
    public static function validateRequiredParameters(array $params, array $requiredParams): void
    {
        foreach ($requiredParams as $paramName) {
            if (!isset($params[$paramName])) {
                throw new MissingParameterException($paramName);
            }
        }
    }

    public static function securityCheckPath(string $path): void
    {
        if (!FileHelper::securityCheck($path, true)) {
            throw new ErrorException(sprintf('Invalid path "%s"', $path));
        }
    }
}
