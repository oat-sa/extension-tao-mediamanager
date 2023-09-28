<?php

namespace oat\taoMediaManager\test\unit\model;

use oat\taoMediaManager\model\ZipExporter;

/**
 * The purpose of this class is to make protected methods of ZipExporter class public, so they can be tested.
 */
class ZipExporterTester extends ZipExporter
{
    public function createZipFile($filename, array $exportClasses = [], array $exportFiles = []): string
    {
        return parent::createZipFile($filename, $exportClasses, $exportFiles);
    }
}
