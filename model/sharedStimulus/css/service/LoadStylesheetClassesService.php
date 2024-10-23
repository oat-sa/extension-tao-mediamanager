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

namespace oat\taoMediaManager\model\sharedStimulus\css\service;

use oat\oatbox\filesystem\FilesystemException;
use oat\oatbox\service\ConfigurableService;
use oat\taoMediaManager\model\sharedStimulus\css\dto\LoadStylesheet;
use oat\taoMediaManager\model\sharedStimulus\css\repository\StylesheetRepository;

class LoadStylesheetClassesService extends ConfigurableService
{
    public function load(LoadStylesheet $loadStylesheetDTO): array
    {
        $stylesheetRepository = $this->getStylesheetRepository();

        $path = $stylesheetRepository->getPath($loadStylesheetDTO->getUri());

        if ($path === '.') {
            throw new \Exception('Shared stimulus stored as single file');
        }

        try {
            $content = $stylesheetRepository->read(
                $path . DIRECTORY_SEPARATOR . $loadStylesheetDTO->getStylesheetUri()
            );

            return $this->cssToArray($content);
        } catch (FilesystemException $e) {
            $this->logDebug(
                sprintf(
                    'Passage %s does not contain stylesheet %s. An empty array will be returned.',
                    $loadStylesheetDTO->getUri(),
                    $loadStylesheetDTO->getStylesheetUri()
                )
            );
        }

        return [];
    }

    private function cssToArray(string $css): array
    {
        $oldCssArr = explode("\n", $css);
        array_shift($oldCssArr);

        $newCssArr = [];
        foreach ($oldCssArr as $line) {
            if (false === strpos($line, '{')) {
                continue;
            }

            preg_match('~(?P<selector>[^{]+)(\{)(?P<rules>[^}]+)\}~', $line, $matches);

            foreach ($matches as $key => &$match) {
                if (is_numeric($key)) {
                    continue;
                }
                $match = trim($match);
                if ($key === 'rules') {
                    $ruleSet = array_filter(array_map('trim', explode(';', $match)));
                    $match = [];
                    foreach ($ruleSet as $rule) {
                        $rule = array_map('trim', explode(':', $rule));
                        $match[$rule[0]] = $rule[1];
                    }
                }
            }

            $newCssArr[$matches['selector']] = $matches['rules'];
        }
        return $newCssArr;
    }

    private function getStylesheetRepository(): StylesheetRepository
    {
        return $this->getServiceLocator()->get(StylesheetRepository::class);
    }
}
