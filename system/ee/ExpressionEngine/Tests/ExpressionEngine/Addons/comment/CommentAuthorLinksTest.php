<?php

namespace ExpressionEngine\Tests\Addons\Comment;

use ExpressionEngine\Addons\Comment\Comment as CommentModule;
use ExpressionEngine\Addons\Comment\Service\Variables\Comment as CommentVars;
use ExpressionEngine\Library\Security\XSS;
use ExpressionEngine\Model\Comment\Comment as CommentModel;
use ExpressionEngine\Model\Member\Member;
use ExpressionEngine\Model\Member\MemberField;
use ExpressionEngine\Service\Formatter\FormatterFactory;
use ExpressionEngine\Service\Model\Collection;
use ExpressionEngine\Service\Model\Facade;
use ExpressionEngine\Service\Model\Query\Builder;
use ExpressionEngine\Service\Permission\Permission;
use ExpressionEngine\Service\Template\Variables\LegacyParser;
use PHPUnit\Framework\TestCase;

class CommentAuthorLinksTest extends TestCase
{
    private const LINK_VARIABLES = ['url_as_author', 'url_or_email_as_author', 'url_or_email_as_link'];
    private $originalPost;
    private $module;
    private $model;

    protected function setUp(): void
    {
        $this->originalPost = $_POST;
        require_once SYSPATH . 'ee/ExpressionEngine/Boot/boot.common.php';
        require_once APPPATH . 'helpers/multibyte_helper.php';
        require_once APPPATH . 'core/Input.php';
        require_once APPPATH . 'core/Lang.php';
        require_once APPPATH . 'libraries/Typography.php';
        require_once APPPATH . 'libraries/Template.php';
        require_once PATH_ADDONS . 'comment/mod.comment.php';
        require_once PATH_ADDONS . 'comment/Service/Variables/Comment.php';

        ee()->resetMocks();
        ee()->setMock('config', new \FakeConfig());
        ee()->setMock('session', new class extends \eeSingletonSessionMock {
            public function userdata($item, $default = false) { return $item === 'member_id' ? 0 : $default; }
        });
        ee()->setMock('localize', (object) ['now' => 100]);
        ee()->setMock('uri', (object) ['uri_string' => 'article']);
        ee()->setMock('functions', new \FakeFunctions());
        ee()->setMock('Permission', $this->createStub(Permission::class));
        ee()->setMock('extensions', new class {
            public function active_hook($name) { return false; }
        });
        // EE_Session is already declared by eeObjectMock.php in the test bootstrap.
        ee()->setMock('Format', new FormatterFactory(
            $this->createStub(\EE_Lang::class), $this->createStub(\EE_Session::class), [], 0
        ));
        ee()->setMock('Security/XSS', new XSS());
        ee()->setMock('input', (new \ReflectionClass(\EE_Input::class))->newInstanceWithoutConstructor());
        ee()->setMock('Variables/Parser', new LegacyParser());
        ee()->setMock('TMPL', (new \ReflectionClass(\EE_Template::class))->newInstanceWithoutConstructor());

        $typography = $this->createStub(\EE_Typography::class);
        $typography->method('parse_type')->willReturnArgument(0);
        $typography->method('encode_email')->willReturnCallback(function ($email, $label = '') {
            return 'email fallback: ' . $email . ' / ' . ($label ?: $email);
        });
        ee()->setMock('typography', $typography);

        $guest = $this->createStub(Member::class);
        $guest->method('getValues')->willReturn([]);
        $this->model = $this->createMock(Facade::class);
        $this->model->method('make')->with('Member')->willReturn($guest);
        ee()->setMock('Model', $this->model);
        $this->module = (new \ReflectionClass(CommentModule::class))->newInstanceWithoutConstructor();
    }

    protected function tearDown(): void
    {
        $_POST = $this->originalPost;
        ee()->resetMocks();
    }

    public static function linkCases(): array
    {
        $cases = [];
        foreach (self::LINK_VARIABLES as $variable) {
            foreach ([
                'ordinary' => ['https://example.test/profile', 'Alex Reader'],
                'quotes' => ['https://example.test/reader"notes\'edition', 'Alex "Reader"'],
                'ampersands' => ['https://example.test/profile?first=one&second=two', 'Alex & Friends'],
                'html4 entities' => ['https://example.test/profile?note=&quot;reader&quot;&amp;page=2', 'Alex &amp; &quot;Reader&quot;'],
                'html5 entities' => ['https://example.test/profile?note=&apos;reader&apos;&amp;page=2', 'Alex &amp; &apos;Reader&apos;'],
                'normalization' => ['example.test/profile', 'Alex Reader'],
            ] as $name => $values) {
                $cases[$variable . ' / ' . $name] = array_merge([$variable], $values);
            }
        }

        return $cases;
    }

    /** @dataProvider linkCases */
    public function testStoredGuestLinksEscapeThePreservedUrl($variable, $url, $name): void
    {
        $this->template([$variable]);
        $fields = $this->memberFields('unselected');
        $this->assertSame([], $fields);

        // Exercise submission transformations in memory, then render an existing value.
        $_POST = ['url' => $url];
        $this->assertSame($url, ee()->input->post('url', true));
        $storedUrl = (string) ee('Format')->make('Text', ee()->input->post('url', true))->url();
        $this->assertSame(strpos($url, '://') === false ? 'http://' . $url : $url, $storedUrl);
        $this->assertSame($storedUrl, filter_var($storedUrl, FILTER_VALIDATE_URL));
        $comment = $this->comment($storedUrl, $name, 'alex@example.test');
        $variables = (new CommentVars($comment, $fields, $this->invoke('getFieldsInTemplate')))->getTemplateVariables();

        $this->assertSame($storedUrl, $variables['url']);
        $this->assertSame('Thanks for the article.', $variables['comment']);
        $this->assertSame($storedUrl, $comment->url);
        $this->assertAnchor($variables[$variable], $storedUrl, $variable === 'url_or_email_as_link' ? $storedUrl : $name);
    }

    /** @dataProvider linkCases */
    public function testPreviewLinksEscapeIndependentlyOfMemberFields($variable, $url, $name): void
    {
        $this->template([$variable, 'comment']);
        $this->model->expects($this->never())->method('get');
        $_POST = ['entry_id' => 1, 'comment' => 'Thanks for the article.', 'url' => $url,
            'name' => $name, 'email' => 'alex@example.test', 'location' => ''];

        // A channel-settings query only; no database connection or writes.
        ee()->setMock('db', new class {
            public function select($columns) {}
            public function where($condition, $value = null) {}
            public function from($tables) {}
            public function dbprefix($table) { return 'exp_' . $table; }
            public function get() { return $this; }
            public function num_rows() { return 1; }
            public function row($key) {
                return ['comment_max_chars' => 0, 'comment_text_formatting' => 'none',
                    'comment_html_formatting' => 'safe', 'comment_auto_link_urls' => 'n',
                    'comment_allow_img_urls' => 'n'][$key];
            }
        });
        $normalized = strpos($url, '://') === false ? 'http://' . $url : $url;
        $label = $variable === 'url_or_email_as_link' ? $normalized : $name;
        $output = $this->module->preview();
        $suffix = '|Thanks for the article.';
        $this->assertSame($suffix, substr($output, -strlen($suffix)));
        $this->assertAnchor(substr($output, 0, -strlen($suffix)), $normalized, $label);
    }

    public static function clearedGuestCases(): array
    {
        return [
            'selected / email' => ['selected', 'alex@example.test'],
            'selected / name' => ['selected', ''],
            'cached / email' => ['cached', 'alex@example.test'],
            'cached / name' => ['cached', ''],
        ];
    }

    /** @dataProvider clearedGuestCases */
    public function testProcessedUrlMemberFieldKeepsGuestFallbacks($mode, $email): void
    {
        $this->template($mode === 'selected' ? array_merge(self::LINK_VARIABLES, ['url']) : self::LINK_VARIABLES);
        $fields = $this->memberFields($mode);
        $this->assertArrayHasKey('url', $fields);
        $templateFields = $this->invoke('getFieldsInTemplate');
        $this->assertSame($mode === 'selected', isset($templateFields['url']));
        $comment = $this->comment('https://example.test/reader"notes', 'Alex Reader', $email);
        $variables = (new CommentVars($comment, $fields, $templateFields))->getTemplateVariables();

        $this->assertSame('', $variables['url']);
        $this->assertSame($email, $variables['url_or_email']);
        $this->assertSame('Alex Reader', $variables['url_as_author']);
        foreach (['url_or_email_as_author', 'url_or_email_as_link'] as $variable) {
            $label = $variable === 'url_or_email_as_author' ? 'Alex Reader' : $email;
            $this->assertSame($email ? 'email fallback: ' . $email . ' / ' . $label : 'Alex Reader', $variables[$variable]);
        }
    }

    private function template(array $variables): void
    {
        ee()->TMPL->var_single = array_combine($variables, $variables);
        ee()->TMPL->tagdata = '{' . implode('}|{', $variables) . '}';
    }

    private function memberFields($mode): array
    {
        $field = $this->createStub(MemberField::class);
        $field->method('__get')->willReturnMap([['field_id', 1], ['field_name', 'url']]);
        $field->method('getId')->willReturn(1);
        ee()->session->set_cache(CommentModule::class, 'member_field_names', ['url' => $field]);
        ee()->session->set_cache(CommentModule::class, 'member_fields', $mode === 'cached' ? ['url' => $field] : []);
        if ($mode === 'selected') {
            $query = $this->createStub(Builder::class);
            $query->method('all')->willReturn(new Collection([$field]));
            $this->model->expects($this->once())->method('get')->with('MemberField', [1])->willReturn($query);
        } else {
            $this->model->expects($this->never())->method('get');
        }

        return $this->invoke('getMemberFields');
    }

    private function comment($url, $name, $email): CommentModel
    {
        $values = [
            'Author' => null, 'author_id' => 0, 'site_id' => 1, 'entry_id' => 1, 'comment_id' => 1,
            'name' => $name, 'email' => $email, 'url' => $url, 'status' => 'o', 'comment' => 'Thanks for the article.',
            'Channel' => (object) ['comment_text_formatting' => 'none', 'comment_html_formatting' => 'safe',
                'comment_auto_link_urls' => 'n', 'comment_allow_img_urls' => 'n', 'comment_system_enabled' => true,
                'comment_url' => '', 'channel_url' => '', 'channel_name' => 'news', 'channel_title' => 'News'],
            'Entry' => (object) ['allow_comments' => true, 'channel_id' => 1, 'entry_id' => 1,
                'author_id' => 1, 'comment_expiration_date' => 0, 'url_title' => 'article', 'title' => 'Article'],
        ];
        $comment = $this->createMock(CommentModel::class);
        $comment->method('__get')->willReturnCallback(function ($key) use ($values) { return $values[$key] ?? null; });
        $comment->expects($this->never())->method('save');

        return $comment;
    }

    private function invoke($name)
    {
        $method = new \ReflectionMethod(CommentModule::class, $name);
        \TestReflectionHelper::makeAccessible($method);

        return $method->invoke($this->module);
    }

    private function assertAnchor($html, $url, $label): void
    {
        $dom = new \DOMDocument();
        $this->assertTrue($dom->loadHTML('<html><body>' . $html . '</body></html>', LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING));
        $body = $dom->getElementsByTagName('body')->item(0);
        $this->assertSame(1, $body->childNodes->length, $html);
        $this->assertSame(1, $body->getElementsByTagName('*')->length, $html);
        $anchor = $body->firstChild;
        $this->assertSame('a', $anchor->nodeName);
        $this->assertSame(1, $anchor->attributes->length, 'Only the intended href attribute may be present: ' . $html);
        $this->assertTrue($anchor->hasAttribute('href'));
        // HTML5 expectations catch double-encoding of existing entities such as &apos;.
        $this->assertSame(html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'), $anchor->getAttribute('href'));
        $this->assertSame(html_entity_decode($label, ENT_QUOTES | ENT_HTML5, 'UTF-8'), $anchor->textContent);
    }
}
