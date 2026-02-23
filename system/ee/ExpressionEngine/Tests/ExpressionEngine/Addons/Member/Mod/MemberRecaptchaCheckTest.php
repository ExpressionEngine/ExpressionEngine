<?php

use PHPUnit\Framework\TestCase;

require_once PATH_ADDONS . 'member/mod.member.php';

class MemberRecaptchaCheckInputMock
{
    private $token;
    private $ipAddress;

    public function __construct($token, $ipAddress = '127.0.0.1')
    {
        $this->token = $token;
        $this->ipAddress = $ipAddress;
    }

    public function get_post($key)
    {
        return ($key === 'rec') ? $this->token : null;
    }

    public function ip_address()
    {
        return $this->ipAddress;
    }
}

class MemberRecaptchaCheckConfigMock
{
    private $items;

    public function __construct(array $items)
    {
        $this->items = $items;
    }

    public function item($key)
    {
        return $this->items[$key] ?? null;
    }
}

class MemberRecaptchaCheckOutputMock
{
    public $payloads = [];

    public function send_ajax_response($payload)
    {
        $this->payloads[] = $payload;

        return $payload;
    }
}

class MemberRecaptchaCheckLocalizeMock
{
    public $now;

    public function __construct($now)
    {
        $this->now = $now;
    }
}

class MemberRecaptchaCheckModelMock
{
    public $recentAttemptCount = 0;
    public $deleteCount = 0;
    public $savedCaptchas = [];

    public function __construct($recentAttemptCount = 0)
    {
        $this->recentAttemptCount = $recentAttemptCount;
    }

    public function get($name)
    {
        return new MemberRecaptchaCheckQueryMock($this);
    }

    public function make($name)
    {
        return new MemberRecaptchaCheckCaptchaEntityMock($this);
    }
}

class MemberRecaptchaCheckQueryMock
{
    private $model;

    public function __construct(MemberRecaptchaCheckModelMock $model)
    {
        $this->model = $model;
    }

    public function filter($key, $operator = null, $value = null)
    {
        return $this;
    }

    public function count()
    {
        return $this->model->recentAttemptCount;
    }

    public function delete()
    {
        $this->model->deleteCount++;

        return true;
    }
}

class MemberRecaptchaCheckCaptchaEntityMock
{
    public $date;
    public $ip_address;
    public $word;

    private $model;

    public function __construct(MemberRecaptchaCheckModelMock $model)
    {
        $this->model = $model;
    }

    public function save()
    {
        $this->model->savedCaptchas[] = [
            'date' => $this->date,
            'ip_address' => $this->ip_address,
            'word' => $this->word,
        ];

        return true;
    }
}

class MemberRecaptchaCheckTest extends TestCase
{
    private $output;
    private $model;
    private $now = 1710000000;
    private $ipAddress = '198.51.100.11';

    protected function setUp(): void
    {
        if (function_exists('ee') && method_exists(ee(), 'resetMocks')) {
            ee()->resetMocks();
        }

        $this->output = new MemberRecaptchaCheckOutputMock();
        ee()->setMock('output', $this->output);

        ee()->setMock('localize', new MemberRecaptchaCheckLocalizeMock($this->now));
        ee()->setMock('config', new MemberRecaptchaCheckConfigMock([
            'recaptcha_site_secret' => 'test-secret',
            'recaptcha_score_threshold' => 0.5,
        ]));
    }

    protected function tearDown(): void
    {
        if (function_exists('ee') && method_exists(ee(), 'resetMocks')) {
            ee()->resetMocks();
        }
    }

    public function testRecaptchaCheckRejectsMissingToken()
    {
        ee()->setMock('input', new MemberRecaptchaCheckInputMock('', $this->ipAddress));
        $this->model = new MemberRecaptchaCheckModelMock(0);
        ee()->setMock('Model', $this->model);

        $member = $this->buildMemberMock();
        $member->method('shouldRunRecaptchaCleanup')->willReturn(false);
        $member->method('generateCaptchaResponseCode')->willReturn('missing-token-code');
        $member->expects($this->never())->method('verifyRecaptchaToken');

        $result = $member->recaptcha_check();

        $this->assertEquals(['success' => false, 'code' => 'failed'], $result);
        $this->assertCount(1, $this->model->savedCaptchas);
        $this->assertSame('missing-token-code', $this->model->savedCaptchas[0]['word']);
    }

    public function testRecaptchaCheckRejectsWhenRateLimitExceeded()
    {
        ee()->setMock('input', new MemberRecaptchaCheckInputMock('token', $this->ipAddress));
        $this->model = new MemberRecaptchaCheckModelMock(25);
        ee()->setMock('Model', $this->model);

        $member = $this->buildMemberMock();
        $member->method('shouldRunRecaptchaCleanup')->willReturn(false);
        $member->method('generateCaptchaResponseCode')->willReturn('rate-limited-code');
        $member->expects($this->never())->method('verifyRecaptchaToken');

        $result = $member->recaptcha_check();

        $this->assertEquals(['success' => false, 'code' => 'failed'], $result);
        $this->assertCount(1, $this->model->savedCaptchas);
        $this->assertSame('rate-limited-code', $this->model->savedCaptchas[0]['word']);
    }

    public function testRecaptchaCheckRejectsWhenGoogleResponseIsInvalid()
    {
        ee()->setMock('input', new MemberRecaptchaCheckInputMock('token', $this->ipAddress));
        $this->model = new MemberRecaptchaCheckModelMock(0);
        ee()->setMock('Model', $this->model);

        $member = $this->buildMemberMock();
        $member->method('shouldRunRecaptchaCleanup')->willReturn(false);
        $member->method('generateCaptchaResponseCode')->willReturn('invalid-google-code');
        $member->method('verifyRecaptchaToken')->willReturn(null);

        $result = $member->recaptcha_check();

        $this->assertEquals(['success' => false, 'code' => 'failed'], $result);
        $this->assertCount(1, $this->model->savedCaptchas);
        $this->assertSame('invalid-google-code', $this->model->savedCaptchas[0]['word']);
    }

    public function testRecaptchaCheckRejectsWhenScoreIsBelowThreshold()
    {
        ee()->setMock('input', new MemberRecaptchaCheckInputMock('token', $this->ipAddress));
        $this->model = new MemberRecaptchaCheckModelMock(0);
        ee()->setMock('Model', $this->model);

        $member = $this->buildMemberMock();
        $member->method('shouldRunRecaptchaCleanup')->willReturn(false);
        $member->method('generateCaptchaResponseCode')->willReturn('low-score-code');
        $member->method('verifyRecaptchaToken')->willReturn([
            'success' => true,
            'score' => 0.1,
        ]);

        $result = $member->recaptcha_check();

        $this->assertEquals(['success' => false, 'code' => 'failed'], $result);
        $this->assertCount(1, $this->model->savedCaptchas);
        $this->assertSame('low-score-code', $this->model->savedCaptchas[0]['word']);
    }

    public function testRecaptchaCheckStoresCaptchaAndReturnsCodeOnSuccess()
    {
        ee()->setMock('input', new MemberRecaptchaCheckInputMock('token', $this->ipAddress));
        $this->model = new MemberRecaptchaCheckModelMock(0);
        ee()->setMock('Model', $this->model);

        $member = $this->buildMemberMock();
        $member->method('shouldRunRecaptchaCleanup')->willReturn(false);
        $member->method('verifyRecaptchaToken')->willReturn([
            'success' => true,
            'score' => 0.9,
        ]);
        $member->method('generateCaptchaResponseCode')->willReturn('fixed-captcha-code');

        $result = $member->recaptcha_check();

        $this->assertEquals(['success' => true, 'code' => 'fixed-captcha-code'], $result);
        $this->assertCount(1, $this->model->savedCaptchas);
        $this->assertSame($this->ipAddress, $this->model->savedCaptchas[0]['ip_address']);
        $this->assertSame($this->now, $this->model->savedCaptchas[0]['date']);
        $this->assertSame('fixed-captcha-code', $this->model->savedCaptchas[0]['word']);
    }

    public function testRecaptchaCheckRunsCleanupWhenCleanupFlagIsTrue()
    {
        ee()->setMock('input', new MemberRecaptchaCheckInputMock('token', $this->ipAddress));
        $this->model = new MemberRecaptchaCheckModelMock(0);
        ee()->setMock('Model', $this->model);

        $member = $this->buildMemberMock();
        $member->method('shouldRunRecaptchaCleanup')->willReturn(true);
        $member->method('generateCaptchaResponseCode')->willReturn('cleanup-test-code');
        $member->method('verifyRecaptchaToken')->willReturn([
            'success' => false,
            'score' => 0.0,
        ]);

        $member->recaptcha_check();

        $this->assertSame(1, $this->model->deleteCount);
        $this->assertCount(1, $this->model->savedCaptchas);
        $this->assertSame('cleanup-test-code', $this->model->savedCaptchas[0]['word']);
    }

    private function buildMemberMock()
    {
        $member = $this->getMockBuilder(Member::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'shouldRunRecaptchaCleanup',
                'verifyRecaptchaToken',
                'generateCaptchaResponseCode',
            ])
            ->getMock();

        return $member;
    }
}
