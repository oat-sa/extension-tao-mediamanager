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

 use InvalidArgumentException;
use oat\oatbox\service\ConfigurableService;
use oat\taoMediaManager\model\sharedStimulus\FindQuery;
use oat\taoMediaManager\model\sharedStimulus\UpdateCommand;
use Psr\Http\Message\ServerRequestInterface;
use tao_models_classes_UserService;

class QueryFactory extends ConfigurableService
{
    public function makeFindQueryByRequest(ServerRequestInterface $request): FindQuery
    {
        return new FindQuery($request->getQueryParams()['id'] ?? '');
    }

    public function patchStimulusByRequest(ServerRequestInterface $request): UpdateCommand
    {
        $parsedBody = json_decode((string)$request->getBody(), true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new InvalidArgumentException('Incorrect request format');
        }

        $user = $this->getUserService()->getCurrentUser();

        return new UpdateCommand(
            $parsedBody['id'],
            $parsedBody['body'],
            $user->getUri()
        );
    }

    private function getUserService(): tao_models_classes_UserService
    {
        return $this->getServiceLocator()->get(tao_models_classes_UserService::SERVICE_ID);
    }
}
