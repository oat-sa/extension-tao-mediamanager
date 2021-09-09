<?php

declare(strict_types=1);

namespace oat\taoMediaManager\migrations;

use Doctrine\DBAL\Schema\Schema;
use oat\tao\model\Middleware\MiddlewareChainBuilder;
use oat\tao\model\Middleware\ValidateRequestMiddleware;
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
        $middleware = new ValidateRequestMiddleware();
        $middleware->setOption(ValidateRequestMiddleware::SCHEMA_MAP, [self::OPENAPI_SPEC_PATH,]);
        $this->getServiceManager()->register(ValidateRequestMiddleware::SERVICE_ID, $middleware);

        $chainBuilder = $this->getServiceLocator()->get(MiddlewareChainBuilder::SERVICE_ID);
        $map = $chainBuilder->getOption(MiddlewareChainBuilder::MAP);

        $chainBuilder->setOption(
            MiddlewareChainBuilder::MAP,
            array_merge_recursive($map, [
                '/taoMediaManager/SharedStimulus/patch' => [ValidateRequestMiddleware::SERVICE_ID]
            ])
        );

        $this->getServiceManager()->register(MiddlewareChainBuilder::SERVICE_ID, $chainBuilder);
    }

    public function down(Schema $schema): void
    {
        $middleware = $this->getServiceManager()->get(ValidateRequestMiddleware::SERVICE_ID);

        $map = $middleware->getOption(ValidateRequestMiddleware::SCHEMA_MAP);
        if (($key = array_search(self::OPENAPI_SPEC_PATH, $map)) !== false) {
            unset($map[$key]);
        }
        $middleware->setOption(ValidateRequestMiddleware::SCHEMA_MAP, $map);

        $this->getServiceManager()->register(ValidateRequestMiddleware::SERVICE_ID, $middleware);

        $chainBuilder = $this->getServiceLocator()->get(MiddlewareChainBuilder::SERVICE_ID);
        $map = $chainBuilder->getOption(MiddlewareChainBuilder::MAP);
        unset($map['/taoMediaManager/SharedStimulus/patch']);

        $chainBuilder->setOption(MiddlewareChainBuilder::MAP, $map);

        $this->getServiceManager()->register(MiddlewareChainBuilder::SERVICE_ID, $chainBuilder);
    }
}
