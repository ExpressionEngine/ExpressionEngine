<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

require_once __DIR__ . '/FileFtTestBase.php';

class FileFtProcessImageTest extends FileFtTestBase
{
    /** @dataProvider sourceCallbackCacheStates */
    public function testProcessImageResolvesSourceOnlyOnCacheMiss($cached)
    {
        $fieldtype = $this->makeFieldtype();
        $filesystem = new FileFtProcessImageFilesystemStub();
        $params = ['width' => '1600', 'height' => '900'];
        $filename = 'hero_crop_' . md5(serialize($params)) . '.jpg';
        $destination = '/srv/uploads/gallery/_crop' . DIRECTORY_SEPARATOR . $filename;
        $source = '/srv/uploads/gallery/prepared-source.jpg';
        $filesystem->existingPaths[$destination] = $cached;
        $data = [
            'model_object' => new FileFtProcessImageModelObjectStub(['filesystem' => $filesystem]),
            'fs_filename' => 'hero.jpg',
            'filesystem' => $filesystem,
            'source_image' => '/srv/uploads/gallery/hero.jpg',
        ];
        $calls = 0;
        $resolveSource = function () use (&$calls, $cached, $filesystem, $destination, $source) {
            $this->assertFalse($cached, 'A cached output must not resolve its source dependency.');
            $this->assertSame([$destination], $filesystem->existsCalls);
            $calls++;

            return $source;
        };
        $method = new ReflectionMethod(File_ft::class, 'process_image');
        $method->setAccessible(true);

        $result = $method->invoke($fieldtype, 'crop', $data, $params, false, false, $resolveSource);

        $this->assertSame('https://example.com/uploads/gallery/_crop/' . $filename, $result);
        $this->assertSame($cached ? 0 : 1, $calls);
        $this->assertSame([$destination], $filesystem->existsCalls);
        $this->assertSame([$cached ? $destination : $source], $filesystem->copyToTempFileCalls);
        $this->assertSame($cached ? [] : ['crop'], $this->imageLibMock->actionCalls);
        $this->assertSame($cached ? [] : [$destination], array_column($filesystem->writeStreamCalls, 'path'));
    }

    public function sourceCallbackCacheStates()
    {
        return [
            'cache miss' => [false],
            'cache hit' => [true],
        ];
    }
}
