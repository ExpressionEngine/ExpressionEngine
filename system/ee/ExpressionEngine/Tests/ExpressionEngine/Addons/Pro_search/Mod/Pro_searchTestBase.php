<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../../eeObjectMock.php';

if (!defined('APP_VER')) {
	define('APP_VER', '7.5.14');
}
if (!defined('BASEPATH')) {
	define('BASEPATH', __DIR__);
}
if (!defined('PATH_ADDONS')) {
	define('PATH_ADDONS', '/Users/tomjaeger/Sites/ee_repo/system/ee/ExpressionEngine/Addons/');
}
if (!defined('PATH_MOD')) {
	define('PATH_MOD', PATH_ADDONS);
}

// Include real Pro Search module
require_once PATH_ADDONS . 'pro_search/mod.pro_search.php';

// Local lightweight fakes for common EE services used by tests
if (!defined('LD')) { define('LD', '{'); }
if (!defined('RD')) { define('RD', '}'); }
if (!defined('AMP')) { define('AMP', '&'); }
if (!defined('QUERY_MARKER')) { define('QUERY_MARKER', '?'); }
if (!defined('AJAX_REQUEST')) { define('AJAX_REQUEST', false); }

// Minimal stubs for helper functions referenced by Pro Search
if (!function_exists('pro_format')) {
	function pro_format($value, $format = 'html') { return $value; }
}
if (!function_exists('pro_array_get_prefixed')) {
	function pro_array_get_prefixed($array, $prefix, $include_params = false) { return []; }
}
if (!function_exists('pro_search_encode')) {
	function pro_search_encode($arr) { return base64_encode(json_encode($arr)); }
}
if (!function_exists('pro_search_decode')) {
	function pro_search_decode($str, $assoc = true) { $d = json_decode(base64_decode($str), $assoc); return $d ?: []; }
}
if (!function_exists('pro_set_cache')) {
	function pro_set_cache($pkg, $key, $val) { /* no-op */ }
}
if (!function_exists('pro_get_cache')) {
	function pro_get_cache($pkg, $key) { return false; }
}
if (!function_exists('pro_not_empty')) {
	function pro_not_empty($v) { return $v !== null && $v !== '' && $v !== false; }
}
if (!function_exists('pro_array_is_numeric')) {
	function pro_array_is_numeric($arr) { foreach ((array) $arr as $v) { if (!is_numeric($v)) return false; } return true; }
}
if (!function_exists('is_ajax')) {
	function is_ajax() { return false; }
}
if (!function_exists('pro_prep_in_conditionals')) {
	function pro_prep_in_conditionals($tagdata) { return $tagdata; }
}
if (!function_exists('pro_param_string')) {
	function pro_param_string($arr) { return ''; }
}

// Local lightweight fakes for common EE services used by tests
class ProSearchFakeTMPL
{
	public $tagdata = '';
	public $tagparams = [];
	public $var_single = [];
	public $var_pair = [];
	public $site_ids = [1];
	public $no_results = 'NO_RESULTS';
	public $search_fields = [];

	public function fetch_param($key, $default = null)
	{
		return array_key_exists($key, $this->tagparams) ? $this->tagparams[$key] : $default;
	}

	public function parse_variables_row($tagdata, $row)
	{
		$result = $tagdata;
		foreach ($row as $k => $v) {
			if (is_array($v)) { continue; }
			$result = str_replace('{' . $k . '}', (string) $v, $result);
		}
		return $result;
	}

	public function parse_variables($tagdata, $rows)
	{
		// Very small implementation: only supports replacing {key} by row[key] for the first row
		if (empty($rows)) {
			return $tagdata;
		}
		$out = '';
		foreach ($rows as $row) {
			$item = $tagdata;
			foreach ($row as $k => $v) {
				$item = str_replace('{' . $k . '}', (string) $v, $item);
			}
			$out .= $item;
		}
		return $out;
	}

	public function no_results()
	{
		return 'NO_RESULTS';
	}

	public function log_item($msg)
	{
		// no-op
	}
}

class ProSearchFakeFunctions
{
	public $lastRedirect = null;

	public function fetch_site_index($a = 0, $b = 0)
	{
		return '/';
	}

	public function create_url($path = '')
	{
		// Simulate absolute URL
		return 'https://example.com/' . ltrim($path, '/');
	}

	public function form_declaration($data)
	{
		return '<form>';
	}

	public function fetch_action_id($class, $method)
	{
		return 123;
	}

	public function redirect($url)
	{
		$this->lastRedirect = $url;
	}
}

abstract class Pro_searchTestBase extends TestCase
{
	/** @var Pro_search */
	protected $pro;

	protected function setUp(): void
	{
		// Ensure ee() has required core mocks, then override specific ones we need
		ee()->setMock('TMPL', new ProSearchFakeTMPL());
		ee()->setMock('functions', new ProSearchFakeFunctions());

		// Instantiate without running constructor to avoid heavy EE bootstrapping
		$this->pro = (new ReflectionClass('Pro_search'))
			->newInstanceWithoutConstructor();

		// Default settings stub
		$this->setSettingsStub([
			'encode_query' => 'y',
			'default_result_page' => '/search/results',
			'can_manage_shortcuts' => [],
			'build_index_act_key' => 'secret'
		]);

		// Default params stub with minimal behavior
		$this->setParamsStub([
			'get' => [],
		]);

		// Default site id
		$this->setPrivate('site_id', 1);
		$this->setPrivate('package', 'pro_search');
		$this->setPrivate('class_name', 'Pro_search');

		// Provide minimal session and lang mocks expected by methods
		$this->setMock('session', new class {
			public $flashdata = [];
			public $userdataMap = ['member_id' => 0, 'ip_address' => '127.0.0.1', 'group_id' => 1];
			public function flashdata($key){ return $this->flashdata[$key] ?? null; }
			public function set_flashdata($key,$val){ $this->flashdata[$key] = $val; }
			public function userdata($key){ return $this->userdataMap[$key] ?? null; }
			public function set_flashdata_data(array $a){ foreach($a as $k=>$v){ $this->set_flashdata($k,$v);} }
		});
		$this->setMock('lang', new class {
			public function loadfile($p){}
			public function line($k){ return $k; }
		});

		// Security mock for legacy restore_xid checks
		$this->setMock('security', new class {
			public function restore_xid(){}
		});

		// Extensions mock default
		$this->setMock('extensions', new class {
			public $end_script = false;
			public function active_hook($name){ return false; }
			public function call($name, $data){ return $data; }
		});

		// Default log model stub with required API
		$this->setMock('pro_search_log_model', new class {
			public $key = 'pro_search_log_id';
			public function insert($arr){ return 1; }
			public function prune($site_id, $size){}
			public function add_num_results($total, $log_id){}
			public function get_popular_keywords(){ return []; }
		});

		// Pro_multibyte library stub
		$this->setMock('pro_multibyte', new class {
			public function strtoupper($s){ return strtoupper($s); }
		});
	}

	private function setPrivate(string $prop, $value): void
	{
		$rp = new ReflectionProperty('Pro_search', $prop);
		$rp->setAccessible(true);
		$rp->setValue($this->pro, $value);
	}

	protected function setTemplateParams(array $params): void
	{
		ee()->TMPL->tagparams = $params;
	}

	protected function setTemplateTagdata(string $tagdata): void
	{
		ee()->TMPL->tagdata = $tagdata;
	}

	protected function setSettingsStub(array $map): void
	{
		$settings = new class($map) {
			public $prefix = 'pro_search_';
			private $map;
			public function __construct($map){ $this->map = $map; }
			public function get($key){ return $this->map[$key] ?? null; }
		};
		$this->setPrivate('settings', $settings);
	}

	protected function setParamsStub(array $initial): void
	{
		$stub = new class($initial) {
			private $values;
			public $tagparams = [];
			public function __construct($initial){ $this->values = $initial; }
			public function set($key = null, $val = null){ if ($key !== null) { $this->values[$key] = $val; } }
			public function reset(){ $this->values = []; }
			public function get($key = null){ if ($key === null) return $this->values; return $this->values[$key] ?? null; }
			public function overwrite($params, $decode = false){ $this->values = is_array($params) ? $params : $this->values; }
			public function combine(){ /* no-op */ }
			public function set_defaults(){ /* no-op */ }
			public function get_vars($prefix){ return []; }
			public function apply($k = null, $v = null){ if ($k !== null) { $this->values[$k] = $v; } }
			public function explode($val){ return [is_array($val) ? $val : explode('|', (string) $val), true]; }
			public function implode($vals, $in){ return implode('|', (array) $vals); }
			public function query_given(){ return isset($this->values['query']) && $this->values['query'] !== ''; }
			public function valid_query(){ return true; }
			public function site_ids(){ return [1]; }
			public function merge($a, $b){ return array_values(array_unique(array_merge((array) $a, (array) $b))); }
		};
		$this->setPrivate('params', $stub);
	}

	protected function setMock(string $name, $mock): void
	{
		ee()->setMock($name, $mock);
	}

	protected function getLastRedirect(): ?string
	{
		return ee()->functions->lastRedirect;
	}

	protected function mockEmptyCollectionModel(): void
	{
		$this->setMock('pro_search_collection_model', new class {
			public function get_by_site($ids){ return []; }
			public function get_by_param($v, $rows){ return $rows; }
			public function get_by_language($v, $in, $rows){ return $rows; }
		});
	}
}


