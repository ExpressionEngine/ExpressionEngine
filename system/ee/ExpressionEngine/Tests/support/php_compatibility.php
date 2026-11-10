<?php

// Standalone smoke tests: also runs on PHP 7.2.5, below PHPUnit 9's PHP floor.
$eeRoot = dirname(__DIR__, 3);
define('BASEPATH', $eeRoot . '/legacy/');
define('FILE_WRITE_MODE', 0666);
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    if (error_reporting() & $severity) {
        throw new ErrorException($message, 0, $severity, $file, $line);
    }
    return false;
});
function log_message($level, $message) {}
function compatibility_assert($expected, $actual)
{
    if ($expected !== $actual) {
        throw new RuntimeException('Expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
    }
}
require $eeRoot . '/vendor-build/autoload.php';
spl_autoload_register(function ($class) use ($eeRoot) {
    if (strpos($class, 'ExpressionEngine\\') === 0) {
        $path = $eeRoot . '/' . str_replace('\\', '/', $class) . '.php';
        if (is_file($path)) {
            require $path;
        }
    }
});

require BASEPATH . 'libraries/Image_lib.php';

class CompatibilityCurl extends ExpressionEngine\Library\Curl\Request {}

$temporary = sys_get_temp_dir() . '/ee-php-compatibility-' . uniqid();
mkdir($temporary);
$cases = array();
$skipped = array();
if (function_exists('imagejpeg') && function_exists('imagettftext')) {
    $cases['Legacy CAPTCHA bitmap and TrueType geometry'] = function () use ($temporary) {
        require_once BASEPATH . 'helpers/captcha_helper.php';
        $folder = $temporary . '/captcha/';
        mkdir($folder);
        try {
            foreach (array('', BASEPATH . 'fonts/texb.ttf') as $font) {
                $captcha = create_captcha(array('word' => 'example', 'img_path' => $folder,
                    'img_url' => '/captcha/', 'font_path' => $font, 'img_width' => 153, 'img_height' => 31));
                compatibility_assert('example', $captcha['word']);
                preg_match('~src="/captcha/([^"/]+)"~', $captcha['image'], $match);
                $path = $folder . $match[1];
                compatibility_assert(array(153, 31, IMAGETYPE_JPEG), array_slice(getimagesize($path), 0, 3));
                $image = imagecreatefromjpeg($path);
                compatibility_assert(153, imagesx($image));
                if (PHP_VERSION_ID < 80000) { imagedestroy($image); }
            }
        } finally {
            foreach (glob($folder . '*') as $file) { unlink($file); }
            rmdir($folder);
        }
    };
} else {
    $skipped[] = 'Legacy CAPTCHA (GD JPEG/TrueType support unavailable)';
}
if (extension_loaded('curl')) {
    $cases['Curl request and callback'] = function () use ($temporary) {
        $path = $temporary . '/response.txt';
        file_put_contents($path, "HTTP/1.1 200 OK\r\nContent-Type: text/plain\r\n\r\nbody");
        $request = new CompatibilityCurl('file://' . $path, array('CURLOPT_HEADER' => false), function ($body) { return strtoupper($body); });
        compatibility_assert('BODY', $request->exec());
    };
} else {
    $skipped[] = 'Curl request (curl extension unavailable)';
}
if (extension_loaded('gd')) {
    $cases['Image resize, crop, rotate, and mirror'] = function () use ($temporary) {
        $source = $temporary . '/source.png';
        $image = imagecreatetruecolor(20, 20);
        imagepng($image, $source);
        if (PHP_VERSION_ID < 80000) { imagedestroy($image); }
        unset($image);
        foreach (array('resize', 'crop', 'rotate', 'mirror') as $operation) {
            $destination = $temporary . '/' . $operation . '.png';
            $lib = new EE_Image_lib(array('source_image' => $source, 'new_image' => $destination, 'width' => 10, 'height' => 10, 'rotation_angle' => $operation === 'mirror' ? 'hor' : '90'));
            $method = $operation === 'mirror' ? 'rotate' : $operation;
            compatibility_assert(true, $lib->$method());
            $size = getimagesize($destination);
            compatibility_assert($operation === 'resize' || $operation === 'crop' ? 10 : 20, $size[0]);
        }
    };
} else {
    $skipped[] = 'Image processing (gd extension unavailable)';
}
if (extension_loaded('curl')) {
    $cases['HTTP query objects preserve public properties and scalar encoding'] = function () {
        $data = new class {
            public $q = 'a b';
            public $flag = false;
            public $empty = null;
            private $secret = 'private';
            protected $hidden = 'protected';
        };
        $expected = 'q=a+b&flag=0';
        $get = new ExpressionEngine\Library\Curl\GetRequest('https://example.test/', $data);
        $post = new ExpressionEngine\Library\Curl\PostRequest('https://example.test/', array('filter' => $data));
        compatibility_assert('https://example.test?' . $expected, $get->config[CURLOPT_URL]);
        compatibility_assert('filter%5Bq%5D=a+b&filter%5Bflag%5D=0', $post->config[CURLOPT_POSTFIELDS]);
        compatibility_assert('a b', $data->q);
        if (PHP_VERSION_ID >= 80600) {
            // Older PHP versions expose different DateTime state natively.
            $internal = new ExpressionEngine\Library\Curl\PostRequest('https://example.test/', array('date' => new DateTime('2020-01-01'), 'q' => 'value'));
            compatibility_assert('q=value', $internal->config[CURLOPT_POSTFIELDS]);
        }
    };
    $cases['HTTP query shared objects and array references are not cycles'] = function () {
        $object = (object) array('q' => 'value');
        $child = array('filter' => $object);
        $data = array('one' => &$child, 'two' => &$child);
        $post = new ExpressionEngine\Library\Curl\PostRequest('https://example.test/', $data);
        compatibility_assert('one%5Bfilter%5D%5Bq%5D=value&two%5Bfilter%5D%5Bq%5D=value', $post->config[CURLOPT_POSTFIELDS]);
        compatibility_assert(true, $child['filter'] === $object);
        compatibility_assert(true, $data['one']['filter'] === $data['two']['filter']);
    };
    $cases['HTTP query circular data follows the runtime contract'] = function () {
        $handler = set_error_handler(function () {});
        restore_error_handler();
        $object = (object) array('q' => 'value');
        $object->self = $object;
        $array = array('q' => 'value');
        $array['self'] = &$array;
        $mixed = array('q' => (object) array('n' => 'value'));
        $mixed['self'] = &$mixed;
        $copy = $mixed;
        $copy['temporary'] = true;
        unset($copy['temporary']);
        $first = array('q' => (object) array('n' => 'first'));
        $second = array('q' => (object) array('n' => 'second'));
        $first['next'] = &$second;
        $second['next'] = &$first;
        $samples = array(
            array($object, 'q=value'),
            array(array('one' => $object, 'two' => $object), 'one%5Bq%5D=value&two%5Bq%5D=value'),
            array($array, 'q=value'),
            array($mixed, 'q%5Bn%5D=value'),
            array($copy, 'q%5Bn%5D=value&self%5Bq%5D%5Bn%5D=value'),
            array(array('data' => $copy), 'data%5Bq%5D%5Bn%5D=value&data%5Bself%5D%5Bq%5D%5Bn%5D=value'),
            array($first, 'q%5Bn%5D=first&next%5Bq%5D%5Bn%5D=second')
        );
        foreach ($samples as $sample) {
            foreach (array('GetRequest', 'PostRequest') as $type) {
                $class = 'ExpressionEngine\\Library\\Curl\\' . $type;
                $thrown = false;
                try {
                    $request = new $class('https://example.test/', $sample[0]);
                } catch (InvalidArgumentException $e) {
                    $thrown = true;
                    compatibility_assert('Query data cannot contain circular references.', $e->getMessage());
                }
                // Only PHP 8.6 needs the conversion; older versions stay native.
                compatibility_assert(PHP_VERSION_ID >= 80600, $thrown);
                if (!$thrown) {
                    $expected = $type === 'GetRequest' ? 'https://example.test?' . $sample[1] : $sample[1];
                    $option = $type === 'GetRequest' ? CURLOPT_URL : CURLOPT_POSTFIELDS;
                    compatibility_assert($expected, $request->config[$option]);
                }
                $restored = set_error_handler(function () {});
                restore_error_handler();
                compatibility_assert(true, $handler === $restored);
            }
        }
        compatibility_assert(true, $object->self === $object);
        compatibility_assert('value', $array['self']['q']);
        compatibility_assert(true, $copy['self']['q'] === $mixed['q']);
    };
}

if (extension_loaded('curl') && PHP_VERSION_ID >= 80100) {
    $cases['HTTP query enums preserve native results and error handlers'] = function () {
        // Keep enum syntax out of files parsed by PHP 7.2.5.
        eval('enum CompatibilityBacked: string { case Value = "value"; } enum CompatibilityUnit { case Value; }');
        $handler = set_error_handler(function () {});
        restore_error_handler();
        $post = new ExpressionEngine\Library\Curl\PostRequest('https://example.test/', array('enum' => CompatibilityBacked::Value));
        $expected = PHP_VERSION_ID < 80400 ? 'enum%5Bname%5D=Value&enum%5Bvalue%5D=value' : 'enum=value';
        compatibility_assert($expected, $post->config[CURLOPT_POSTFIELDS]);
        $unit = array('enum' => CompatibilityUnit::Value);
        $thrown = false;
        try {
            $post = new ExpressionEngine\Library\Curl\PostRequest('https://example.test/', $unit);
        } catch (ValueError $e) {
            $thrown = true;
        }
        // PHP 8.4 added scalar enum encoding and rejection of unit enums.
        compatibility_assert(PHP_VERSION_ID >= 80400, $thrown);
        if (!$thrown) {
            compatibility_assert('enum%5Bname%5D=Value', $post->config[CURLOPT_POSTFIELDS]);
        }
        $restored = set_error_handler(function () {});
        restore_error_handler();
        compatibility_assert(true, $handler === $restored);
    };
} else {
    $skipped[] = 'HTTP query enums (requires curl and PHP 8.1+)';
}

$failed = array();
foreach ($cases as $name => $case) {
    try { $case(); } catch (Throwable $error) {
        $failed[$name] = get_class($error) . ': ' . $error->getMessage() . ' in ' . $error->getFile() . ':' . $error->getLine();
    }
}
foreach (glob($temporary . '/*') as $file) { unlink($file); }
rmdir($temporary);
restore_error_handler();
echo json_encode(array('php' => PHP_VERSION, 'passed' => count($cases) - count($failed), 'failed' => $failed, 'skipped' => $skipped), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
exit(count($failed) ? 1 : 0);
