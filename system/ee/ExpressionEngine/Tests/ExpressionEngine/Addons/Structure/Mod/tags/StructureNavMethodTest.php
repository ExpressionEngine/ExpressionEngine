<?php

require_once __DIR__ . '/../StructureTestBase.php';

class StructureNavMethodTest extends StructureTestBase
{
    public function testNavUsesRootStartAndReturnsGeneratedHtml()
    {
        $captured = (object) ['args' => null, 'generateArgs' => null];

        $this->structure->site_pages = [
            'url' => '/',
            'uris' => [
                2 => '/about/',
                7 => '/section/'
            ]
        ];
        $this->structure->sql = new class($captured) {
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function get_uri()
            {
                return '/about/';
            }
            public function get_selective_data($site_id, $current_id, $branch_entry_id, $mode, $show_depth, $max_depth, $status, $include, $exclude, $show_overview, $rename_overview, $show_expired, $show_future, $override_hidden_state, $recursive_overview, $include_site_url)
            {
                $this->captured->args = func_get_args();
                return ['ok' => true];
            }
            public function generate_nav($selective_data, $current_id, $branch_entry_id, $mode, $show_overview, $rename_overview, $override_hidden_state, $recursive_overview, $level)
            {
                $this->captured->generateArgs = func_get_args();
                return 'NAV_HTML';
            }
        };

        $this->setTemplateParams(['mode' => 'sub']);

        $result = $this->structure->nav();

        $this->assertSame('NAV_HTML', $result);
        $this->assertSame(2, $captured->args[1]);
        $this->assertSame(0, $captured->args[2]);
        $this->assertSame('1', $captured->generateArgs[8]);
    }

    public function testNavReturnsEmptyWhenStrictStartFromDoesNotMatch()
    {
        $this->structure->site_pages = [
            'url' => '/',
            'uris' => [
                2 => '/about/'
            ]
        ];
        $this->structure->sql = new class {
            public function get_uri()
            {
                return '/about/';
            }
            public function get_settings()
            {
                return ['add_trailing_slash' => 'n'];
            }
        };

        $this->setTemplateParams([
            'start_from' => '/missing',
            'strict_start_from' => 'yes'
        ]);

        $this->assertSame('', $this->structure->nav());
    }

    public function testNavHandlesNonRootStartFromAndNoTrailingSlashSetting()
    {
        $captured = (object) ['args' => null, 'generateArgs' => null];

        $this->structure->site_pages = [
            'url' => '/',
            'uris' => [
                2 => '/about/',
                7 => '/section'
            ]
        ];
        $this->structure->sql = new class($captured) {
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function get_uri()
            {
                return '/about/';
            }
            public function get_settings()
            {
                return ['add_trailing_slash' => 'n'];
            }
            public function get_selective_data($site_id, $current_id, $branch_entry_id, $mode, $show_depth, $max_depth, $status, $include, $exclude, $show_overview, $rename_overview, $show_expired, $show_future, $override_hidden_state, $recursive_overview, $include_site_url)
            {
                $this->captured->args = func_get_args();
                return ['ok' => true];
            }
            public function generate_nav($selective_data, $current_id, $branch_entry_id, $mode, $show_overview, $rename_overview, $override_hidden_state, $recursive_overview, $level)
            {
                $this->captured->generateArgs = func_get_args();
                return 'NAV_SECTION';
            }
        };

        $this->setTemplateParams([
            'start_from' => '/section/',
            'mode' => 'full'
        ]);

        $result = $this->structure->nav();

        $this->assertSame('NAV_SECTION', $result);
        $this->assertSame(7, $captured->args[2]);
        $this->assertSame('0', $captured->generateArgs[8]);
    }
}

