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

namespace oat\taoMediaManager\model\sharedStimulus\css\factory;

use oat\oatbox\service\ConfigurableService;
use oat\taoMediaManager\model\sharedStimulus\css\LoadCommand;
use oat\taoMediaManager\model\sharedStimulus\css\SaveCommand;
use Psr\Http\Message\ServerRequestInterface;
use common_exception_InvalidArgumentType as InvalidParameterException;
use common_exception_MissingParameter as MissingParameterException;
use common_exception_Error as ErrorException;
use tao_helpers_File;

class CommandFactory extends ConfigurableService
{

    /**
     * @throws ErrorException
     * @throws InvalidParameterException
     * @throws MissingParameterException
     */
    public function makeSaveCommandByRequest(ServerRequestInterface $request): SaveCommand
    {
        $parsedBody = $request->getParsedBody();

        if (!isset($parsedBody['uri'])) {
            throw new MissingParameterException('uri', __METHOD__);
        }

        if (!isset($parsedBody['stylesheetUri'])) {
            throw new MissingParameterException('stylesheetUri', __METHOD__);
        }

        if (!isset($parsedBody['cssJson'])) {
            throw new MissingParameterException('cssJson', __METHOD__);
        }

        $this->securityCheckStylesheetPath($parsedBody['stylesheetUri']);

        $css = json_decode($parsedBody['cssJson'], true);
        if (!is_array($css)) {
            throw new InvalidParameterException(
                __CLASS__,
                \Context::getInstance()->getActionName(),
                3,
                'json encoded array'
            );
        }

        return new SaveCommand(
            $parsedBody['uri'],
            $parsedBody['stylesheetUri'],
            $css
        );
    }

    /**
     * @throws ErrorException
     * @throws MissingParameterException
     */
    public function makeLoadCommandByRequest(ServerRequestInterface $request): LoadCommand
    {
        $parsedBody = $request->getQueryParams();

        if (!isset($parsedBody['uri'])) {
            throw new MissingParameterException('uri', __METHOD__);
        }

        if (!isset($parsedBody['stylesheetUri'])) {
            throw new MissingParameterException('stylesheetUri', __METHOD__);
        }

        $this->securityCheckStylesheetPath($parsedBody['stylesheetUri']);

        return new LoadCommand(
            $parsedBody['uri'],
            $parsedBody['stylesheetUri']
        );
    }

    /**
     * @throws ErrorException
     */
    private function securityCheckStylesheetPath(string $stylesheetUri): void
    {
        if (!tao_helpers_File::securityCheck($stylesheetUri, true)) {
            throw new ErrorException('invalid stylesheet path "' . $stylesheetUri . '"');
        }
    }
}
