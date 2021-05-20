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

use tao_helpers_File;
use oat\oatbox\service\ConfigurableService;
use Psr\Http\Message\ServerRequestInterface;
use common_exception_Error as ErrorException;
use oat\taoMediaManager\model\sharedStimulus\css\LoadResourceUri;
use oat\taoMediaManager\model\sharedStimulus\css\SaveResourceUri;
use common_exception_MissingParameter as MissingParameterException;
use common_exception_InvalidArgumentType as InvalidParameterException;
use oat\taoMediaManager\model\sharedStimulus\css\GetStylesheetsResourceUri;
use oat\taoMediaManager\model\sharedStimulus\css\LoadStylesheetResourceUri;

class CommandFactory extends ConfigurableService
{
    public function makeSaveCommandByRequest(ServerRequestInterface $request): SaveResourceUri
    {
        $params = $request->getParsedBody();
        $this->validateParams($params, ['uri', 'stylesheetUri', 'cssJson'],__METHOD__);
        $this->securityCheckStylesheetPath($params['stylesheetUri']);

        $css = json_decode($params['cssJson'], true);

        if (!is_array($css)) {
            throw new InvalidParameterException(
                __CLASS__,
                \Context::getInstance()->getActionName(),
                3,
                'json encoded array'
            );
        }

        return new SaveResourceUri(
            $params['uri'],
            $params['stylesheetUri'],
            $css
        );
    }

    public function makeLoadCommandByRequest(ServerRequestInterface $request): LoadResourceUri
    {
        $params = $request->getQueryParams();
        $this->validateParams($params, ['uri', 'stylesheetUri'],__METHOD__);
        $this->securityCheckStylesheetPath($params['stylesheetUri']);

        return new LoadResourceUri(
            $params['uri'],
            $params['stylesheetUri']
        );
    }

    public function makeGetStylesheetsCommandByRequest(ServerRequestInterface $request): GetStylesheetsResourceUri
    {
        $params = $request->getQueryParams();
        $this->validateParams($params, ['uri'], __METHOD__);

        return new GetStylesheetsResourceUri($params['uri']);
    }

    public function makeLoadStylesheetCommandByRequest(ServerRequestInterface $request): LoadStylesheetResourceUri
    {
        $params = $request->getQueryParams();
        $this->validateParams($params, ['uri', 'stylesheet'],__METHOD__);
        $this->securityCheckStylesheetPath($params['stylesheet']);

        return new LoadStylesheetResourceUri($params['uri'], $params['stylesheet']);
    }

    private function validateParams(array $params, array $requiredParams, string $method): void
    {
        foreach ($requiredParams as $paramName) {
            if (!isset($params[$paramName])) {
                throw new MissingParameterException($paramName, $method);
            }
        }
    }

    private function securityCheckStylesheetPath(string $stylesheetUri): void
    {
        if (!tao_helpers_File::securityCheck($stylesheetUri, true)) {
            throw new ErrorException(sprintf('Invalid stylesheet path "%s"', $stylesheetUri));
        }
    }
}
