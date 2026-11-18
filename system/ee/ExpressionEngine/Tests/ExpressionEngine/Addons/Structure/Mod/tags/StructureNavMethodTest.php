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

    public function testNavUsesExplicitEntryIdAndPassesTemplateFlagsToCollaborators()
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
                return ['filtered' => true];
            }
            public function generate_nav($selective_data, $current_id, $branch_entry_id, $mode, $show_overview, $rename_overview, $override_hidden_state, $recursive_overview, $level)
            {
                $this->captured->generateArgs = func_get_args();
                return 'CUSTOM_NAV';
            }
        };

        $this->setTemplateParams([
            'entry_id' => 99,
            'mode' => 'full',
            'show_depth' => 3,
            'max_depth' => 5,
            'status' => 'open|closed',
            'include' => ['10', '11'],
            'exclude' => ['12'],
            'show_overview' => 'yes',
            'rename_overview' => 'Summary',
            'show_expired' => 'yes',
            'show_future_entries' => 'yes',
            'override_hidden_state' => 'yes',
            'recursive_overview' => 'yes',
            'site_url' => 'yes',
        ]);

        $result = $this->structure->nav();

        $this->assertSame('CUSTOM_NAV', $result);
        $this->assertSame(99, $captured->args[1]);
        $this->assertSame(0, $captured->args[2]);
        $this->assertSame('full', $captured->args[3]);
        $this->assertSame(3, $captured->args[4]);
        $this->assertSame(5, $captured->args[5]);
        $this->assertSame('open|closed', $captured->args[6]);
        $this->assertSame(['10', '11'], $captured->args[7]);
        $this->assertSame(['12'], $captured->args[8]);
        $this->assertSame('yes', $captured->args[9]);
        $this->assertSame('Summary', $captured->args[10]);
        $this->assertSame('yes', $captured->args[11]);
        $this->assertSame('yes', $captured->args[12]);
        $this->assertSame('yes', $captured->args[13]);
        $this->assertSame('yes', $captured->args[14]);
        $this->assertSame('yes', $captured->args[15]);
        $this->assertSame(['filtered' => true], $captured->generateArgs[0]);
        $this->assertSame(99, $captured->generateArgs[1]);
        $this->assertSame(0, $captured->generateArgs[2]);
        $this->assertSame('full', $captured->generateArgs[3]);
        $this->assertSame('yes', $captured->generateArgs[4]);
        $this->assertSame('Summary', $captured->generateArgs[5]);
        $this->assertSame('yes', $captured->generateArgs[6]);
        $this->assertSame('yes', $captured->generateArgs[7]);
        $this->assertSame('1', $captured->generateArgs[8]);
    }

    public function testNavDecodesAndNormalizesStartFromWhenTrailingSlashIsEnabled()
    {
        $captured = (object) ['args' => null, 'generateArgs' => null];

        $this->structure->site_pages = [
            'url' => '/',
            'uris' => [
                2 => '/about/',
                8 => '/section/child/'
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
                return ['add_trailing_slash' => 'y'];
            }
            public function get_selective_data($site_id, $current_id, $branch_entry_id, $mode, $show_depth, $max_depth, $status, $include, $exclude, $show_overview, $rename_overview, $show_expired, $show_future, $override_hidden_state, $recursive_overview, $include_site_url)
            {
                $this->captured->args = func_get_args();
                return ['normalized' => true];
            }
            public function generate_nav($selective_data, $current_id, $branch_entry_id, $mode, $show_overview, $rename_overview, $override_hidden_state, $recursive_overview, $level)
            {
                $this->captured->generateArgs = func_get_args();
                return 'NORMALIZED_NAV';
            }
        };

        $this->setTemplateParams([
            'start_from' => '//section&#47;child//',
            'mode' => 'main',
        ]);

        $result = $this->structure->nav();

        $this->assertSame('NORMALIZED_NAV', $result);
        $this->assertSame(2, $captured->args[1]);
        $this->assertSame(8, $captured->args[2]);
        $this->assertSame('main', $captured->args[3]);
        $this->assertSame(8, $captured->generateArgs[2]);
        $this->assertSame('0', $captured->generateArgs[8]);
    }

    public function testNavContinuesWhenNonStrictStartFromDoesNotMatch()
    {
        $captured = (object) ['args' => null, 'generateArgs' => null];

        $this->structure->site_pages = [
            'url' => '/',
            'uris' => [
                2 => '/about/'
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
                return ['fallback' => true];
            }
            public function generate_nav($selective_data, $current_id, $branch_entry_id, $mode, $show_overview, $rename_overview, $override_hidden_state, $recursive_overview, $level)
            {
                $this->captured->generateArgs = func_get_args();
                return 'FALLBACK_NAV';
            }
        };

        $this->setTemplateParams([
            'start_from' => '/missing',
            'strict_start_from' => false
        ]);

        $result = $this->structure->nav();

        $this->assertSame('FALLBACK_NAV', $result);
        $this->assertSame(2, $captured->args[1]);
        $this->assertSame(0, $captured->args[2]);
        $this->assertSame(0, $captured->generateArgs[2]);
        $this->assertSame('0', $captured->generateArgs[8]);
    }
}
