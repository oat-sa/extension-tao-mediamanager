<?php

/**
 * SPDX-FileCopyrightText: 2014-2026 Open Assessment Technologies S.A.
 * Copyright (C) 2026 (original work) Open Assessment Technologies S.A.
 *
 * SPDX-License-Identifier: AGPL-3.0-only OR LicenseRef-TAO-Commercial-License
 */

declare(strict_types=1);

namespace oat\taoMediaManager\controller;

use oat\tao\model\http\ContentDetector;
use oat\tao\model\ClientLibConfigRegistry;
use oat\tao\model\featureFlag\WProofreaderClientConfigHandler;
use oat\tao\model\TaskOrchestrator\TaskOrchestratorEmailService;
use oat\oatbox\user\User;
use oat\oatbox\validator\ValidatorInterface;
use oat\taoMediaManager\model\editInstanceForm;
use oat\taoMediaManager\model\MediaService;
use oat\taoMediaManager\model\MediaSource;
use oat\taoMediaManager\model\accessControl\MediaPermissionService;
use oat\taoMediaManager\model\fileManagement\FileManagement;
use oat\tao\model\Lists\Business\Validation\DependsOnPropertyValidator;
use oat\taoMediaManager\model\sharedStimulus\service\PreviewAvailabilityService;
use core_kernel_classes_Resource;
use oat\tao\model\TaoOntology;
use oat\taoMediaManager\model\TaoMediaOntology;
use oat\taoMediaManager\model\transcription\TranscriptionMimeTypesProvider;
use tao_actions_form_Instance;
use tao_actions_SaSModule;
use tao_helpers_form_FormContainer as FormContainer;
use tao_helpers_Uri;
use tao_models_classes_FileNotFoundException;
use tao_models_classes_dataBinding_GenerisFormDataBinder;

class MediaManager extends tao_actions_SaSModule
{
    /**
     * Show the form to edit an instance, show also a preview of the media
     *
     * @requiresRight id READ
     */
    public function editInstance()
    {
        $this->defaultData();

        $user = $this->getSession()->getUser();
        $permissionService = $this->getPermissionService();

        $resource = $this->getCurrentInstance();
        $editFormContainer = $this->getFormInstance($resource, $user);
        $editForm = $editFormContainer->getForm();
        $isPreviewEnabled = $permissionService->isAllowedToPreview();

        if (
            $permissionService->isAllowedToEditResource($resource, $user)
            && $editForm->isSubmited()
            && $editForm->isValid()
        ) {
            $binder = new tao_models_classes_dataBinding_GenerisFormDataBinder($resource);
            $binder->bind($editForm->getValues());

            $this->setData('message', __('Instance saved'));
            $this->setData('reload', true);
        }

        $this->setData('formTitle', __('Edit Instance'));
        $this->setData('myForm', $editForm->render());

        $uri = $this->getRequestedMediaUri();
        $url = tao_helpers_Uri::url(
            'getFile',
            'MediaManager',
            'taoMediaManager',
            [
                'uri' => $uri,
            ]
        );

        $this->setData('fileurl', $url);

        try {
            $fileInfo = (new MediaSource())->getFileInfo($uri);
            $mimeType = $fileInfo['mime'];
        } catch (tao_models_classes_FileNotFoundException $e) {
            $this->setData('error', __('No file found for this media'));
        }

        $hasPreviewContent = isset($mimeType)
            && (
                $mimeType !== MediaService::SHARED_STIMULUS_MIME_TYPE
                || $this->getPreviewAvailabilityService()->hasPreviewContent($uri)
            );

        $this->setData('isPreviewEnabled', $isPreviewEnabled);
        $this->setData('displayPreview', $isPreviewEnabled && $hasPreviewContent);
        $this->setData('xml', isset($mimeType) ? $this->getClassService()->isXmlAllowedMimeType($mimeType) : null);
        $this->setData('mimeType', $mimeType ?? null);
        $this->setData('assetUri', $uri);
        $this->setData(
            'itemCommentsMentionsEnabled',
            $this->getPsrContainer()->get(TaskOrchestratorEmailService::class)->isConfigured()
        );
        $this->setView('form.tpl');
    }

    public function renameAsset(): void
    {
        try {
            // jQuery $.ajax sends this header by default; a cross-site form POST cannot set it.
            if (!$this->getPsrRequest()->hasHeader('X-Requested-With')) {
                $this->returnJsonWithStatus([
                    'success' => false,
                    'message' => __('Unable to process your request.'),
                ], 400);

                return;
            }

            $uri = $this->getRequestParameter('uri');
            $uri = is_string($uri) ? trim($uri) : '';

            if ($uri === '') {
                $this->returnJsonWithStatus([
                    'success' => false,
                    'message' => __('The media asset is missing.'),
                ], 400);

                return;
            }

            $label = $this->getRequestParameter('label');
            $name = is_string($label) ? trim($label) : '';

            if ($name === '') {
                $this->returnJsonWithStatus([
                    'success' => false,
                    'message' => __('The name cannot be empty.'),
                ], 400);

                return;
            }

            if (mb_strlen($name) > 255) {
                $this->returnJsonWithStatus([
                    'success' => false,
                    'message' => __('The name cannot be longer than 255 characters.'),
                ], 400);

                return;
            }

            if (strpbrk($name, '/\\') !== false) {
                $this->returnJsonWithStatus([
                    'success' => false,
                    'message' => __('The name cannot contain a slash: the folder is not part of the name.'),
                ], 400);

                return;
            }

            $resource = new core_kernel_classes_Resource(tao_helpers_Uri::decode($uri));

            if (!$resource->exists()) {
                $this->returnJsonWithStatus([
                    'success' => false,
                    'message' => __('The stylesheet could not be found.'),
                ], 404);

                return;
            }

            $isMediaAsset = false;
            foreach ($resource->getTypes() as $type) {
                if ($type->getUri() === TaoMediaOntology::CLASS_URI_MEDIA_ROOT) {
                    $isMediaAsset = true;
                    break;
                }

                foreach ($type->getParentClasses(true) as $parentClass) {
                    if ($parentClass->getUri() === TaoMediaOntology::CLASS_URI_MEDIA_ROOT) {
                        $isMediaAsset = true;
                        break 2;
                    }
                }
            }

            if (!$isMediaAsset) {
                $this->returnJsonWithStatus([
                    'success' => false,
                    'message' => __('The stylesheet could not be found.'),
                ], 404);

                return;
            }

            if (!$this->getPermissionService()->isAllowedToEditResource($resource, $this->getSession()->getUser())) {
                $this->returnJsonWithStatus([
                    'success' => false,
                    'message' => __('You are not allowed to edit this resource.'),
                ], 403);

                return;
            }

            $resource->setLabel($name);

            $this->returnJson([
                'success' => true,
                'label' => $resource->getLabel(),
            ]);
        } catch (\Throwable $e) {
            \common_Logger::e(
                $e->getMessage(),
                [
                    \common_Logger::CONTEXT_ERROR_FILE => $e->getFile(),
                    \common_Logger::CONTEXT_ERROR_LINE => $e->getLine(),
                ]
            );

            $this->returnJsonWithStatus([
                'success' => false,
                'message' => __('The name could not be changed, please try again.'),
            ], 500);
        }
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
     * overwrite the parent moveAllInstances to add the requiresRight only in Items
     * @see tao_actions_TaoModule::moveResource()
     */
    public function moveResource()
    {
        return parent::moveResource();
    }

    /**
     * @requiresRight id READ
     */
    public function editClassLabel()
    {
        parent::editClassLabel();
    }

    /**
     * Loads the shared stimulus authoring shell; WProofreader config is merged for the authoring controller.
     *
     * @requiresRight id WRITE
     */
    public function authoring(): void
    {
        $this->defaultData();

        $assetId = $this->getRequestParameter('id');
        $language = null;

        if (is_string($assetId) && $assetId !== '') {
            $resource = new core_kernel_classes_Resource(tao_helpers_Uri::decode($assetId));
            $languageProperty = $resource->getOnePropertyValue($this->getProperty(TaoMediaOntology::PROPERTY_LANGUAGE));
            if ($languageProperty instanceof core_kernel_classes_Resource) {
                $language = \tao_models_classes_LanguageService::singleton()->getCode($languageProperty);
            }
        }

        $clientConfigs = ($this->getPsrContainer()->get(WProofreaderClientConfigHandler::class))(
            $this->getPsrContainer()->get(ClientLibConfigRegistry::class)->getMap()
        );

        $requireConfig = [];
        if (isset($clientConfigs['tao/ckeditor/wproofreaderBootstrap']['spellCheckConfig'])) {
            $requireConfig['taoMediaManager/controller/authoring'] = [
                'properties' => [
                    'spellCheckConfig' => $clientConfigs['tao/ckeditor/wproofreaderBootstrap']['spellCheckConfig'],
                    'lang' => $language !== null && $language !== ''
                        ? $language
                        : 'en-US',
                ],
            ];
        }

        $this->setData('wproofreaderRequireConfig', $requireConfig);
        $this->setView('authoring.tpl', 'taoMediaManager');
    }

    protected function getClassService()
    {
        return $this->getMediaService();
    }

    private function getRequestedMediaUri(): string
    {
        if ($this->hasRequestParameter('id')) {
            return $this->getRequest()->getParameter('id');
        }

        return $this->getRequest()->getParameter('uri');
    }

    private function returnJsonWithStatus(array $data, int $status): void
    {
        $this->returnJson($data);

        $this->response = $this->response->withStatus($status);
    }

    private function getFormInstance(
        core_kernel_classes_Resource $instance,
        User $user
    ): editInstanceForm {
        $permissionService = $this->getPermissionService();
        $editAllowed = $permissionService->isAllowedToEditResource($instance, $user);
        $canReplaceMedia = $editAllowed && $permissionService->isAllowedToEditMedia();

        return new editInstanceForm(
            $this->getCurrentClass(),
            $instance,
            [
                FormContainer::CSRF_PROTECTION_OPTION => true,
                FormContainer::IS_DISABLED => !$editAllowed,
                editInstanceForm::IS_REPLACE_ASSET_DISABLED => !$canReplaceMedia,
                FormContainer::ATTRIBUTE_VALIDATORS => [
                    'data-depends-on-property' => [
                        $this->getDependsOnPropertyValidator(),
                    ],
                ],
                tao_actions_form_Instance::RESTRICTED_PROPERTIES => [
                    TaoMediaOntology::PROPERTY_TRANSCRIPTION => [
                        TaoMediaOntology::PROPERTY_MIME_TYPE =>
                            $this->getPsrContainer()->get(TranscriptionMimeTypesProvider::class)->getAll(),
                    ]
                ]
            ]
        );
    }

    private function getMediaService(): MediaService
    {
        return $this->getPsrContainer()->get(MediaService::class);
    }

    private function getPermissionService(): MediaPermissionService
    {
        return $this->getPsrContainer()->get(MediaPermissionService::class);
    }

    private function getDependsOnPropertyValidator(): ValidatorInterface
    {
        return $this->getPsrContainer()->get(DependsOnPropertyValidator::class);
    }

    private function getPreviewAvailabilityService(): PreviewAvailabilityService
    {
        return $this->getPsrContainer()->get(PreviewAvailabilityService::class);
    }
}
