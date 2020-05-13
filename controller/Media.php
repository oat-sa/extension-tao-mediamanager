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

namespace oat\taoMediaManager\controller;

use oat\oatbox\log\LoggerAwareTrait;
use oat\tao\model\http\formatter\ResponseFormatter;
use oat\tao\model\http\response\SuccessJsonResponse;
use tao_actions_CommonModule;

class Media extends tao_actions_CommonModule
{
    use LoggerAwareTrait;

    public function relations(): void
    {
        //@TODO Method will be implemented when depending task is finished
        $formatter = $this->getResponseFormatter()
            ->withJsonHeader();

        $formatter->withBody(new SuccessJsonResponse([]));

        $this->setResponse($formatter->format($this->getPsrResponse()));
    }

    private function getResponseFormatter(): ResponseFormatter
    {
        return $this->getServiceLocator()->get(ResponseFormatter::class);
    }
}
