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
 *
 */

namespace oat\taoMediaManager\controller;

use oat\oatbox\log\LoggerAwareTrait;
use oat\tao\model\upload\UploadService;
use oat\taoMediaManager\model\MediaService;
use oat\taoMediaManager\model\sharedStimulus\CreateSharedStimulusService;
use oat\taoMediaManager\model\sharedStimulus\SharedStimulus as SharedStimulusVo;
use oat\taoMediaManager\model\SharedStimulusImporter;

class SharedStimulus extends \tao_actions_SaSModule
{
    use LoggerAwareTrait;

    public function create(): void
    {
        try {
            $templatePath = __DIR__
                . DIRECTORY_SEPARATOR
                . '..'
                . DIRECTORY_SEPARATOR
                . 'assets'
                . DIRECTORY_SEPARATOR
                . 'sharedStimulus'
                . DIRECTORY_SEPARATOR
                . 'empty_template.xml';

            $createService = new CreateSharedStimulusService(
                $this->getUploadService(),
                $this->getSharedStimulusImporter(),
                $this->getCurrentClass(),
                $templatePath,
                sys_get_temp_dir()
            );

            $sharedStimulus = $createService->createEmpty(
                'http_2_www_0_tao_0_lu_1_Ontologies_1_TAO_0_rdf_3_Langen-US',
                'Passage_NEW'
            );

            $this->setData('redirectUrl', $this->getRedirectUrl($sharedStimulus));
            $this->setData('message', __('Instance saved'));
        } catch (\Throwable $e) {
            $this->logError(sprintf('Error creating shared stimulus: %s', $e->getMessage()));
            $this->setData('error', __('Error creating Shared Stimulus'));
        }

        $this->setView('sharedStimulus/created.tpl');
    }

    private function getRedirectUrl(SharedStimulusVo $sharedStimulus): string
    {
        return 'index?structure=taoMediaManager&ext=taoMediaManager&section=media_manager&uri='
            . urlencode($sharedStimulus->getUri());
    }

    private function getUploadService(): UploadService
    {
        return $this->getServiceLocator()
            ->get(UploadService::SERVICE_ID);
    }

    private function getSharedStimulusImporter(): SharedStimulusImporter
    {
        $importer = new SharedStimulusImporter();
        $importer->setServiceLocator($this->getServiceLocator());

        return $importer;
    }

    protected function getClassService()
    {
        return MediaService::singleton();
    }
}
