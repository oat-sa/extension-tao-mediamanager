<?php

declare(strict_types=1);

namespace oat\taoMediaManager\migrations;

use Doctrine\DBAL\Schema\Schema;
use oat\tao\model\Middleware\Context\MiddlewareContext;
use oat\tao\model\Middleware\Context\OpenApiMiddlewareContext;
use oat\tao\model\Middleware\MiddlewareManager;
use oat\tao\model\Middleware\MiddlewareRequestHandler;
use oat\tao\model\Middleware\OpenAPISchemaValidateRequestMiddleware;
use oat\tao\scripts\tools\migrations\AbstractMigration;

final class Version202109081901591888_taoMediaManager extends AbstractMigration
{
    private const OPENAPI_SPEC_PATH = ROOT_PATH . '/taoMediaManager/doc/taoMediaManagerApi.yml';

    public function getDescription(): string
    {
        return 'OpenAPI Schema validation middleware registration for SharedStimulus API';
    }

    public function up(Schema $schema): void
    {
        $openApiMiddleware = $this->getServiceManager()->get(OpenAPISchemaValidateRequestMiddleware::SERVICE_ID)
            ->addSchema($this->getOpenApiMiddlewareContext());

        $middlewareHandler = $this->getServiceManager()->get(MiddlewareManager::class)
            ->append($this->getMiddlewareContext())->getMiddlewareHandler();

        $this->getServiceManager()->register(OpenAPISchemaValidateRequestMiddleware::SERVICE_ID, $openApiMiddleware);
        $this->getServiceManager()->register(MiddlewareRequestHandler::SERVICE_ID, $middlewareHandler);
    }

    public function down(Schema $schema): void
    {
        /** @var OpenAPISchemaValidateRequestMiddleware $middleware */
        $middleware = $this->getServiceManager()->get(OpenAPISchemaValidateRequestMiddleware::SERVICE_ID);
        $middleware->removeSchema($this->getOpenApiMiddlewareContext());

        /** @var MiddlewareManager $middlewareManager */
        $middlewareManager = $this->getServiceManager()->get(MiddlewareManager::class);
        $handler = $middlewareManager->detach($this->getMiddlewareContext())->getMiddlewareHandler();

        $this->getServiceManager()->register(OpenAPISchemaValidateRequestMiddleware::SERVICE_ID, $middleware);
        $this->getServiceManager()->register(MiddlewareRequestHandler::SERVICE_ID, $handler);
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
