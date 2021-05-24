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
use common_exception_InvalidArgumentType as InvalidParameterException;
use oat\taoMediaManager\model\sharedStimulus\css\dto\SaveStylesheetClasses;

class SaveStylesheetClassesHandler extends ConfigurableService
{
    /**
     * @throws ErrorException
     * @throws MissingParameterException
     */
    public function __invoke(ServerRequestInterface $request): SaveStylesheetClasses
    {
        $params = $request->getParsedBody();
        $this->validate($params);

        $css = json_decode($params['cssJson'], true);

        if (!is_array($css)) {
            throw new InvalidParameterException(
                __CLASS__,
                \Context::getInstance()->getActionName(),
                3,
                'json encoded array'
            );
        }

        return new SaveStylesheetClasses(
            $params['uri'],
            $params['stylesheetUri'],
            $css
        );
    }

    /**
     * @throws ErrorException
     * @throws MissingParameterException
     */
    private function validate(array $params): void
    {
        RequestValidator::validateRequiredParameters($params, ['uri', 'stylesheetUri', 'cssJson']);
        RequestValidator::securityCheckPath($params['stylesheetUri']);
    }
}
