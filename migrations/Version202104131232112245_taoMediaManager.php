<?php

declare(strict_types=1);

namespace oat\taoMediaManager\migrations;

use Doctrine\DBAL\Schema\Schema;
use oat\tao\model\accessControl\func\AccessRule;
use oat\tao\model\accessControl\func\AclProxy;
use oat\tao\scripts\tools\migrations\AbstractMigration;

final class Version202104131232112245_taoMediaManager extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update ACL';
    }

    public function up(Schema $schema): void
    {
        AclProxy::applyRule($this->createRule());
    }

    public function down(Schema $schema): void
    {
        AclProxy::revokeRule($this->createRule());
    }

    private function createRule(): AccessRule
    {
        return new AccessRule(
            AccessRule::GRANT,
            'http://www.tao.lu/Ontologies/TAOItem.rdf#ItemAuthor',
            [
                'ext' => 'taoMediaManager',
                'mod' => 'SharedStimulus',
                'act' => 'create'
            ]
        );
    }
}
