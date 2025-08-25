<?php

require_once __DIR__ . '/StructureTestBase.php';

if (!function_exists('form_dropdown')) {
    function form_dropdown($name, $options, $selected)
    {
        $html = '<select name="' . $name . '">';
        foreach ($options as $value => $label) {
            $sel = ($value === $selected) ? ' selected' : '';
            $html .= '<option value="' . $value . '"' . $sel . '>' . $label . '</option>';
        }
        $html .= '</select>';
        return $html;
    }
}

class StructureSaefSelectTest extends StructureTestBase
{
    protected function setUp(): void
    {
        parent::setUp();
        ee()->setMock('load', new class {
            public function helper($name) {}
        });
    }

    public function testReturnsFalseWhenNoTypeProvided()
    {
        $this->setTemplateParams(['type' => '']);
        $this->assertFalse($this->structure->saef_select());
    }

    public function testUnknownTypeReturnsFalse()
    {
        $this->setTemplateParams(['type' => 'unknown']);
        $this->assertFalse($this->structure->saef_select());
    }

    public function testTemplateTypeBuildsDropdownWithSelectedFromSitePages()
    {
        $this->structure->sql = new class {
            public function get_templates()
            {
                return [
                    ['template_id' => 1, 'group_name' => 'site', 'template_name' => 'index'],
                    ['template_id' => 2, 'group_name' => 'site', 'template_name' => 'page'],
                ];
            }
            public function get_site_pages()
            {
                return ['templates' => [55 => 2]];
            }
        };

        $this->setTemplateParams(['type' => 'template', 'entry_id' => 55]);
        $html = $this->structure->saef_select();

        $this->assertStringContainsString('<select name="structure_template_id">', $html);
        $this->assertStringContainsString('<option value="2" selected>site/page</option>', $html);
    }

    public function testTemplateTypeWithNoTemplatesStillRendersChooseTemplate()
    {
        $this->structure->sql = new class {
            public function get_templates() { return []; }
            public function get_site_pages() { return ['templates' => []]; }
        };
        $this->setTemplateParams(['type' => 'template']);
        $html = $this->structure->saef_select();
        $this->assertStringContainsString('Choose Template', $html);
    }

    public function testParentTypeBuildsDropdownWithDepthIndicators()
    {
        $this->structure->sql = new class {
            public function get_data()
            {
                return [
                    10 => ['depth' => 0, 'title' => 'Home'],
                    11 => ['depth' => 1, 'title' => 'About'],
                    12 => ['depth' => 2, 'title' => 'Team'],
                ];
            }
            public function get_parent_id($entry_id) { return 11; }
        };

        $this->setTemplateParams(['type' => 'parent', 'entry_id' => 99]);
        $html = $this->structure->saef_select();

        $this->assertStringContainsString('<select name="structure_parent_id">', $html);
        $this->assertStringContainsString('<option value="11" selected>-- About</option>', $html);
        $this->assertStringContainsString('<option value="12">---- Team</option>', $html);
    }

    public function testParentTypeWhenParentIdIsZeroSelectsChooseParent()
    {
        $this->structure->sql = new class {
            public function get_data() { return []; }
            public function get_parent_id($entry_id) { return 0; }
        };
        $this->setTemplateParams(['type' => 'parent', 'entry_id' => 99]);
        $html = $this->structure->saef_select();
        $this->assertStringContainsString('<option value="0" selected>Choose Parent</option>', $html);
    }
}



