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
 * Copyright (c) 2014-2021 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoMediaManager\controller;

use oat\tao\model\http\ContentDetector;
use oat\taoMediaManager\model\editInstanceForm;
use oat\taoMediaManager\model\MediaService;
use oat\taoMediaManager\model\MediaSource;
use oat\taoMediaManager\model\fileManagement\FileManagement;
use tao_helpers_form_FormContainer as FormContainer;
use tao_models_classes_FileNotFoundException;

class MediaManager extends \tao_actions_SaSModule
{
    /**
     * Show the form to edit an instance, show also a preview of the media
     *
     * @requiresRight id READ
     */
    public function editInstance()
    {
        $this->defaultData();

        $clazz = $this->getCurrentClass();
        $instance = $this->getCurrentInstance();
        $hasWriteAccess = $this->hasWriteAccess($instance->getUri());
        $myFormContainer = new editInstanceForm(
            $clazz,
            $instance,
            [
                FormContainer::CSRF_PROTECTION_OPTION => true,
                FormContainer::IS_DISABLED => !$hasWriteAccess,
            ]
        );

        $myForm = $myFormContainer->getForm();

        if ($hasWriteAccess && $myForm->isSubmited() && $myForm->isValid()) {
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

        try {
            $uri = $this->hasRequestParameter('id')
                ? $this->getRequestParameter('id')
                : $this->getRequestParameter('uri');

            $mediaSource = new MediaSource([]);
            $fileInfo = $mediaSource->getFileInfo($uri);

            $mimeType = $fileInfo['mime'];
            $xml = in_array(
                $mimeType,
                [
                    'application/xml',
                    'text/xml',
                    MediaService::SHARED_STIMULUS_MIME_TYPE
                ],
                true
            );
            $url = \tao_helpers_Uri::url(
                'getFile',
                'MediaManager',
                'taoMediaManager',
                [
                    'uri' => $uri,
                ]
            );
        } catch (tao_models_classes_FileNotFoundException $e) {
            $this->setData('error', __('No file found for this media'));
        }

        $this->setData('xml', $xml);
        $this->setData('fileurl', $url);
        $this->setData('mimeType', $mimeType);
        $this->setView('form.tpl');
    }

    /**
     * Get the file stream associated to given uri GET parameter
     *
     * @throws \common_exception_Error
     * @throws tao_models_classes_FileNotFoundException
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
        if ($fileInfo['mime'] === MediaService::SHARED_STIMULUS_MIME_TYPE) {
            $this->response = $this->getPsrResponse()->withBody($stream);
        } elseif ($this->hasGetParameter('xml')) {
            $this->returnJson(htmlentities((string)$stream));
        } else {
            $this->setContentHeader($fileInfo['mime']);
            if ($this->getServiceLocator()->get(ContentDetector::class)->isGzip($stream)) {
                $this->response = $this->getPsrResponse()->withHeader('Content-Encoding', 'gzip');
            }
            $this->response = $this->getPsrResponse()->withBody($stream);
        }
    }

    /**
     * @inheritDoc
     *
     * @requiresRight id WRITE
     */
    public function delete()
    {
        return parent::delete();
    }

    /**
     * @requiresRight id READ
     */
    public function editClassLabel()
    {
        parent::editClassLabel();
    }

    /**
     * @requiresRight id WRITE
     */
    public function authoring()
    {
        //This method is required to hide button on FE based on ACL
    }

    protected function getClassService()
    {
        return $this->getServiceLocator()->get(MediaService::class);
    }
}
