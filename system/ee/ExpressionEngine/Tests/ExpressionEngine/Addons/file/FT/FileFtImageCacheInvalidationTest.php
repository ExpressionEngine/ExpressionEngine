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

class FileFtImageCacheInvalidationTest extends FileFtTestBase
{
    /** @dataProvider convertedFilenames */
    public function testConvertedCacheIsRegeneratedAfterReplacingSource($sourceName, $format, $expectedStem)
    {
        $directory = sys_get_temp_dir() . '/file-cache-invalidation-' . bin2hex(random_bytes(8));
        $filesystem = new \ExpressionEngine\Library\Filesystem\Filesystem(
            new \ExpressionEngine\Library\Filesystem\Adapter\Local(['path' => sys_get_temp_dir()])
        );
        $imageLib = new class extends FileFtImageLibStub {
            public function __call($name, $arguments)
            {
                $result = parent::__call($name, $arguments);
                $config = end($this->initializeCalls);
                // Echo source bytes to test cache freshness without depending on codec availability.
                file_put_contents($config['new_image'], file_get_contents($config['source_image']));

                return $result;
            }
        };
        $this->setImageLib($imageLib);
        $sourcePath = $directory . '/nested/' . $sourceName;
        $model = new FileFtProcessImageModelObjectStub([
            'filesystem' => $filesystem,
            'file_name' => $sourceName,
            'baseServerPath' => $directory . '/',
            'subfoldersPath' => 'nested/',
            'absolutePath' => $sourcePath,
        ]);
        $cleanup = new class extends \ExpressionEngine\Model\File\File {
            public $UploadDestination;
        };
        $destination = \Mockery::mock();
        $destination->server_path = $directory . '/nested/';
        $destination->shouldReceive('getProperty')->with('adapter')->andReturn('local');
        $destination->shouldReceive('getFilesystem')->andReturn($filesystem);
        $destination->FileDimensions = \Mockery::mock();
        $destination->FileDimensions->shouldReceive('pluck')->with('short_name')->andReturn([]);
        $cleanup->UploadDestination = $destination;
        $cleanup->setRawProperty('file_name', $sourceName);
        $cleanup->setRawProperty('directory_id', 0);
        $fieldtype = $this->makeFieldtype();
        $modifier = 'replace_' . $format;
        $params = ['width' => '320'];
        $cacheName = $expectedStem . '_' . $format . '_' . md5(serialize($params)) . '.' . $format;
        $cachePath = $directory . '/nested/_' . $format . '/' . $cacheName;

        try {
            $filesystem->mkdir($directory . '/nested');
            $filesystem->write($sourcePath, 'original image');
            $first = $fieldtype->$modifier(['model_object' => $model], $params, null);

            $this->assertSame($cachePath, $first['source_image']);
            $this->assertSame('original image', $filesystem->read($cachePath));
            $this->assertSame([$format], $imageLib->actionCalls);

            $filesystem->write($sourcePath, 'replacement image', true);
            // Upload replacement already calls full cleanup on the original file model.
            $cleanup->deleteGeneratedFiles();
            $this->assertFalse($filesystem->exists($cachePath));

            $second = $fieldtype->$modifier(['model_object' => $model], $params, null);

            $this->assertSame($first['url'], $second['url']);
            $this->assertSame($cachePath, $second['source_image']);
            $this->assertSame('replacement image', $filesystem->read($cachePath));
            $this->assertSame([$format, $format], $imageLib->actionCalls);
        } finally {
            if (is_dir($directory)) {
                $filesystem->deleteDir($directory);
            }
            \Mockery::close();
        }
    }

    public static function convertedFilenames()
    {
        return [
            'JPEG to WebP' => ['hero.jpg', 'webp', 'hero_.jpg'],
            'PNG to WebP' => ['hero.png', 'webp', 'hero_.png'],
            'JPEG to AVIF' => ['hero.jpg', 'avif', 'hero_.jpg'],
            'PNG to AVIF' => ['hero.png', 'avif', 'hero_.png'],
            'dotted source' => ['hero.v2+test.jpg', 'webp', 'hero.v2+test_.jpg'],
            'truncated source stem' => [str_repeat('a', 180) . '.jpg', 'webp', str_repeat('a', 150)],
            'truncated converted stem' => [str_repeat('a', 147) . '.jpg', 'avif', str_repeat('a', 147) . '_.j'],
        ];
    }
}
