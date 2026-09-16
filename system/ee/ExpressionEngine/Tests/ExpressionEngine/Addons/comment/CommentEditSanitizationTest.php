<?php

use ExpressionEngine\Addons\Comment\Comment;
use ExpressionEngine\Library\Security\XSS;
use PHPUnit\Framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class CommentEditSanitizationTest extends TestCase
{
    private $get;
    private $post;

    protected function setUp(): void
    {
        $this->get = $_GET;
        $this->post = $_POST;
        ee()->resetMocks();

        require_once SYSPATH . 'ee/ExpressionEngine/Boot/boot.common.php';
        require_once BASEPATH . 'core/Input.php';
        require_once BASEPATH . 'libraries/Typography.php';
        require_once PATH_ADDONS . 'comment/mod.comment.php';

        if (!defined('CSRF_TOKEN')) {
            define('CSRF_TOKEN', 'comment-edit-test-token');
        }
    }

    protected function tearDown(): void
    {
        $_GET = $this->get;
        $_POST = $this->post;
        ee()->resetMocks();
        Mockery::close();
    }

    /**
     * Provide selected comment inputs and their expected sanitized values.
     *
     * @return array
     */
    public static function commentInputs(): array
    {
        $cases = [];
        foreach (['get', 'mixed', 'post'] as $source) {
            // Inert markup rejected by the sanitizer; no executable content.
            $cases[$source . ' rejected markup'] = [
                $source, '<blink>Comment</blink>', '&lt;blink&gt;Comment&lt;/blink&gt;'
            ];
            $ordinary = "Ordinary comment\n<strong>Thank you</strong>";
            $cases[$source . ' ordinary formatting'] = [$source, $ordinary, $ordinary];
            $cases[$source . ' encoded link'] = [
                $source,
                '<a href="https://example.test/report%2523part.txt">Download</a>',
                '<a href="https://example.test/report%23part.txt">Download</a>'
            ];
        }

        return $cases;
    }

    /** @dataProvider commentInputs */
    public function testSelectedCommentIsCleanedBeforeValidationSaveAndOutput($source, $text, $expected): void
    {
        $_GET = ['comment_id' => '7', 'csrf_token' => CSRF_TOKEN, 'comment' => $text];
        $_POST = [];
        if ($source !== 'get') {
            $_POST['comment_id'] = '7';
        }
        if ($source === 'post') {
            $_POST['comment'] = $text;
            $_GET['comment'] = 'Unselected GET comment';
        }

        $config = new FakeConfig();
        $config->items = ['charset' => 'UTF-8', 'disable_csrf_protection' => false, 'global_xss_filtering' => false];
        ee()->setMock('config', $config);
        ee()->setMock('input', (new ReflectionClass(EE_Input::class))->newInstanceWithoutConstructor());
        ee()->setMock('Security/XSS', new class extends XSS {
            protected function _decode_entity($match)
            {
                // Supply the charset without requiring an installed site's config.
                return $this->entity_decode($match[0], 'UTF-8');
            }
        });
        ee()->setMock('session', new class {
            public $userdata = ['member_id' => 17];
            public function cache($class, $key) { return []; }
        });
        ee()->setMock('localize', (object) ['now' => 1000]);
        ee()->setMock('Permission', new class {
            public function can($permission) { return $permission === 'edit_own_comments'; }
        });
        ee()->setMock('functions', new class {
            public function encode_ee_tags($text, $encode) { return $text; }
        });
        ee()->setMock('output', new class {
            public function send_ajax_response($data)
            {
                if (isset($data['error'])) {
                    throw new RuntimeException('Unexpected comment edit rejection');
                }
                return $data;
            }
        });

        // Record persistence boundaries without a database.
        $comment = new class {
            public $comment = 'Original comment';
            public $edit_date;
            public $validated;
            public $saved;
            public function isDirty() { return true; }
            public function validate()
            {
                $this->validated = $this->comment;
                return new class {
                    public function isValid() { return true; }
                };
            }
            public function save() { $this->saved = $this->comment; }
        };
        $query = $this->getMockBuilder(stdClass::class)
            ->addMethods(['get', 'with', 'first'])->getMock();
        $query->expects($this->once())->method('get')->with('Comment', '7')->willReturnSelf();
        $query->method('with')->willReturnSelf();
        $query->method('first')->willReturn($comment);
        ee()->setMock('Model', $query);

        // Isolate the authorized branch and template assembly, retaining the real
        // all-mode HTML formatter so output cannot conceal a missing write-time clean.
        $typography = (new ReflectionClass(EE_Typography::class))->newInstanceWithoutConstructor();
        $typography->html_format = 'all';
        $variables = Mockery::mock('overload:ExpressionEngine\\Addons\\Comment\\Service\\Variables\\Comment');
        $variables->shouldReceive('getVariable')->with('editable')->andReturn(true);
        $variables->shouldReceive('getVariable')->with('comment')->andReturnUsing(function () use ($comment, $typography) {
            return $typography->format_html($comment->comment);
        });

        $result = (new Comment())->edit_comment();

        $this->assertSame($expected, $comment->validated, 'Validation must receive sanitized comment text.');
        $this->assertSame($expected, $comment->saved, 'Persistence must receive sanitized comment text.');
        $this->assertSame(['comment' => $expected], $result, 'All-mode success output must use sanitized text.');
    }
}
