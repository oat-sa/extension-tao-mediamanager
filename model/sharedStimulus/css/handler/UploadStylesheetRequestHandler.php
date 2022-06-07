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

namespace oat\taoMediaManager\model\sharedStimulus\css\handler;

use common_exception_Error;
use oat\oatbox\service\ConfigurableService;
use oat\tao\helpers\FileUploadException;
use oat\taoMediaManager\model\sharedStimulus\css\dto\UploadedStylesheet;
use Psr\Http\Message\ServerRequestInterface;
use oat\taoMediaManager\model\validation\RequestValidator;
use common_exception_MissingParameter as MissingParameterException;
use tao_helpers_Http;

class UploadStylesheetRequestHandler extends ConfigurableService
{
    private const VALID_CSS_MIMETYPES = ['text/css'];

    /**
     * @throws MissingParameterException
     * @throws common_exception_Error
     */
    public function __invoke(ServerRequestInterface $request): UploadedStylesheet
    {
        $params = $request->getQueryParams();
        $this->validate($params);

        $file = $this->getUploadedFileInfo();
        $this->validateUploadedFile($file);

        return new UploadedStylesheet($params['uri'], $file['name'], $file['tmp_name']);
    }

    /**
     * @throws MissingParameterException
     */
    private function validate(array $params): void
    {
        RequestValidator::validateRequiredParameters($params, ['uri']);
    }

    /**
     * @throws common_exception_Error
     */
    private function getUploadedFileInfo(): array
    {
        return tao_helpers_Http::getUploadedFile('content');
    }

    /**
     * @throws FileUploadException|common_exception_Error
     */
    private function validateUploadedFile(array $fileInfo): void
    {
        if (!in_array($fileInfo['type'], self::VALID_CSS_MIMETYPES)) {
            throw new FileUploadException(__('The file you tried to upload is not valid'));
        }

        RequestValidator::securityCheckPath($fileInfo['name']);
    }
}
