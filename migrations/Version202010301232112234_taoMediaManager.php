<?php

declare(strict_types=1);

namespace oat\taoMediaManager\migrations;

use Doctrine\DBAL\Schema\Schema;
use oat\tao\model\resources\relation\service\ResourceRelationServiceProxy;
use oat\tao\scripts\tools\migrations\AbstractMigration;
use oat\taoMediaManager\model\relation\MediaRelationService;

final class Version202010301232112234_taoMediaManager extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Register item relation status';
    }

    public function up(Schema $schema): void
    {
        /** @var ResourceRelationServiceProxy $resourceRelationService */
        $resourceRelationService = $this->getServiceManager()->get(ResourceRelationServiceProxy::SERVICE_ID);
        $resourceRelationService->addService('item', MediaRelationService::class);

        $this->getServiceManager()->register(ResourceRelationServiceProxy::SERVICE_ID, $resourceRelationService);
    }

    public function down(Schema $schema): void
    {
    }
}
