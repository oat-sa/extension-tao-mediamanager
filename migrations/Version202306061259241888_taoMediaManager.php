<?php

declare(strict_types=1);

namespace oat\taoMediaManager\migrations;

use Doctrine\DBAL\Schema\Schema;
use oat\tao\scripts\tools\accessControl\SetRolesAccess;
use oat\tao\scripts\tools\migrations\AbstractMigration;
use oat\tao\scripts\update\OntologyUpdater;
use oat\taoMediaManager\scripts\install\AddAssetClassEditorRolePermission;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version202306061259241888_taoMediaManager extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add new role for media manager that will allow AssetClassEditorRole permission to modify class name';
    }

    public function up(Schema $schema): void
    {
        OntologyUpdater::syncModels();
        $setRolesAccess = $this->propagate(new SetRolesAccess());
        $setRolesAccess([
            '--' . SetRolesAccess::OPTION_CONFIG, AddAssetClassEditorRolePermission::CONFIG,
        ]);
    }

    public function down(Schema $schema): void
    {
        $setRolesAccess = $this->propagate(new SetRolesAccess());
        $setRolesAccess([
            '--' . SetRolesAccess::OPTION_REVOKE,
            '--' . SetRolesAccess::OPTION_CONFIG, AddAssetClassEditorRolePermission::CONFIG,
        ]);
    }
}
