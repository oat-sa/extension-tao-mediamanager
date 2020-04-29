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

namespace oat\taoMediaManager\model\sharedStimulus\service;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class CreateSharedStimulusByRequestService
{
    /** @var CreateSharedStimulusService */
    private $createSharedStimulusService;

    public function __construct(CreateSharedStimulusService $createSharedStimulusService)
    {
        $this->createSharedStimulusService = $createSharedStimulusService;
    }

    public function create(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $parsedBody = $request->getParsedBody();

        $sharedStimulus = $this->createSharedStimulusService->createEmpty(
            $parsedBody['language'] ?? 'http_2_www_0_tao_0_lu_1_Ontologies_1_TAO_0_rdf_3_Langen-US',
            $parsedBody['name'] ?? 'New Passage'
        );

        return $response->withStatus(204)
            ->withBody(stream_for(json_encode($sharedStimulus)));
    }
}
