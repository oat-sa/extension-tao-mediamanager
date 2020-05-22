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
 */

declare(strict_types=1);

namespace oat\taoMediaManager\model\sharedStimulus\factory;

use League\Flysystem\FilesystemInterface;
use oat\generis\model\fileReference\FileReferenceSerializer;
use oat\oatbox\filesystem\Directory;
use oat\oatbox\filesystem\FileSystemService;
use oat\oatbox\service\ConfigurableService;
use oat\oatbox\user\User;
use oat\taoMediaManager\model\MediaService;
use oat\taoMediaManager\model\sharedStimulus\CreateCommand;
use oat\taoMediaManager\model\sharedStimulus\PatchCommand;
use Psr\Http\Message\ServerRequestInterface;

class CommandFactory extends ConfigurableService
{
    public const DEFAULT_DIRECTORY = 'sharedStimulusUploads';

    /** @var FilesystemInterface */
    private $directory;

    public function makeCreateCommandByRequest(ServerRequestInterface $request): CreateCommand
    {
        $parsedBody = json_decode((string)$request->getBody(), true);

        return new CreateCommand(
            $parsedBody['classId'] ?? $parsedBody['classUri'] ?? MediaService::ROOT_CLASS_URI,
            $parsedBody['name'] ?? null,
            $parsedBody['languageId'] ?? $parsedBody['languageUri'] ?? null
        );
    }

    public function makePatchCommand(string $id, string $body, User $user): PatchCommand
    {
        $name = hash('md5', $id);
        $file = $this->getDirectory()->getFile($name);
        $file->write($body);

        return new PatchCommand(
            $id,
            $this->getSerializer()->serialize($file),
            $user->getIdentifier()
        );
    }

    private function getDirectory(): Directory
    {
        if (is_null($this->directory)) {
            /** @var FileSystemService $fileSystemService */
            $fileSystemService = $this->getServiceLocator()->get(FileSystemService::SERVICE_ID);
            $this->directory = $fileSystemService->getDirectory(self::DEFAULT_DIRECTORY);
        }
        return $this->directory;
    }

    private function getSerializer(): FileReferenceSerializer
    {
        return $this->getServiceLocator()->get(FileReferenceSerializer::SERVICE_ID);
    }
}
