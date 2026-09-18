<?php

/**
 * SPDX-FileCopyrightText: 2021-2026 Open Assessment Technologies S.A.
 * Copyright (C) 2026 (original work) Open Assessment Technologies S.A.
 *
 * SPDX-License-Identifier: AGPL-3.0-only OR LicenseRef-TAO-Commercial-License
 */

declare(strict_types=1);

namespace oat\taoMediaManager\model\classes\ServiceProvider;

use oat\generis\model\data\Ontology;
use oat\generis\model\DependencyInjection\ContainerServiceProviderInterface;
use oat\oatbox\log\LoggerService;
use oat\tao\model\accessControl\ActionAccessControl;
use oat\tao\model\accessControl\PermissionChecker;
use oat\tao\model\resources\Service\ClassCopierProxy;
use oat\tao\model\resources\Service\ClassDeleter;
use oat\tao\model\resources\Service\ClassMetadataCopier;
use oat\tao\model\resources\Service\ClassMetadataMapper;
use oat\tao\model\resources\Service\InstanceCopier;
use oat\tao\model\resources\Service\InstanceCopierProxy;
use oat\tao\model\resources\Service\InstanceMetadataCopier;
use oat\tao\model\resources\Service\RootClassesListService;
use oat\tao\model\resources\Service\ClassCopier as TaoClassCopier;
use oat\taoMediaManager\model\accessControl\MediaPermissionService;
use oat\taoMediaManager\model\classes\Copier\AssetClassCopier;
use oat\taoMediaManager\model\classes\Copier\AssetContentCopier;
use oat\taoMediaManager\model\classes\Copier\AssetMetadataCopier;
use oat\taoMediaManager\model\fileManagement\FileManagement;
use oat\taoMediaManager\model\fileManagement\FileSourceUnserializer;
use oat\taoMediaManager\model\MediaService;
use oat\taoMediaManager\model\AssetDeleter;
use oat\taoMediaManager\model\TextReaderReferencesExtractorAdapter;
use oat\taoMediaManager\model\TextReaderReferencesExtractorInterface;
use oat\taoMediaManager\model\TextReaderInteractionQtiUpdater;
use oat\taoMediaManager\model\relation\repository\MediaRelationRepositoryInterface;
use oat\taoMediaManager\model\relation\repository\rdf\RdfMediaRelationRepository;
use oat\taoMediaManager\model\sharedStimulus\css\repository\StylesheetRepository;
use oat\taoMediaManager\model\sharedStimulus\css\service\ListStylesheetsService;
use oat\taoMediaManager\model\sharedStimulus\css\service\LoadStylesheetService;
use oat\taoMediaManager\model\sharedStimulus\factory\CommandFactory;
use oat\taoMediaManager\model\sharedStimulus\parser\JsonQtiAttributeParser;
use oat\taoMediaManager\model\sharedStimulus\repository\SharedStimulusRepository;
use oat\taoMediaManager\model\sharedStimulus\service\CopyService;
use oat\taoMediaManager\model\sharedStimulus\service\PreviewerSharedStimulusHandler;
use oat\taoMediaManager\model\sharedStimulus\service\StoreService;
use oat\taoMediaManager\model\sharedStimulus\specification\SharedStimulusResourceSpecification;
use oat\taoMediaManager\model\TaoMediaOntology;
use oat\taoMediaManager\model\Specification\MediaClassSpecification;
use oat\taoMediaManager\model\transcription\TranscriptionMimeTypesProvider;
use oat\taoQtiItem\model\qti\event\UpdatedItemEventDispatcher;
use oat\taoQtiItem\model\qti\parser\TextReaderReferencesExtractor as QtiTextReaderReferencesExtractor;
use oat\taoQtiItem\model\qti\parser\ElementReferencesExtractor;
use oat\taoQtiItem\model\qti\Service as QtiService;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

/**
 * @codeCoverageIgnore
 */
class MediaServiceProvider implements ContainerServiceProviderInterface
{
    public function __invoke(ContainerConfigurator $configurator): void
    {
        $services = $configurator->services();

        $services
            ->set(CopyService::class, CopyService::class)
            ->public()
            ->args(
                [
                    service(Ontology::SERVICE_ID),
                    service(StoreService::class),
                    service(ListStylesheetsService::class),
                    service(StylesheetRepository::class),
                    service(FileSourceUnserializer::class),
                    service(FileManagement::SERVICE_ID),
                ]
            );

        $services
            ->set(PreviewerSharedStimulusHandler::class, PreviewerSharedStimulusHandler::class)
            ->args(
                [
                    service(SharedStimulusRepository::class),
                    service(JsonQtiAttributeParser::class),
                    service(SharedStimulusResourceSpecification::class),
                    service(ListStylesheetsService::class),
                    service(LoadStylesheetService::class),
                ]
            )
            ->tag('tao.qti_test_previewer.shared_stimulus_handler');

        $services
            ->set(MediaPermissionService::class, MediaPermissionService::class)
            ->public()
            ->args(
                [
                    service(ActionAccessControl::class),
                    service(PermissionChecker::class),
                ]
            );

        $services
            ->set(MediaClassSpecification::class, MediaClassSpecification::class)
            ->public();

        $services
            ->get(InstanceMetadataCopier::class)
            ->call(
                'addPropertyUriToBlacklist',
                [
                    TaoMediaOntology::PROPERTY_LINK
                ]
            );

        $services
            ->set(AssetMetadataCopier::class, AssetMetadataCopier::class)
            ->args(
                [
                    service(InstanceMetadataCopier::class),
                ]
            );

        $services
            ->set(AssetContentCopier::class, AssetContentCopier::class)
            ->args(
                [
                    service(SharedStimulusResourceSpecification::class),
                    service(CommandFactory::class),
                    service(CopyService::class),
                    service(MediaService::class),
                    service(FileManagement::SERVICE_ID),
                    DEFAULT_LANG,
                ]
            );

        $services
            ->set(InstanceCopier::class . '::ASSETS', InstanceCopier::class)
            ->args(
                [
                    service(AssetMetadataCopier::class),
                    service(Ontology::SERVICE_ID)
                ]
            )
            ->call(
                'withInstanceContentCopier',
                [
                    service(AssetContentCopier::class),
                ]
            )
            ->call(
                'withPermissionCopiers',
                [
                    tagged_iterator('tao.copier.permissions'),
                ]
            );

        $services
            ->set(TaoClassCopier::class . '::ASSETS', TaoClassCopier::class)
            ->share(false)
            ->args(
                [
                    service(RootClassesListService::class),
                    service(ClassMetadataCopier::class),
                    service(InstanceCopier::class . '::ASSETS'),
                    service(ClassMetadataMapper::class),
                    service(Ontology::SERVICE_ID),
                ]
            )
            ->call(
                'withPermissionCopiers',
                [
                    tagged_iterator('tao.copier.permissions'),
                ]
            );

        $services
            ->set(AssetClassCopier::class, AssetClassCopier::class)
            ->share(false)
            ->args(
                [
                    service(MediaClassSpecification::class),
                    service(TaoClassCopier::class . '::ASSETS'),
                    service(Ontology::SERVICE_ID),
                ]
            );

        $services
            ->get(ClassCopierProxy::class)
            ->call(
                'addClassCopier',
                [
                    TaoMediaOntology::CLASS_URI_MEDIA_ROOT,
                    service(AssetClassCopier::class),
                ]
            );

        $services
            ->get(InstanceCopierProxy::class)
            ->call(
                'addInstanceCopier',
                [
                    TaoMediaOntology::CLASS_URI_MEDIA_ROOT,
                    service(InstanceCopier::class . '::ASSETS'),
                ]
            );

        $services
            ->set(AssetDeleter::class, AssetDeleter::class)
            ->public()
            ->args(
                [
                    service(LoggerService::SERVICE_ID),
                    service(MediaService::class),
                    service(Ontology::SERVICE_ID),
                    service(ClassDeleter::class),
                    service(RdfMediaRelationRepository::class),
                ]
            );

        $services
            ->set(QtiTextReaderReferencesExtractor::class, QtiTextReaderReferencesExtractor::class);

        $services
            ->set(TextReaderReferencesExtractorAdapter::class, TextReaderReferencesExtractorAdapter::class)
            ->args(
                [
                    service(QtiTextReaderReferencesExtractor::class),
                ]
            );

        $services
            ->set(TextReaderReferencesExtractorInterface::class, TextReaderReferencesExtractorAdapter::class)
            ->args(
                [
                    service(QtiTextReaderReferencesExtractor::class),
                ]
            );

        $services->set(ElementReferencesExtractor::class, ElementReferencesExtractor::class)
            ->args(
                [
                    service(QtiTextReaderReferencesExtractor::class),
                ]
            );

        $services
            ->set(TextReaderInteractionQtiUpdater::class, TextReaderInteractionQtiUpdater::class)
            ->public()
            ->args(
                [
                    service(MediaRelationRepositoryInterface::SERVICE_ID),
                    service(QtiService::class),
                    service(UpdatedItemEventDispatcher::class),
                    service(TextReaderReferencesExtractorAdapter::class),
                ]
            )
            ->call(
                'setLogger',
                [
                    service(LoggerService::SERVICE_ID),
                ]
            );

        $services->set(TranscriptionMimeTypesProvider::class)
            ->public();
    }
}
