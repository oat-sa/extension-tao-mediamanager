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
 * Copyright (c) 2014 (original work) Open Assessment Technologies SA;
 *
 *
 */

namespace oat\taoMediaManager\test\model;

use oat\tao\test\TaoPhpUnitTestRunner;
use oat\taoMediaManager\model\ZipExporter;

class ZipExporterTest extends TaoPhpUnitTestRunner
{
    /**
     * @return string[]
     */
    public function pathProvider()
    {
        return [
            ['filename'],
            ['filename/', 'filename'],
            ['../../../../../tmp/filename', 'filename'],
            ['../../../../../tmp/filename.zip', 'filename.zip'],
            ['.......filename.zip'],
            ['........zip'],
        ];
    }

    /**
     * @dataProvider pathProvider
     *
     * @param $originalPath
     * @param $expectedResultingPath
     */
    public function testExportIsProtectedFromPathTraversal($originalPath, $expectedResultingPath = null)
    {
        if ($expectedResultingPath === null) {
            $expectedResultingPath = $originalPath;
        }

        $exporterMock = $this->getMockBuilder(ZipExporter::class)
            ->disableOriginalConstructor()
            ->setMethods(['createZipFile'])
            ->getMock();

        $exporterMock->expects($this->once())
            ->method('createZipFile')
            ->with($expectedResultingPath)
            ->will($this->returnValue('does_not_matter'));

        $exporterMock->export([
            'filename' => $originalPath,
            'id' => 'no_matter',
        ], '');
    }
}
 