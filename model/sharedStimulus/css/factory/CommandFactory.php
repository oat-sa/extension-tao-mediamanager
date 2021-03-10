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

namespace oat\taoMediaManager\model\sharedStimulus\css\factory;

use common_exception_InvalidArgumentType;
use common_exception_MissingParameter;
use oat\oatbox\service\ConfigurableService;
use oat\taoMediaManager\model\sharedStimulus\css\LoadCommand;
use oat\taoMediaManager\model\sharedStimulus\css\SaveCommand;
use Psr\Http\Message\ServerRequestInterface;

class CommandFactory extends ConfigurableService
{
    public function makeSaveCommandByRequest(ServerRequestInterface $request): SaveCommand
    {
        $parsedBody = $request->getParsedBody();

        if (!isset($parsedBody['uri'])) {
            throw new common_exception_MissingParameter('uri', __METHOD__);
        }

        if (!isset($parsedBody['stylesheetUri'])) {
            throw new common_exception_MissingParameter('stylesheetUri', __METHOD__);
        }

        if (!isset($parsedBody['cssJson'])) {
            throw new common_exception_MissingParameter('cssJson', __METHOD__);
        }

        $css = json_decode($parsedBody['cssJson'], true);
        if (!is_array($css)) {
            throw new common_exception_InvalidArgumentType(
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

    public function makeLoadCommandByRequest(ServerRequestInterface $request): LoadCommand
    {
        $parsedBody = $request->getParsedBody();


        if (!isset($parsedBody['uri'])) {
            throw new common_exception_MissingParameter('uri', __METHOD__);
        }

        if (!isset($parsedBody['stylesheetUri'])) {
            throw new common_exception_MissingParameter('stylesheetUri', __METHOD__);
        }

        return new LoadCommand(
            $parsedBody['uri'],
            $parsedBody['stylesheetUri']
        );
    }
}
