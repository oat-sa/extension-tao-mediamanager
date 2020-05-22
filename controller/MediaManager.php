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
 * Copyright (c) 2014-2020 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoMediaManager\controller;

use oat\taoMediaManager\model\editInstanceForm;
use oat\taoMediaManager\model\MediaService;
use oat\taoMediaManager\model\MediaSource;
use oat\taoMediaManager\model\fileManagement\FileManagement;
use tao_helpers_form_FormContainer as FormContainer;

class MediaManager extends \tao_actions_SaSModule
{
    /**
     * Show the form to edit an instance, show also a preview of the media
     */
    public function editInstance()
    {
        $this->defaultData();

        $clazz = $this->getCurrentClass();
        $instance = $this->getCurrentInstance();
        $myFormContainer = new editInstanceForm($clazz, $instance, [FormContainer::CSRF_PROTECTION_OPTION => true]);

        $myForm = $myFormContainer->getForm();
        if ($myForm->isSubmited() && $myForm->isValid()) {
            $values = $myForm->getValues();
            // save properties
            $binder = new \tao_models_classes_dataBinding_GenerisFormDataBinder($instance);
            $instance = $binder->bind($values);
            $message = __('Instance saved');

            $this->setData('message', $message);
            $this->setData('reload', true);
        }

        $this->setData('formTitle', __('Edit Instance'));
        $this->setData('myForm', $myForm->render());
        $uri = ($this->hasRequestParameter('id')) ? $this->getRequestParameter('id') : $this->getRequestParameter('uri');

        try {
            $mediaSource = new MediaSource([]);
            $fileInfo = $mediaSource->getFileInfo($uri);

            $mimeType = $fileInfo['mime'];
            $xml = in_array($mimeType, ['application/xml', 'text/xml']);
            $url = \tao_helpers_Uri::url(
                'getFile',
                'MediaManager',
                'taoMediaManager',
                [
                    'uri' => $uri,
                ]
            );
            $this->setData('xml', $xml);
            $this->setData('fileurl', $url);
            $this->setData('mimeType', $mimeType);
        } catch (\tao_models_classes_FileNotFoundException $e) {
            $this->setData('error', __('No file found for this media'));
        }
        $this->setView('form.tpl');
    }

    /**
     * Get the file stream associated to given uri GET parameter
     *
     * @throws \common_exception_Error
     * @throws \tao_models_classes_FileNotFoundException
     */
    public function getFile()
    {
        if (!$this->hasGetParameter('uri')) {
            throw new \common_exception_Error('invalid media identifier');
        }
        $uri = urldecode($this->getGetParameter('uri'));

        $mediaSource = new MediaSource([]);
        $fileInfo = $mediaSource->getFileInfo($uri);

        $fileManagement = $this->getServiceLocator()->get(FileManagement::SERVICE_ID);
        $stream = $fileManagement->getFileStream($fileInfo['link']);
        if ($fileInfo['mime'] === 'application/qti+xml') {
            $this->response = $this->getPsrResponse()->withBody($stream);
        } elseif ($this->hasGetParameter('xml')) {
            $this->returnJson(htmlentities((string)$stream));
        } else {
            $this->setContentHeader($fileInfo['mime']);
            $this->response = $this->getPsrResponse()->withBody($stream);
        }
    }

    protected function getClassService()
    {
        return $this->getServiceLocator()->get(MediaService::class);
    }
}
