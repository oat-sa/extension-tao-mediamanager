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
 * Copyright (c) 2023 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoMediaManager\scripts;

use Laminas\ServiceManager\ServiceLocatorAwareTrait;
use oat\oatbox\extension\script\ScriptAction;
use oat\oatbox\reporting\Report;
use oat\tao\model\resources\Service\ResourceTransferProxy;
use oat\taoMediaManager\model\MediaService;
use oat\tao\model\resources\Command\ResourceTransferCommand;
use oat\tao\model\resources\Contract\ResourceTransferInterface;

/**
 * sudo -u www-data php index.php 'oat\taoMediaManager\scripts\MoveResources' [--id][--namePattern][--destinationClass]
 */
class MoveResources extends ScriptAction
{
    use ServiceLocatorAwareTrait;

    private const OPTION_RESOURCE_ID = 'id';
    private const OPTION_RESOURCE_NAME_PATTERN = 'namePattern';
    private const OPTION_DESTINATION_CLASS_URI = 'destinationClassUri';

    private Report $report;

    protected function provideOptions(): array
    {
        return [
            self::OPTION_RESOURCE_ID => [
                'prefix' => 'id',
                'longPrefix' => self::OPTION_RESOURCE_ID,
                'flag' => false,
                'required' => false,
                'description' => __('Set the resource id to move.'),
            ],
            self::OPTION_RESOURCE_NAME_PATTERN => [
                'prefix' => 'np',
                'longPrefix' => self::OPTION_RESOURCE_NAME_PATTERN,
                'flag' => false,
                'required' => false,
                'description' => __('Set the name pattern whom script will use to select resource to move.'),
            ],
            self::OPTION_DESTINATION_CLASS_URI => [
                'prefix' => 'dcu',
                'longPrefix' => self::OPTION_DESTINATION_CLASS_URI,
                'flag' => false,
                'required' => true,
                'description' => __('Set the identifier of class where assets will be moved.'),
            ],
        ];
    }

    protected function provideDescription(): string
    {
        return __('Move item resource to different class, can be filtered by name pattern.');
    }

    /**
     * @throws \common_exception_Error
     */
    protected function run(): Report
    {
        $this->report = Report::createInfo(__('Move resource script start.'));
        $preflightCheck = $this->checkRequiredOptions();
        if ($preflightCheck === false) {
            return $this->report;
        }
        $destinationClassId = $this->getOption(self::OPTION_DESTINATION_CLASS_URI);

        $mediaInstances = $this->getMediaService()->getRootClass()->getSubClasses();
        $this->report->add(Report::createInfo(__('Destination class: %s', $destinationClassId)));

        $pattern = $this->getOption(self::OPTION_RESOURCE_NAME_PATTERN);
        $id = $this->getOption(self::OPTION_RESOURCE_ID);

        $destinationClassFound = false;
        foreach ($mediaInstances as $mediaInstance) {
            if ($mediaInstance->getUri() === $destinationClassId) {
                $destinationClassFound = true;
                break;
            }
        }
        if ($destinationClassFound === false) {
            $this->report->add(Report::createError(__('Destination class uri "%s" not found!', $destinationClassId)));
            return $this->report;
        }
        $count = 0;
        foreach ($mediaInstances as $mediaInstance) {
            if ($pattern != null && str_contains($mediaInstance->getLabel(), $pattern) === false) {
                continue;
            }
            if ($id !== null && $mediaInstance->getUri() !== $id) {
                continue;
            }
            $this->moveResource($mediaInstance);
            $count++;
        }
        $this->report->add(
            Report::createInfo(
                __('Moved %d resource/s.', $count)
            )
        );

        return $this->report;
    }

    /**
     * Options flagged as non required, but we need one of them
     * @return bool
     * @throws \common_exception_Error
     */
    private function checkRequiredOptions(): bool
    {
        $id = $this->getOption(self::OPTION_RESOURCE_ID);
        $pattern = $this->getOption(self::OPTION_RESOURCE_NAME_PATTERN);
        if (empty($id) && empty($pattern)) {
            $this->report->add(
                Report::createError(
                    __(
                        'One of options, %s or %s, is required.',
                        self::OPTION_RESOURCE_ID,
                        self::OPTION_RESOURCE_NAME_PATTERN
                    )
                )
            );
            return false;
        }

        return true;
    }


    /**
     * @param \core_kernel_classes_Resource $mediaInstance
     * @return void
     * @throws \common_exception_Error
     */
    private function moveResource(\core_kernel_classes_Resource $mediaInstance): void
    {
        $result = $this->getResourceTransfer()->transfer(
            new ResourceTransferCommand(
                $mediaInstance->getUri(),
                $this->getOption(self::OPTION_DESTINATION_CLASS_URI),
                ResourceTransferCommand::ACL_KEEP_ORIGINAL,
                ResourceTransferCommand::TRANSFER_MODE_MOVE
            )
        );
        $this->report->add(Report::createSuccess(__('Moved resource, id %s', $result->getDestination())));
    }


    private function getMediaService(): MediaService
    {
        return $this->getServiceManager()->getContainer()->get(MediaService::class);
    }

    private function getResourceTransfer(): ResourceTransferInterface
    {
        return $this->getServiceManager()->getContainer()->get(ResourceTransferProxy::class);
    }
}
