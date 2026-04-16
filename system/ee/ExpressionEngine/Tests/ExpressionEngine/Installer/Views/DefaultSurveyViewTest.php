<?php

namespace {
    if (! function_exists('form_radio')) {
        function form_radio($name, $value, $checked = false, $extra = '')
        {
            $checkedAttr = $checked ? ' checked="checked"' : '';

            return '<input type="radio" name="' . $name . '" value="' . $value . '"' . $checkedAttr . ' ' . $extra . '>';
        }
    }

    if (! function_exists('form_submit')) {
        function form_submit($name, $value, $extra = '')
        {
            return '<input type="submit" name="' . $name . '" value="' . $value . '" ' . $extra . '>';
        }
    }
}

namespace ExpressionEngine\Tests\ExpressionEngine\Installer\Views {
use PHPUnit\Framework\TestCase;

class DefaultSurveyViewTest extends TestCase
{
    public function testDefaultSurveyViewRendersFormAndServerDataBlock()
    {
        $action_url = '/installer/submit-survey';
        $anonymous_server_data = [
            'php_version' => '8.2.29',
            'mysql_version' => '8.0.36',
        ];

        ob_start();
        include SYSPATH . 'ee/installer/views/surveys/default_survey.php';
        $output = ob_get_clean();

        $this->assertStringContainsString('action="/installer/submit-survey"', $output);
        $this->assertStringContainsString('toggle_server_data()', $output);
        $this->assertStringContainsString('<dt>php_version</dt>', $output);
        $this->assertStringContainsString('<dd>8.2.29</dd>', $output);
        $this->assertStringContainsString('name="participate_in_survey"', $output);
    }
}
}
