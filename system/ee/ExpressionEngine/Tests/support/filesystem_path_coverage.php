<?php

// Standalone so the same regressions run on exact PHP 7.2.5 and current PHPUnit.
$eeRoot = dirname(__DIR__, 3);
define('BASEPATH', $eeRoot . '/legacy/');
define('SYSPATH', dirname($eeRoot) . '/');
define('APPPATH', BASEPATH);
define('APP_NAME', 'ExpressionEngine');
define('REQ', 'PAGE');
define('MB_ENABLED', extension_loaded('mbstring'));
define('FILE_WRITE_MODE', 0666);
define('DIR_WRITE_MODE', 0777);
define('FOPEN_WRITE_CREATE', 'ab');
define('FOPEN_WRITE_CREATE_DESTRUCTIVE', 'wb');
define('FOPEN_READ', 'rb');
define('PASSWORD_MAX_LENGTH', 1000);
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    if (error_reporting() & $severity) {
        throw new ErrorException($message, 0, $severity, $file, $line);
    }
    return false;
});
require $eeRoot . '/vendor-build/autoload.php';
require $eeRoot . '/ExpressionEngine/Boot/boot.common.php';
spl_autoload_register(function ($class) use ($eeRoot) {
    if (strpos($class, 'ExpressionEngine\\') === 0) {
        $base = strpos($class, 'ExpressionEngine\\Updater\\') === 0 ? $eeRoot . '/installer/updater/' : $eeRoot . '/';
        $file = $base . str_replace('\\', '/', $class) . '.php';
        if (is_file($file)) { require $file; }
    }
});
function log_message($level, $message) {}
function lang($key) { return $key; }
function path_assert($expected, $actual) {
    if ($expected !== $actual) {
        throw new RuntimeException('Expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
    }
}
function path_throws($class, $callback) {
    try { $callback(); } catch (Throwable $error) {
        path_assert($class, get_class($error));
        return;
    }
    throw new RuntimeException('Expected ' . $class);
}
function path_instance($class) { return (new ReflectionClass($class))->newInstanceWithoutConstructor(); }
function path_invoke($object, $method, array $arguments = array()) {
    $method = new ReflectionMethod($object, $method);
    if (PHP_VERSION_ID < 80100) { $method->setAccessible(true); }
    return $method->invokeArgs($object, $arguments);
}
function path_set($object, $name, $value) {
    $property = new ReflectionProperty($object, $name);
    if (PHP_VERSION_ID < 80100) { $property->setAccessible(true); }
    $property->setValue($object, $value);
}
class PathTestServices {
    public $db;
    public $file_upload_preferences_model;
    public $legacy_api;
    public $api_template_structure;
    public $pro_variables_settings;
    public $pro_variables_variable_model;
    public $stubPaths = array();
    public $file_path;
    public $uploadPrefs = array();
    public $fileRow;
    public $config;
    public $lang;
    public $localize;
    public $load;
    public $input;
    public $form_validation;
    public $extensions;
    public $session;
    public $values = array();
    public $messages = array();
    public $now = 1000000000;
    public $userdata = array('member_id' => 0);
    public function __construct() {
        foreach (array('db', 'file_upload_preferences_model', 'legacy_api', 'api_template_structure', 'pro_variables_settings', 'pro_variables_variable_model', 'config', 'lang', 'localize', 'load', 'input', 'form_validation', 'extensions', 'session') as $name) { $this->$name = $this; }
    }
    public function item($name) { return isset($this->values[$name]) ? $this->values[$name] : false; }
    public function slash_item($name, $unused = null) { return rtrim((string) $this->item($name), '/') . '/'; }
    public function post($name) { return $this->item($name); }
    public function get_post($name) { return $this->item($name); }
    public function set_message($name, $message) { $this->messages[$name] = $message; }
    public function helper($name) { require_once BASEPATH . 'helpers/' . $name . '_helper.php'; }
    public function loadfile($name) {}
    public function model($name) {}
    public function library($name) {}
    public function instantiate($name) {}
    public function file_extensions($type, $engine = null) { return '.html'; }
    public function get_template_file_info($path) { return array('engine' => null); }
    public function all_file_extensions() { return array('.html' => array('type' => 'webpage', 'engine' => null)); }
    public function getGeneratorStubPaths($provider, $folder, $theme) { return $this->stubPaths; }
    public function get_file_upload_preferences($id) { return $this->uploadPrefs; }
    public function select($fields) { return $this; }
    public function from($table) { return $this; }
    public function where($key, $value) { return $this; }
    public function get($name = null, $id = null) { return $this; }
    public function first() { return new stdClass(); }
    public function row() { return $this->fileRow; }
    public function get_file_vars($ids) { return array(array('variable_name' => 'sentinel')); }
    public function line($name) { return $name; }
    public function active_hook($name) { return false; }
}
$services = new PathTestServices();
function ee($service = null) {
    global $services;
    if ($service === 'File') { return new ExpressionEngine\Service\File\Factory(); }
    if ($service === 'MimeType') { return new ExpressionEngine\Library\Mime\MimeType(); }
    return $services;
}
class CP_Controller {}
require BASEPATH . 'core/Config.php';
require BASEPATH . 'helpers/string_helper.php';
require BASEPATH . 'helpers/file_helper.php';
require BASEPATH . 'helpers/captcha_helper.php';
require BASEPATH . 'libraries/Driver.php';
require BASEPATH . 'libraries/Cache/Cache.php';
require BASEPATH . 'libraries/Cache/drivers/Cache_file.php';
foreach (array('Image_lib', 'Filemanager', 'Ftp', 'Zip', 'Email', 'Form_validation', 'Validate', 'View', 'Upload', 'Messages', 'Messages_send', 'Members', 'Template') as $library) {
    require BASEPATH . 'libraries/' . $library . '.php';
}
require $eeRoot . '/ExpressionEngine/Addons/block_and_allow/mcp.block_and_allow.php';
require BASEPATH . 'libraries/simplepie/SimplePieAutoloader.php';
require $eeRoot . '/ExpressionEngine/Addons/pro_search/libraries/Pro_search_words.php';
require $eeRoot . '/ExpressionEngine/Addons/pro_variables/libraries/Pro_variables_sync.php';
class Member {}
require $eeRoot . '/ExpressionEngine/Addons/member/mod.member_images.php';
class PathTestImage extends EE_Image_lib {
    public $errors = array();
    public function set_error($message) { $this->errors[] = $message; }
    public function image_create_gd($path = '', $image_type = '') { return true; }
}
class PathTestFtp extends EE_Ftp {
    public $errors = array();
    public function _is_conn() { return true; }
    public function _error($message) { $this->errors[] = $message; }
}
class PathTestEmail extends EE_Email {
    public $errors = array();
    protected function _set_error_message($message, $value = '') { $this->errors[] = $message; }
    public function buildForTest() { return $this->_build_message(); }
}
class PathTestUpdatePrepare extends ExpressionEngine\Cli\Commands\CommandUpdatePrepare {
    public function fail($messages = null) { throw new RuntimeException($messages); }
}
$temporary = sys_get_temp_dir() . '/ee-path-compatibility-' . uniqid();
mkdir($temporary);
define('PATH_CACHE', $temporary . '/cache/');
define('PATH_DICT', $temporary . '/');
define('PATH_TMPL', $temporary . '/');
define('PATH_ADDONS', $eeRoot . '/ExpressionEngine/Addons/');
define('PATH_JS', 'src');
define('PATH_THEMES_GLOBAL_ASSET', $temporary . '/');
define('URL_THEMES_GLOBAL_ASSET', '/assets/');
define('PATH_CP_THEME', $temporary . '/');
define('URL_THEMES', '/themes/');
mkdir(PATH_CACHE);
$file = $temporary . '/sentinel.txt';
file_put_contents($file, 'sentinel');
$invalid = $file . "\0suffix";
$cases = array();

$cases['file_exists_safe preserves native existence checks and rejects NUL paths'] = function () use ($temporary, $file) {
    path_assert(true, file_exists_safe($file));
    path_assert(true, file_exists_safe($temporary));
    path_assert(false, file_exists_safe($file . '.missing'));
    path_assert(false, file_exists_safe(''));
    foreach (array("\0" . $file, $file . "\0", $file . "\0suffix", "\0") as $path) {
        path_assert(false, file_exists_safe($path));
    }
    $unicode = $temporary . '/caf' . "\xc3\xa9" . '.txt';
    file_put_contents($unicode, 'unicode');
    path_assert(true, file_exists_safe($unicode));
    path_assert(file_exists('file://' . $file), file_exists_safe('file://' . $file));
    path_assert('sentinel', file_get_contents($file));
};

$cases['local roots keep the existing LogicException and allow_missing behavior'] = function () use ($temporary) {
    foreach (array("\0" . $temporary, $temporary . "\0", $temporary . "\0suffix") as $path) {
        path_throws('LogicException', function () use ($path) { new ExpressionEngine\Library\Filesystem\Adapter\Local(array('path' => $path)); });
        $adapter = new ExpressionEngine\Library\Filesystem\Adapter\Local(array('path' => $path, 'allow_missing' => true));
        path_assert(false, $adapter->has('sentinel.txt'));
    }
    $fs = new ExpressionEngine\Library\Filesystem\Filesystem(new ExpressionEngine\Library\Filesystem\Adapter\Local(array('path' => $temporary)));
    path_assert(true, $fs->exists('sentinel.txt'));
    // Preserve the established normalizer's treatment of control characters.
    path_assert(true, $fs->exists("sentinel.txt\0"));
};
foreach (array('exists', 'isDir', 'isFile', 'isReadable', 'isWritable') as $method) {
    $cases['updater ' . $method . ' rejects NUL paths'] = function () use ($method, $file, $temporary) {
        $fs = new ExpressionEngine\Updater\Library\Filesystem\Filesystem();
        foreach (array("\0" . $file, $file . "\0suffix", $file . "\0") as $path) { path_assert(false, $fs->$method($path)); }
        path_assert(true, $fs->$method($method === 'isDir' ? $temporary : $file));
        path_assert(false, $fs->$method($file . '.missing/child'));
    };
}
$cases['updater reads keep their filesystem exception'] = function () use ($invalid) {
    $fs = new ExpressionEngine\Updater\Library\Filesystem\Filesystem();
    foreach (array('read', 'mtime', 'touch') as $method) {
        path_throws('ExpressionEngine\\Updater\\Library\\Filesystem\\FilesystemException', function () use ($fs, $method, $invalid) { $fs->$method($invalid); });
    }
};
foreach (array('get', 'get_metadata', 'save', 'delete', 'is_writable') as $method) {
    $cases['file cache ' . $method . ' rejects malformed keys without touching the valid key'] = function () use ($method) {
        $cache = new EE_Cache_file();
        path_assert(true, $cache->save('sentinel', 'cached'));
        foreach (array("sentinel\0", "bad\0namespace/sentinel") as $key) {
            path_assert(false, $method === 'save' ? $cache->save($key, 'changed') : $cache->$method($key));
        }
        path_assert('cached', $cache->get('sentinel'));
    };
}
foreach (array('save', 'load', 'mtime', 'touch', 'unlink') as $method) {
    $cases['SimplePie cache ' . $method . ' rejects malformed paths'] = function () use ($method, $temporary) {
        foreach (array(array($temporary . "\0", 'feed', 'spc'), array($temporary, "feed\0", 'spc'), array($temporary, 'feed', "spc\0")) as $args) {
            $cache = new SimplePie_Cache_File($args[0], $args[1], $args[2]);
            path_assert(false, $method === 'save' ? $cache->save(array('title' => 'feed')) : $cache->$method());
        }
        $cache = new SimplePie_Cache_File($temporary, 'feed', 'spc');
        path_assert(true, $cache->save(array('title' => 'feed')));
        path_assert(array('title' => 'feed'), $cache->load());
        path_assert(true, is_int($cache->mtime()));
        path_assert(true, $cache->touch());
        path_assert(true, $cache->unlink());
    };
}
$cases['image metadata and fonts retain their error messages'] = function () use ($invalid) {
    $image = new PathTestImage();
    path_assert(false, $image->get_image_properties($invalid));
    path_assert(array('imglib_invalid_path'), $image->errors);
    $image->wm_use_truetype = true;
    $image->wm_font_path = $invalid;
    path_assert(false, $image->text_watermark());
    path_assert('imglib_missing_font', $image->errors[1]);
};
$cases['editable image checks return false for malformed paths'] = function () use ($invalid, $file) {
    $manager = path_instance('Filemanager');
    path_assert(false, $manager->is_editable_image($invalid, 'image/png'));
    path_assert(false, $manager->is_editable_image($file, 'text/plain'));
};
$cases['CAPTCHA directories fail and malformed font paths use bitmap text'] = function () use ($temporary, $services) {
    path_assert(false, create_captcha(array('img_path' => $temporary . "\0", 'img_url' => '/captcha/')));
    $services->values['captcha_path'] = $temporary . "\0";
    $services->values['captcha_url'] = '/captcha/';
    $captcha = path_instance('ExpressionEngine\\Library\\Captcha');
    path_assert(false, $captcha->create());
    if (function_exists('imagejpeg')) {
        mkdir($temporary . '/captcha');
        $captcha = create_captcha(array('word' => 'path', 'img_path' => $temporary . '/captcha/', 'img_url' => '/captcha/', 'font_path' => "bad\0font.ttf"));
        path_assert('path', $captcha['word']);
        path_assert(1, preg_match('~src="/captcha/([^"/]+)"~', $captcha['image'], $match));
        path_assert(IMAGETYPE_JPEG, getimagesize($temporary . '/captcha/' . $match[1])[2]);
    }
};
$cases['FTP and ZIP retain missing-source handling'] = function () use ($invalid, $temporary) {
    $ftp = new PathTestFtp();
    path_assert(false, $ftp->upload($invalid, 'unused'));
    path_assert(array('ftp_no_source_file'), $ftp->errors);
    $zip = new EE_Zip();
    path_assert(false, $zip->read_file($invalid));
    path_assert('', $zip->zipdata);
    path_assert($zip->_get_mod_time($temporary . '/missing'), $zip->_get_mod_time($invalid));
};
$cases['email attachments retain missing-file errors and binary content'] = function () use ($invalid) {
    $email = path_instance('PathTestEmail');
    $email->wordwrap = false;
    $email->attach($invalid);
    path_assert(false, $email->buildForTest());
    path_assert(array('lang:email_attachment_missing'), $email->errors);
    $email = path_instance('PathTestEmail');
    $email->wordwrap = false;
    $email->attach("binary\0content", 'attachment', 'data.bin', 'application/octet-stream');
    path_assert(null, $email->buildForTest());
    path_assert(array(), $email->errors);
};
$cases['path validators return false instead of throwing'] = function () use ($invalid, $services, $file) {
    $validator = path_instance('EE_Form_validation');
    foreach (array('file_exists', 'writable') as $method) { path_assert(false, $validator->$method($invalid)); }
    $services->values['image_resize_protocol'] = 'imagemagick';
    $controller = path_instance('ExpressionEngine\\Controller\\Settings\\ContentDesign');
    path_assert(false, $controller->_validateResizeLibraryPath($invalid));
    path_assert(true, $controller->_validateResizeLibraryPath($file));
    $services->values['allow_dictionary_pw'] = 'n';
    $controller = path_instance('ExpressionEngine\\Controller\\Settings\\SecurityPrivacy');
    path_assert(false, $controller->_validateDictionaryPath("dictionary\0.txt"));
    path_assert(true, $controller->_validateDictionaryPath('sentinel.txt'));
    $block = path_instance('Block_and_allow_mcp');
    path_assert(false, path_invoke($block, '_check_path', array($invalid)));
};
$cases['dictionary lookups treat malformed configuration as a missing file'] = function () use ($services) {
    $services->values['allow_dictionary_pw'] = 'n';
    $services->values['name_of_dictionary_file'] = "sentinel.txt\0";
    $services->values['pw_min_len'] = 8;
    path_assert(false, path_instance('EE_Validate')->lookup_dictionary_word('sentinel'));
    path_assert(false, path_instance('EE_Form_validation')->_lookup_dictionary_word('sentinel'));
    $rule = path_instance('ExpressionEngine\\Service\\Validation\\Rule\\ValidPassword');
    $rule->setAllValues(array('username' => 'somebody'));
    path_assert(true, $rule->validate('password', 'strong-password'));
};
$cases['license loading keeps its missing-license result'] = function () use ($invalid, $file) {
    path_assert(null, (new ExpressionEngine\Service\License\License($invalid, 'key'))->getRawLicense());
    path_assert('sentinel', (new ExpressionEngine\Service\License\License($file, 'key'))->getRawLicense());
};

$cases['theme lookup keeps fallback paths and user theme precedence'] = function () use ($temporary) {
    $theme = new ExpressionEngine\Service\Theme\Theme('core/', '/core/', $temporary . '/', '/user/', 'assets/', $temporary . '/');
    path_assert($temporary . '/sentinel.txt', $theme->getPath('sentinel.txt'));
    path_assert($temporary . '/sentinel.txt', $theme->getUserPath('sentinel.txt'));
    path_assert('/user/sentinel.txt', $theme->getUrl('sentinel.txt'));
    path_assert('core/missing', $theme->getPath('missing'));
    foreach (array("\0sentinel.txt", "sentinel.txt\0") as $name) {
        path_assert('core/' . $name, $theme->getPath($name));
        path_assert(false, $theme->getUserPath($name));
        path_assert('/core/' . $name, $theme->getUrl($name));
        path_assert(array(), $theme->listThemes($name));
        path_assert(array(), $theme->listUserThemes($name));
    }
};
$cases['view lookup retains its missing-view exception'] = function () use ($temporary) {
    $view = path_instance('ExpressionEngine\Service\View\View');
    $provider = new class($temporary) {
        private $path;
        public function __construct($path) { $this->path = $path; }
        public function getPath() { return $this->path; }
    };
    path_set($view, 'provider', $provider);
    path_set($view, 'path', "sentinel\0");
    path_throws('Exception', function () use ($view) { path_invoke($view, 'getPath'); });
    mkdir($temporary . '/views');
    file_put_contents($temporary . '/views/sentinel.php', 'view');
    path_set($view, 'path', 'sentinel');
    path_assert($temporary . '/views/sentinel.php', path_invoke($view, 'getPath'));
};
$cases['legacy asset helpers retain their missing-file fallbacks'] = function () {
    $view = path_instance('View');
    path_assert(null, $view->script_tag("file\0.js"));
    path_assert(true, strpos($view->head_link("file\0.css"), '<link rel="stylesheet"') === 0);
};
$cases['local path column treats malformed directories as missing'] = function () use ($invalid) {
    $column = path_instance('ExpressionEngine\Model\File\Column\LocalPath');
    $column->unserialize($invalid);
    path_assert(null, $column->files);
};
$cases['site preference validation records invalid paths without checking their writability'] = function () use ($invalid) {
    $config = path_instance('EE_Config');
    path_invoke($config, '_check_paths', array(array('captcha_path' => $invalid)));
    $property = new ReflectionProperty($config, '_config_path_errors');
    if (PHP_VERSION_ID < 80100) { $property->setAccessible(true); }
    $errors = $property->getValue($config);
    path_assert(array('invalid_path'), array_keys($errors));
    path_assert(true, isset($errors['invalid_path']['captcha_path']));
};
$cases['upload content checks reject malformed temporary paths'] = function () use ($invalid, $temporary) {
    $upload = path_instance('EE_Upload');
    $upload->file_temp = $invalid;
    path_assert(false, $upload->do_xss_clean());
    path_assert(false, $upload->do_embedded_php_check());
    $upload->file_temp = $temporary . '/empty';
    file_put_contents($upload->file_temp, '');
    path_assert(false, $upload->do_xss_clean());
    path_assert(false, $upload->do_embedded_php_check());
};
$cases['private message uploads retain their directory error'] = function () use ($invalid) {
    $messages = path_instance('EE_Messages_send');
    $messages->upload_path = $invalid;
    $messages->attachments = array(1);
    path_assert('unable_to_recieve_attach', $messages->_attach_file());
    path_assert('unable_to_recieve_attach', $messages->_duplicate_files());
};
$cases['avatar listing returns no files for malformed directories'] = function () use ($invalid) {
    path_assert(array(), path_invoke(path_instance('Member_images'), '_get_avatars', array($invalid)));
};
$cases['CLI upgrade configuration lookup returns false for malformed directories'] = function () use ($invalid, $temporary) {
    file_put_contents($temporary . '/upgrade.config.php', '<?php return array();');
    foreach (array('CommandUpdatePrepare', 'CommandUpdateRunHook') as $name) {
        $command = path_instance('ExpressionEngine\Cli\Commands\\' . $name);
        path_assert(false, path_invoke($command, 'getConfigPath', array($invalid)));
        path_assert($temporary . '/upgrade.config.php', path_invoke($command, 'getConfigPath', array($temporary)));
    }
};
$cases['Pro Search retains its unknown-language fallback'] = function () {
    $words = new Pro_search_words("en\0");
    path_assert(null, $words->stem('flowers'));
};


$cases['file manager retains thumbnail and download fallbacks'] = function () use ($invalid, $services) {
    $manager = path_instance('Filemanager');
    $files = array(array('file_name' => 'sentinel.txt'));
    path_assert($files, $manager->find_thumbs(array('server_path' => $invalid), $files));
    $services->uploadPrefs = array(1 => array('server_path' => $invalid));
    $services->fileRow = (object) array('upload_location_id' => 1, 'file_name' => 'sentinel.txt');
    path_assert(false, $manager->download_files(array(1)));
};
$cases['template stubs retain missing-file errors and extension lookup'] = function () use ($temporary, $services) {
    $stub = path_instance('ExpressionEngine\Service\View\Stub');
    $services->stubPaths = array($temporary);
    path_set($stub, 'path', "sentinel\0");
    path_throws('Exception', function () use ($stub) { path_invoke($stub, 'getPath'); });
    file_put_contents($temporary . '/sentinel.html', 'stub');
    path_set($stub, 'path', 'sentinel');
    path_assert($temporary . '/sentinel.html', path_invoke($stub, 'getPath'));
};
$cases['Pro Variables rejects malformed sync directories before creating files'] = function () use ($invalid, $services) {
    $services->file_path = $invalid;
    path_assert(false, (new Pro_variables_sync())->files());
};
$cases['CLI directory validation keeps its existing failure'] = function () use ($invalid, $temporary) {
    $command = path_instance('PathTestUpdatePrepare');
    try {
        path_invoke($command, 'checkIfPathAllowed', array($invalid));
        throw new LogicException('Expected CLI failure');
    } catch (RuntimeException $error) {
        path_assert($invalid . ' does not exist.', $error->getMessage());
    }
    path_assert(true, path_invoke($command, 'checkIfPathAllowed', array($temporary)));
};
$cases['member image uploads retain their directory error'] = function () use ($invalid, $services) {
    $services->values['avatar_path'] = $invalid;
    $_POST = array('submit' => 'upload');
    $_FILES = array('userfile' => array('size' => 100));
    path_assert(array('error', array('edit_avatar', 'image_assignment_error')), (new Members())->upload_member_images('avatar', 1));
};


$cases['channel set import retains its validation error for malformed paths'] = function () use ($invalid) {
    $set = path_instance('ExpressionEngine\Service\ChannelSet\Set');
    $result = new class {
        public $errors = array();
        public function addError($error) { $this->errors[] = $error; }
    };
    path_set($set, 'path', $invalid);
    path_set($set, 'result', $result);
    path_invoke($set, 'load');
    path_assert(array('channel_set_invalid'), $result->errors);
};
$cases['template file discovery rejects malformed groups and filenames'] = function () use ($services, $temporary) {
    $services->values['save_tmpl_files'] = 'y';
    $services->values['site_short_name'] = 'site';
    $template = path_instance('EE_Template');
    path_assert(false, $template->_create_from_file("group\0", 'index'));
    mkdir($temporary . '/site/group.group', 0777, true);
    path_assert(false, $template->_create_from_file('group', "index\0"));
    path_assert(false, $template->_create_from_file('group', 'missing'));
};

$passed = 0;
$failed = array();
try {
    foreach ($cases as $name => $case) {
        try { $case(); ++$passed; } catch (Throwable $error) { $failed[$name] = get_class($error) . ': ' . $error->getMessage(); }
    }
    path_assert('sentinel', file_get_contents($file));
} finally {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($temporary, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($iterator as $entry) { $entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname()); }
    rmdir($temporary);
}
echo json_encode(array('php' => PHP_VERSION, 'passed' => $passed, 'failed' => $failed), JSON_PRETTY_PRINT), "\n";
exit($failed ? 1 : 0);
