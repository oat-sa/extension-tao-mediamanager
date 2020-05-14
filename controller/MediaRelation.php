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

declare(strict_types=1);

namespace oat\taoMediaManager\controller;

use oat\tao\model\http\Controller;
use oat\taoMediaManager\model\relation\MediaRelationService;
use Psr\Http\Message\ResponseInterface;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use function GuzzleHttp\Psr7\stream_for;

class MediaRelation extends Controller implements ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;

    const HTTP_PARAMS_MEDIA_ID = 'id';

    public function relation() : ResponseInterface
    {
        $this->response = $this->getPsrResponse()->withHeader('content-type', 'application/json');

        $id = (string) $this->getPsrRequest()->getQueryParams()[self::HTTP_PARAMS_MEDIA_ID] ?? false;

        if ($id === false) {
            return $this->getPsrResponse()->withStatus(400);
        }

        $data = [
            'source' => [
                'id' => $id
            ],
            'relatedAssets' => $this->getMediaRelationService()->getMediaRelation($id)
        ];

        return $this->getPsrResponse()
            ->withStatus(200)
            ->withBody(stream_for(json_encode($data)));
    }

    protected function getMediaRelationService() : MediaRelationService
    {
        return $this->getServiceLocator()->get(MediaRelationService::class);
    }
}