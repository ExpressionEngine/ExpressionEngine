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

require BASEPATH . 'helpers/text_helper.php';
require BASEPATH . 'libraries/Xmlparser.php';
require BASEPATH . 'libraries/Image_lib.php';
require BASEPATH . 'database/drivers/mysqli/mysqli_connection.php';

class CompatibilityCurl extends ExpressionEngine\Library\Curl\Request {}
class CompatibilityXml extends EE_XMLparser { public function __construct() {} }

class CompatibilityRoute { protected $title = 'Route'; }
class CompatibilitySidebar extends ExpressionEngine\Service\Addon\Controllers\Mcp\AbstractSidebar
{
    public function __construct() {}
    public function readTitle() { return $this->getProperty(CompatibilityRoute::class, 'title'); }
}

$temporary = sys_get_temp_dir() . '/ee-php-compatibility-' . uniqid();
mkdir($temporary);
$cases = array();
$skipped = array();
$cases['Nullable string and size helpers'] = function () {
    require_once BASEPATH . 'helpers/string_helper.php';
    require_once BASEPATH . 'helpers/number_helper.php';
    compatibility_assert('', trim_slashes(null));
    compatibility_assert('example', trim_slashes('/example/'));
    compatibility_assert('', repeater(null, 3));
    compatibility_assert('000', repeater('0', 3));
    compatibility_assert('', reduce_double_slashes(null));
    compatibility_assert(array('a/b', 'c/d'), reduce_double_slashes(array('a//b', 'c///d')));
    compatibility_assert('', reduce_multiples(null));
    compatibility_assert(array('a,b', 'c,d'), reduce_multiples(array('a,,b', 'c,,,d')));
    compatibility_assert(0, get_bytes(null));
    compatibility_assert(1048576, get_bytes('1M'));
};
$cases['Missing and present file dimensions'] = function () {
    $column = new ExpressionEngine\Library\CP\FileManager\Columns\Dimensions('dimensions');
    compatibility_assert('', $column->renderTableCell(null, null, (object) array('file_hw_original' => null)));
    compatibility_assert('640x480', $column->renderTableCell(null, null, (object) array('file_hw_original' => '480 640')));
};
$cases['Unicode entities and named-entity preservation'] = function () {
    $examples = array(
        '&#0;' => "\0", '&#127;' => "\x7f", '&#128;' => "\xc2\x80",
        '&#2047;' => "\xdf\xbf", '&#2048;' => "\xe0\xa0\x80",
        '&#55295;' => "\xed\x9f\xbf", '&#57344;' => "\xee\x80\x80",
        '&#65535;' => "\xef\xbf\xbf", '&#65536;' => "\xf0\x90\x80\x80",
        '&#128512;' => "\xf0\x9f\x98\x80", '&#131072;' => "\xf0\xa0\x80\x80",
        '&#1114111;' => "\xf4\x8f\xbf\xbf", '&#55296;' => '&#55296;',
        '&#57343;' => '&#57343;', '&#1114112;' => '&#1114112;',
        '&#999999999999999999999999;' => '&#999999999999999999999999;',
        '&#0000131072;' => "\xf0\xa0\x80\x80",
    );
    foreach ($examples as $input => $expected) {
        compatibility_assert($expected, entities_to_ascii($input));
    }
    compatibility_assert('&amp;A', entities_to_ascii('&amp;&#65;', false));
    compatibility_assert('&A', entities_to_ascii('&amp;&#65;', true));
};
$cases['Pro Search nullable result keys'] = function () use ($eeRoot) {
    require $eeRoot . '/ExpressionEngine/Addons/pro_search/helpers/pro_search_helper.php';
    $rows = array(array('id' => null, 'value' => 'first'), array('id' => null, 'value' => 'last'), array('id' => 0, 'value' => 'zero'));
    compatibility_assert(array('' => 'last', 0 => 'zero'), pro_flatten_results($rows, 'value', 'id'));
    compatibility_assert(array('' => $rows[0], 0 => $rows[2]), pro_associate_results($rows, 'id'));
};
$cases['Pro Search missing result key preserves its diagnostic'] = function () {
    $diagnostics = array();
    set_error_handler(function ($severity, $message) use (&$diagnostics) {
        $diagnostics[] = array($severity, $message);
        return true;
    });
    try {
        $result = pro_flatten_results(array(array('value' => 'alpha')), 'value', 'id');
    } finally {
        restore_error_handler();
    }
    compatibility_assert(array('' => 'alpha'), $result);
    compatibility_assert(array(array(
        PHP_VERSION_ID < 80000 ? E_NOTICE : E_WARNING,
        PHP_VERSION_ID < 80000 ? 'Undefined index: id' : 'Undefined array key "id"'
    )), $diagnostics);
};
$cases['Pro Search nullable field-name arguments'] = function () {
    $rows = array(array('' => 'first', 'value' => 0), array('' => 'last', 'value' => false));
    compatibility_assert(array('first' => 0, 'last' => false), pro_flatten_results($rows, 'value', null));
    compatibility_assert(array('first', 'last'), pro_flatten_results($rows, null));
    compatibility_assert(array('first' => 'first', 'last' => 'last'), pro_flatten_results($rows, null, null));
    compatibility_assert(array('first' => $rows[0], 'last' => $rows[1]), pro_associate_results($rows, null));
};
$cases['Pro Search optional prefixes'] = function () {
    $params = array('form_id' => '10', '0id' => 'zero');
    foreach (array(null, false, '') as $prefix) {
        compatibility_assert(array(), pro_array_get_prefixed($params, $prefix, true));
    }
    compatibility_assert(array('id' => '10'), pro_array_get_prefixed($params, 'form_', true));
    compatibility_assert(array('id' => 'zero'), pro_array_get_prefixed($params, '0', true));
};
$cases['Channel Form missing field and entry data'] = function () use ($eeRoot) {
    if (!defined('PATH_ADDONS')) {
        define('PATH_ADDONS', $eeRoot . '/ExpressionEngine/Addons/');
    }
    require_once PATH_ADDONS . 'channel/libraries/channel_form/Channel_form_lib.php';
    $form = (new ReflectionClass('Channel_form_lib'))->newInstanceWithoutConstructor();
    $form->custom_fields = array();
    compatibility_assert(array(), $form->get_field_options('unknown_field'));
    $form->channel = (object) array('deft_category' => 7);
    foreach (array(null, false) as $entry) {
        $form->entry = $entry;
        compatibility_assert(array(), $form->get_selected_cats());
    }
};
$cases['Unsaved channel field settings and handler'] = function () {
    require_once BASEPATH . 'libraries/Api.php';
    require_once BASEPATH . 'libraries/api/Api_channel_fields.php';
    $api = new Api_channel_fields();
    $handler = new class {
        public $config;
        public function _init($config) { $this->config = $config; }
    };
    $api->field_types['compatibility'] = $handler;
    $api->global_settings = array('compatibility' => array());
    foreach (array(null, 0, 7, 'member_field_2') as $id) {
        $settings = array('field_type' => 'compatibility', 'field_name' => 'test');
        $api->set_settings($id, $settings);
        compatibility_assert($settings, $api->get_settings($id));
        compatibility_assert($handler, $api->setup_handler($id, true));
        compatibility_assert($id, $handler->config['field_id']);
    }
    compatibility_assert(false, (new Api_channel_fields())->setup_handler(null, true));
};
$cases['Unsaved upload directory overrides'] = function () {
    $directory = new class extends ExpressionEngine\Model\File\UploadDestination {
        public function __construct() {}
        public function configure($id, $overrides) { $this->id = $id; $this->_property_overrides = $overrides; }
        public function originalId() { return $this->id; }
    };
    $fetch = new ReflectionMethod(ExpressionEngine\Model\File\UploadDestination::class, 'fetchOverride');
    if (PHP_VERSION_ID < 80100) { $fetch->setAccessible(true); }
    foreach (array(null, 0, 7) as $id) {
        $directory->configure($id, array());
        compatibility_assert('default', $fetch->invoke($directory, 'name', 'default', true));
        $directory->configure($id, array(($id ?? '') => array('name' => 'override')));
        compatibility_assert('override', $fetch->invoke($directory, 'name', 'default', true));
        compatibility_assert($id, $directory->originalId());
    }
};
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
$cases['Protected sidebar properties'] = function () {
    compatibility_assert('Route', (new CompatibilitySidebar())->readTitle());
};
$cases['XML success and failure cleanup'] = function () {
    $parser = new CompatibilityXml();
    compatibility_assert(true, $parser->parse_into_struct('<root><item>value</item></root>'));
    compatibility_assert(false, $parser->parse_into_struct('<root>'));
};
$cases['SimplePie RSS parsing'] = function () {
    require_once BASEPATH . 'libraries/simplepie/SimplePieAutoloader.php';
    $feed = new SimplePie();
    $feed->enable_cache(false);
    $feed->set_raw_data('<?xml version="1.0"?><rss version="2.0"><channel><title>Compatibility</title><link>https://example.invalid/</link><description>Feed</description></channel></rss>');
    compatibility_assert(true, $feed->init());
    compatibility_assert('Compatibility', $feed->get_title());
};
$cases['SimplePie relative URL normalization'] = function () {
    require_once BASEPATH . 'libraries/simplepie/SimplePieAutoloader.php';
    foreach (array('', '../feed.xml', '//example.test/feed', '?query=value', '#fragment') as $url) {
        $iri = new SimplePie_IRI($url);
        compatibility_assert(null, $iri->scheme);
        compatibility_assert($url, $iri->get_iri());
    }
    $iri = new SimplePie_IRI('http://example.test:80/feed');
    compatibility_assert('http://example.test/feed', $iri->get_iri());
};
$cases['Patched dependency declarations'] = function () {
    foreach (array(
        'League\\Flysystem\\FileExistsException', 'League\\Flysystem\\FileNotFoundException',
        'League\\Flysystem\\Cached\\Storage\\PhpRedis', 'League\\Flysystem\\Cached\\Storage\\Predis',
    ) as $class) {
        compatibility_assert(true, class_exists('ExpressionEngine\\Dependency\\' . $class));
    }
};
if (extension_loaded('ftp')) {
    $cases['FTP adapter compilation'] = function () {
        compatibility_assert(true, class_exists('ExpressionEngine\\Dependency\\League\\Flysystem\\Adapter\\Ftp'));
    };
} else {
    $skipped[] = 'FTP adapter (ftp extension unavailable; covered by lint)';
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
    $cases['GD QR rendering and object release'] = function () {
        $renderer = new ExpressionEngine\Dependency\BaconQrCode\Renderer\GDLibRenderer(120);
        $writer = new ExpressionEngine\Dependency\BaconQrCode\Writer($renderer);
        foreach (array('first', 'second') as $value) {
            $png = $writer->writeString($value);
            compatibility_assert("\x89PNG\r\n\x1a\n", substr($png, 0, 8));
            compatibility_assert(120, getimagesizefromstring($png)[0]);
        }
    };
} else {
    $skipped[] = 'Image and QR processing (gd extension unavailable)';
}
if (extension_loaded('pdo_mysql')) {
    $cases['Custom PDO options before connection'] = function () {
        $config = array('hostname' => '127.0.0.1', 'username' => 'audit', 'password' => '', 'database' => 'audit', 'char_set' => 'utf8mb4', 'pconnect' => false, 'dbcollat' => 'utf8mb4_unicode_ci', 'port' => 1,
            'MYSQL_ATTR_INIT_COMMAND' => 'SET NAMES utf8mb4', 'MYSQL_ATTR_SSL_VERIFY_SERVER_CERT' => false, 'MYSQL_ATTR_COMPRESS' => false);
        try {
            (new CI_DB_mysqli_connection($config))->open();
        } catch (PDOException $e) {
            return; // No server is needed: option evaluation must be warning-free.
        }
        throw new RuntimeException('Expected unused local port to refuse connection');
    };
    $cases['SimplePie MySQL cache option before connection'] = function () {
        require_once BASEPATH . 'libraries/simplepie/SimplePieAutoloader.php';
        $cache = new SimplePie_Cache_MySQL('mysql://audit:@127.0.0.1:1/audit', 'audit', 'rss');
        compatibility_assert(false, $cache->load());
    };
} else {
    $skipped[] = 'MySQL options (pdo_mysql extension unavailable)';
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

$cases += require __DIR__ . '/php86_cases.php';
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
