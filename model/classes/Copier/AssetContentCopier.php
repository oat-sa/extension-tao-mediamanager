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
 * Copyright (c) 2022 (original work) Open Assessment Technologies SA.
 */

declare(strict_types=1);

namespace oat\taoMediaManager\model\classes\Copier;

use common_Exception;
use core_kernel_classes_Resource;
use oat\tao\model\resources\Contract\InstanceContentCopierInterface;
use oat\taoMediaManager\model\sharedStimulus\factory\CommandFactory;
use oat\taoMediaManager\model\sharedStimulus\service\CopyService;
use oat\taoMediaManager\model\sharedStimulus\specification\SharedStimulusResourceSpecification;
use oat\taoMediaManager\model\TaoMediaOntology;

class AssetContentCopier implements InstanceContentCopierInterface
{
    /** @var SharedStimulusResourceSpecification */
    private $sharedStimulusSpecification;

    /** @var CommandFactory */
    private $commandFactory;

    /** @var CopyService */
    private $sharedStimulusCopyService;

    /** @var string */
    private $defaultLanguage;

    public function __construct(
        SharedStimulusResourceSpecification $sharedStimulusResourceSpecification,
        CommandFactory $commandFactory,
        CopyService $copyService,
        string $defaultLanguage
    ) {
        $this->sharedStimulusSpecification = $sharedStimulusResourceSpecification;
        $this->commandFactory = $commandFactory;
        $this->sharedStimulusCopyService = $copyService;
        $this->defaultLanguage = $defaultLanguage;
    }

    /**
     * @throws common_Exception
     */
    public function copy(
        core_kernel_classes_Resource $instance,
        core_kernel_classes_Resource $destinationInstance
    ): void {
        if ($this->sharedStimulusSpecification->isSatisfiedBy($instance)) {
            $this->sharedStimulusCopyService->copy(
                $this->commandFactory->makeCopyCommand(
                    $instance->getUri(),
                    $destinationInstance->getUri(),
                    $this->getResourceLanguageCode($instance)
                )
            );
        }
    }

    private function getResourceLanguageCode(
        core_kernel_classes_Resource $instance
    ): string {
        $lang = $instance->getPropertyValues(
            new \core_kernel_classes_Property(
                TaoMediaOntology::PROPERTY_LANGUAGE
            )
        );

        if (empty($lang)) {
            return $this->defaultLanguage;
        }

        return current($lang);
    }
}
