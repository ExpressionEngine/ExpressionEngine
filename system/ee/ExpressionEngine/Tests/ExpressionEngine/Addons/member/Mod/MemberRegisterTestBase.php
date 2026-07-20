<?php

use PHPUnit\Framework\TestCase;

if (!defined('QUERY_MARKER')) {
    define('QUERY_MARKER', '?');
}

if (!class_exists('Auth_result')) {
    class Auth_result
    {
        public static $constructed = [];
        public static $rememberCalls = 0;
        public static $sessionCalls = 0;

        public function __construct(stdClass $member)
        {
            self::$constructed[] = (int) ($member->member_id ?? 0);
        }

        public function remember_me($remember = true)
        {
            self::$rememberCalls++;
        }

        public function start_session($cp_sess = false)
        {
            self::$sessionCalls++;
        }

        public static function reset(): void
        {
            self::$constructed = [];
            self::$rememberCalls = 0;
            self::$sessionCalls = 0;
        }
    }
}

class MemberRegisterTestStop extends RuntimeException
{
}

require_once rtrim(PATH_ADDONS, '/') . '/member/mod.member.php';
require_once rtrim(PATH_ADDONS, '/') . '/member/mod.member_register.php';

class MemberRegisterFixture extends Member_register
{
    public $loadedElement = '';

    public function __construct()
    {
    }

    public function get_language_listing($default = 'english')
    {
        return '<option value="english">english</option>';
    }

    public function _load_element($which)
    {
        return $this->loadedElement;
    }
}

abstract class MemberRegisterTestBase extends TestCase
{
    private static $memberRegisterHelpersLoaded = false;

    protected $subject;
    protected $db;
    protected $input;
    protected $output;
    protected $modelService;
    protected $load;
    protected $functions;
    protected $router;

    protected function setUp(): void
    {
        $this->loadMemberRegisterDependencies();

        if (!function_exists('ee') || !method_exists(ee(), 'setMock')) {
            $this->markTestSkipped('EE mock container is not available for this test.');
        }

        $_POST = [];
        $this->setupCoreMocks();
        $this->subject = (new ReflectionClass('MemberRegisterFixture'))->newInstanceWithoutConstructor();
    }

    protected function tearDown(): void
    {
        $_POST = [];
        if (class_exists('Auth_result') && method_exists('Auth_result', 'reset')) {
            Auth_result::reset();
        }
        if (function_exists('ee') && method_exists(ee(), 'resetMocks')) {
            ee()->resetMocks();
        }
        parent::tearDown();
    }

    protected function callPrivateMethod($method, array $args = [])
    {
        $reflection = new ReflectionMethod($this->subject, $method);
        if (PHP_VERSION_ID < 80100) {
            \TestReflectionHelper::makeAccessible($reflection);
        }

        return $reflection->invokeArgs($this->subject, $args);
    }

    protected function setConfigItem($key, $value): void
    {
        ee()->config->items[$key] = $value;
    }

    protected function setInputGetPost(array $values): void
    {
        $this->input->getPost = $values;
    }

    private function loadMemberRegisterDependencies(): void
    {
        if (self::$memberRegisterHelpersLoaded) {
            return;
        }

        require_once SYSPATH . 'ee/ExpressionEngine/Boot/boot.common.php';
        require_once APPPATH . 'helpers/string_helper.php';
        require_once APPPATH . 'helpers/text_helper.php';
        if (!function_exists('form_preference')) {
            if (!defined('REQ')) {
                define('REQ', 'CP');
            }
            require_once APPPATH . 'helpers/form_helper.php';
        }

        self::$memberRegisterHelpersLoaded = true;
    }

    private function setupCoreMocks(): void
    {
        ee()->setMock('TMPL', new class extends FakeTemplate {
            public $tagdata = '';
            public $template_engine = null;
            public $form_class = '';
            public $tagparams = [];
            public $setData = [];

            public function parse_variables_row($tagdata, $vars)
            {
                $result = $this->parse_variables($tagdata, [$vars]);
                foreach ($vars as $key => $value) {
                    if (is_scalar($value)) {
                        $result = str_replace('{' . $key . '}', (string) $value, $result);
                    }
                }
                return $result;
            }

            public function parse_inline_errors($tagdata)
            {
                return $tagdata;
            }

            public function set_data($data)
            {
                $this->setData = $data;
            }
        });

        ee()->setMock('config', new class extends FakeConfig {
            public function prep_view_vars($group)
            {
                return [
                    'fields' => [
                        'date_format' => ['type' => 'v', 'value' => 'us'],
                        'time_format' => ['type' => 'v', 'value' => '24'],
                        'include_seconds' => ['type' => 'v', 'value' => 'n'],
                    ],
                ];
            }
        });

        ee()->config->items = [
            'allow_member_registration' => 'y',
            'site_name' => 'Test Site',
            'site_url' => 'https://example.com/',
            'un_min_len' => 4,
            'pw_min_len' => 8,
            'req_mbr_activation' => 'none',
            'default_primary_role' => 5,
            'require_terms_of_service' => 'n',
            'registration_auto_login' => 'n',
            'use_recaptcha' => 'n',
            'new_member_notification' => 'n',
            'mbr_notification_emails' => '',
            'mail_format' => 'text',
            'webmaster_email' => 'admin@example.com',
            'webmaster_name' => 'Admin',
            'activation_redirect' => '',
            'site_id' => 1,
            'deft_lang' => 'english',
        ];

        $this->functions = new class extends FakeFunctions {
            public $protected = [];

            public function fetch_action_id($class, $method)
            {
                return 11;
            }

            public function get_protected_form_params($params)
            {
                return 'encoded';
            }

            public function form_declaration($data)
            {
                $class = isset($data['class']) ? $data['class'] : '';
                $id = isset($data['id']) ? $data['id'] : '';
                return '<form id="' . $id . '" class="' . trim((string) $class) . '">';
            }

            public function handle_protected()
            {
                return $this->protected;
            }

            public function determine_return()
            {
                return '/return-success';
            }

            public function determine_error_return()
            {
                return '/return-error';
            }

            public function random($type, $length)
            {
                return str_repeat('a', (int) $length);
            }

            public function fetch_email_template($name)
            {
                return ['title' => 'Subject', 'data' => 'Body'];
            }

            public function redirect($url)
            {
                return 'REDIRECT:' . $url;
            }
        };
        ee()->setMock('functions', $this->functions);

        $this->db = new class extends eeDbArMock {
            public $selectedColumns = '';
            public $whereValues = [];
            public $getTable = '';
            public $lastWhereBoardId = null;
            public $lastGetWhereTable = null;
            public $lastGetWhereConditions = [];
            public $queries = [];
            public $memberFieldRows = [];
            public $captchaCountResult = 1;

            public function select($columns = '*')
            {
                $this->selectedColumns = $columns;
                return $this;
            }

            public function where($field = null, $value = null)
            {
                if ($field !== null) {
                    $this->whereValues[$field] = $value;
                    if ($field === 'board_id') {
                        $this->lastWhereBoardId = $value;
                    }
                }
                return parent::where($field, $value);
            }

            public function order_by($field, $direction = '')
            {
                return $this;
            }

            public function get($table = '', $limit = null, $offset = null)
            {
                $this->getTable = $table;
                if ($table === 'forum_boards') {
                    $boardId = $this->lastWhereBoardId ?? 1;
                    return new eeDbResultMock([[
                        'board_forum_url' => '/forums/' . $boardId,
                        'board_id' => (int) $boardId,
                        'board_label' => 'Forum ' . $boardId,
                    ]]);
                }

                if ($table === 'member_fields') {
                    return new eeDbResultMock($this->memberFieldRows);
                }

                return new eeDbResultMock($this->rows);
            }

            public function query($sql)
            {
                $this->queries[] = $sql;

                if (stripos($sql, 'SELECT COUNT(*) AS count FROM exp_captcha') !== false) {
                    return new eeDbResultMock([['count' => $this->captchaCountResult]]);
                }

                return new eeDbResultMock([]);
            }

            public function get_where($table, $where = [])
            {
                $this->lastGetWhereTable = $table;
                $this->lastGetWhereConditions = $where;
                if ($table === 'members') {
                    return new eeDbResultMock([[
                        'member_id' => (int) ($where['member_id'] ?? 0),
                        'group_id' => 1,
                        'role_id' => 1,
                        'username' => 'test-user',
                    ]]);
                }

                return new eeDbResultMock([]);
            }

            public function escape_str($str)
            {
                return addslashes((string) $str);
            }
        };
        ee()->setMock('db', $this->db);

        $this->input = new class {
            public $postValues = [];
            public $getPost = [];
            public $ipAddress = '127.0.0.1';

            public function post($key, $xss = false)
            {
                return $this->postValues[$key] ?? '';
            }

            public function get_post($key)
            {
                return $this->getPost[$key] ?? false;
            }

            public function ip_address()
            {
                return $this->ipAddress;
            }
        };
        ee()->setMock('input', $this->input);

        $this->output = new class {
            public $throwOnMessage = false;
            public $throwOnFormError = false;
            public $throwOnUserError = false;
            public $messages = [];
            public $userErrors = [];
            public $formErrors = [];

            public function show_message($data)
            {
                $this->messages[] = $data;
                if ($this->throwOnMessage) {
                    throw new MemberRegisterTestStop('show_message');
                }
                return 'SHOW_MESSAGE';
            }

            public function show_user_error($type, $messages, $heading = '', $return = '')
            {
                $this->userErrors[] = [$type, $messages, $heading, $return];
                if ($this->throwOnUserError) {
                    throw new MemberRegisterTestStop('show_user_error');
                }
                return 'SHOW_USER_ERROR';
            }

            public function show_form_error($messages)
            {
                $this->formErrors[] = $messages;
                if ($this->throwOnFormError) {
                    throw new MemberRegisterTestStop('show_form_error');
                }
                return 'SHOW_FORM_ERROR';
            }

            public function show_form_error_aliases($result, $aliases)
            {
                return 'SHOW_FORM_ERROR_ALIASES';
            }
        };
        ee()->setMock('output', $this->output);

        $this->router = new class {
            public $class = 'ee';
            public $history = [];

            public function fetch_class()
            {
                return $this->class;
            }

            public function set_class($class)
            {
                $this->class = $class;
                $this->history[] = $class;
            }
        };
        ee()->setMock('router', $this->router);

        $this->load = new class {
            public $helpers = [];
            public $models = [];
            public $libraries = [];
            public $packagePaths = [];

            public function helper($name)
            {
                $this->helpers[] = $name;
            }

            public function model($name)
            {
                $this->models[] = $name;
            }

            public function add_package_path($path)
            {
                $this->packagePaths[] = $path;
            }

            public function remove_package_path($path)
            {
                $this->packagePaths[] = 'remove:' . $path;
            }

            public function library($name)
            {
                $this->libraries[] = $name;
                if ($name === 'channel_form/channel_form_lib') {
                    ee()->setMock('channel_form_lib', new class {
                        public $datepicker = true;
                        public $head = '<script>head</script>';
                        public function compile_js()
                        {
                        }
                    });
                }
                if ($name === 'email') {
                    ee()->setMock('email', new class {
                        public $wordwrap = false;
                        public $mailtype = 'text';
                        public $to = '';
                        public $from = [];
                        public $subject = '';
                        public $message = '';
                        public function from($email, $name)
                        {
                            $this->from = [$email, $name];
                        }
                        public function to($email)
                        {
                            $this->to = $email;
                        }
                        public function subject($subject)
                        {
                            $this->subject = $subject;
                        }
                        public function message($message)
                        {
                            $this->message = $message;
                        }
                        public function Send()
                        {
                            return true;
                        }
                    });
                }
            }
        };
        ee()->setMock('load', $this->load);

        ee()->setMock('localize', new class {
            public $now = 1700000000;
            public function timezone_menu()
            {
                return '<select name="timezone"></select>';
            }
        });

        ee()->setMock('session', new class {
            public $flashdataValues = [];
            public $userdataValues = [];

            public function flashdata($key = null)
            {
                if ($key === null) {
                    return $this->flashdataValues;
                }
                return $this->flashdataValues[$key] ?? null;
            }

            public function set_flashdata($key, $value)
            {
                $this->flashdataValues[$key] = $value;
            }

            public function userdata($key, $default = false)
            {
                return $this->userdataValues[$key] ?? $default;
            }
        });

        ee()->setMock('lang', new class {
            public function line($key)
            {
                return $key;
            }
        });

        ee()->setMock('extensions', new class {
            public $end_script = false;
            public function call($name, ...$args)
            {
                return null;
            }
        });

        ee()->setMock('blockedlist', new class {
            public $blocked = 'n';
            public $allowed = 'y';
        });

        ee()->setMock('stats', new class {
            public $updates = 0;
            public function update_member_stats()
            {
                $this->updates++;
            }
        });

        ee()->setMock('Security/XSS', new class {
            public function clean($value)
            {
                return $value;
            }
        });

        ee()->setMock('Request', new class {
            public $posts = [];
            public function post($key)
            {
                return $this->posts[$key] ?? null;
            }
            public function isPost()
            {
                return false;
            }
        });

        ee()->setMock('Config', new class {
            public function getFile()
            {
                return new class {
                    public function getBoolean($key)
                    {
                        return false;
                    }
                };
            }
        });

        ee()->setMock('Captcha', new class {
            public $required = false;
            public function shouldRequireCaptcha()
            {
                return $this->required;
            }
            public function create()
            {
                return '<captcha />';
            }
        });

        ee()->setMock('Validation', new class {
            public $forceFailures = [];

            public function make()
            {
                $service = $this;
                return new class($service) {
                    private $service;
                    private $rules = [];
                    private $definedRules = [];

                    public function __construct($service)
                    {
                        $this->service = $service;
                    }

                    public function setRule($field, $rule)
                    {
                        $this->rules[$field][] = $rule;
                    }

                    public function defineRule($ruleName, $callback)
                    {
                        $this->definedRules[$ruleName] = $callback;
                    }

                    public function validate($input)
                    {
                        $failed = $this->service->forceFailures;

                        foreach ($this->rules as $field => $rules) {
                            foreach ($rules as $ruleName) {
                                if (!isset($this->definedRules[$ruleName])) {
                                    continue;
                                }

                                $ruleObject = new class {
                                    public function stop()
                                    {
                                    }
                                };
                                $value = $input[$field] ?? null;
                                $result = call_user_func($this->definedRules[$ruleName], $field, $value, [], $ruleObject);

                                if ($result !== true) {
                                    $failed[$field][] = is_string($result) ? $result : $ruleName;
                                }
                            }
                        }

                        return new class($failed) {
                            private $failedRules;

                            public function __construct(array $failedRules)
                            {
                                $this->failedRules = $failedRules;
                            }

                            public function failed()
                            {
                                return !empty($this->failedRules);
                            }

                            public function getFailed()
                            {
                                return $this->failedRules;
                            }
                        };
                    }
                };
            }
        });

        $this->modelService = new class {
            public $memberRecord = null;
            public $roleRecords = [];
            public $memberFieldModels = [];
            public $memberValidateFailed = false;
            public $memberDisplayFields = [];
            public $memberValidationAdded = [];
            public $madeMembers = [];
            public function get($model, $id = null)
            {
                return new class($this, $model, $id) {
                    private $service;
                    private $model;
                    private $id;
                    public function __construct($service, $model, $id)
                    {
                        $this->service = $service;
                        $this->model = $model;
                        $this->id = $id;
                    }
                    public function fields(...$args)
                    {
                        return $this;
                    }
                    public function filter(...$args)
                    {
                        return $this;
                    }
                    public function with(...$args)
                    {
                        return $this;
                    }
                    public function all()
                    {
                        return $this;
                    }
                    public function indexBy($key)
                    {
                        if ($this->model === 'MemberField') {
                            return $this->service->memberFieldModels;
                        }
                        return [];
                    }
                    public function first()
                    {
                        if ($this->model === 'Member') {
                            return $this->service->memberRecord;
                        }
                        if ($this->model === 'Role') {
                            return $this->service->roleRecords[$this->id] ?? null;
                        }
                        return null;
                    }
                };
            }

            public function make($model, $data)
            {
                if ($model !== 'Member') {
                    return null;
                }

                $service = $this;
                $member = new class($service, $data) {
                    private $service;
                    private $data = [];
                    public $member_id = 1001;
                    public $password = '';
                    public $saved = false;
                    public $hashed = false;
                    public function __construct($service, $data)
                    {
                        $this->service = $service;
                        foreach ($data as $key => $value) {
                            $this->data[$key] = $value;
                        }
                        $this->password = $data['password'] ?? '';
                    }
                    public function __get($name)
                    {
                        return $this->data[$name] ?? null;
                    }
                    public function __set($name, $value)
                    {
                        $this->data[$name] = $value;
                    }
                    public function validate()
                    {
                        return new class($this->service) {
                            private $service;

                            public function __construct($service)
                            {
                                $this->service = $service;
                            }

                            public function failed()
                            {
                                return $this->service->memberValidateFailed;
                            }
                            public function addFailed($field, $rule)
                            {
                                $this->service->memberValidationAdded[$field][] = $rule;
                                $this->service->memberValidateFailed = true;
                            }
                        };
                    }
                    public function getDisplay()
                    {
                        return new class($this->service) {
                            private $service;

                            public function __construct($service)
                            {
                                $this->service = $service;
                            }

                            public function getFields()
                            {
                                return $this->service->memberDisplayFields;
                            }
                        };
                    }
                    public function hashAndUpdatePassword($password)
                    {
                        $this->hashed = true;
                    }
                    public function save()
                    {
                        $this->saved = true;
                    }
                };

                $this->madeMembers[] = $member;
                return $member;
            }
        };
        ee()->setMock('Model', $this->modelService);
    }
}
