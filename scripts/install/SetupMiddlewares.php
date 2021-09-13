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

namespace oat\taoMediaManager\scripts\install;

use oat\oatbox\extension\InstallAction;
use oat\oatbox\reporting\Report;
use oat\tao\model\Middleware\Context\MiddlewareContext;
use oat\tao\model\Middleware\Context\OpenApiMiddlewareContext;
use oat\tao\model\Middleware\MiddlewareManager;
use oat\tao\model\Middleware\MiddlewareRequestHandler;
use oat\tao\model\Middleware\OpenAPISchemaValidateRequestMiddleware;

class SetupMiddlewares extends InstallAction
{
    private const OPENAPI_SPEC_PATH = ROOT_PATH . '/taoMediaManager/doc/taoMediaManagerApi.yml';

    public function __invoke($params)
    {
        $openApiMiddleware = $this->getServiceManager()->get(OpenAPISchemaValidateRequestMiddleware::SERVICE_ID)
            ->addSchema($this->getOpenApiMiddlewareContext());

        $middlewareHandler = $this->getServiceManager()->get(MiddlewareManager::class)
            ->append($this->getMiddlewareContext())->getMiddlewareHandler();

        $this->getServiceManager()->register(OpenAPISchemaValidateRequestMiddleware::SERVICE_ID, $openApiMiddleware);
        $this->getServiceManager()->register(MiddlewareRequestHandler::SERVICE_ID, $middlewareHandler);

        return Report::createSuccess('OpenAPIValidationMiddleware successfully installed');
    }

    private function getMiddlewareContext(): MiddlewareContext
    {
        return new MiddlewareContext(
            [
                MiddlewareContext::PARAM_ROUTE => '/taoMediaManager/SharedStimulus/patch',
                MiddlewareContext::PARAM_MIDDLEWARE_ID => OpenAPISchemaValidateRequestMiddleware::SERVICE_ID
            ]
        );
    }

    private function getOpenApiMiddlewareContext(): OpenApiMiddlewareContext
    {
        return new OpenApiMiddlewareContext([
            OpenApiMiddlewareContext::PARAM_ROUTE => '/taoMediaManager/SharedStimulus/patch',
            OpenApiMiddlewareContext::PARAM_SCHEMA_PATH => self::OPENAPI_SPEC_PATH,
        ]);
    }
}
