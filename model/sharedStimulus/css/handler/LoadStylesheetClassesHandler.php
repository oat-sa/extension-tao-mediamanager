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

namespace oat\taoMediaManager\model\sharedStimulus\css\handler;

use oat\oatbox\service\ConfigurableService;
use Psr\Http\Message\ServerRequestInterface;
use common_exception_Error as ErrorException;
use oat\taoMediaManager\model\validation\RequestValidator;
use common_exception_MissingParameter as MissingParameterException;
use oat\taoMediaManager\model\sharedStimulus\css\dto\LoadStylesheet;

class LoadStylesheetClassesHandler extends ConfigurableService
{
    /**
     * @throws ErrorException
     * @throws MissingParameterException
     */
    public function __invoke(ServerRequestInterface $request): LoadStylesheet
    {
        $params = $request->getQueryParams();
        $this->validate($params);

        return new LoadStylesheet(
            $params['uri'],
            $params['stylesheetUri']
        );
    }

    /**
     * @throws ErrorException
     * @throws MissingParameterException
     */
    private function validate(array $params): void
    {
        RequestValidator::validateRequiredParameters($params, ['uri', 'stylesheetUri']);
        RequestValidator::securityCheckPath($params['stylesheetUri']);
    }
}
