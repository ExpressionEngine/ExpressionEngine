<?php

namespace ExpressionEngine\Tests\Service\License;

use ExpressionEngine\Service\License\DomainChallenge;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class DomainChallengeTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private const ACTION = 'https://ee.test/subsite/index.php?ACT=37';

    /**
     * @return void
     */
    public function testPingHasOnlyADigestAndItsOwnSiteCanReadAndClearTheProof(): void
    {
        $entries = [];
        $cache = Mockery::mock();
        $cache->shouldReceive('get')->andReturnUsing(function ($key) use (&$entries) {
            return $entries[$key] ?? false;
        });
        $cache->shouldReceive('save')->once()->with(Mockery::type('string'), Mockery::type('string'), 180)
            ->andReturnUsing(function ($key, $proof) use (&$entries) {
                $entries[$key] = $proof;
                return true;
            });
        $cache->shouldReceive('delete')->andReturnUsing(function ($key) use (&$entries) {
            $present = isset($entries[$key]);
            unset($entries[$key]);
            return $present;
        });
        $action = static function () {
            return self::ACTION;
        };
        $site = new DomainChallenge($cache, 1, $action);
        $other = new DomainChallenge($cache, 2, $action);

        $ping = $site->create();
        $proof = $site->read($ping['id']);

        $this->assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/', $proof);
        $this->assertSame(self::ACTION . '&challenge=' . $ping['id'], $ping['callback_url']);
        $this->assertSame(hash('sha256', $proof), $ping['proof_sha256']);
        $this->assertNotContains($proof, $ping);
        $this->assertSame($proof, $site->read($ping['id']));
        $this->assertNull($other->read($ping['id']));

        $site->forget($ping['id']);
        $site->forget($ping['id']);
        $this->assertNull($site->read($ping['id']));
    }

    /**
     * @return void
     */
    public function testMalformedIdsCannotAccessOrClearACacheNamespace(): void
    {
        $site = new DomainChallenge(Mockery::mock(), 1, static function () {
            return self::ACTION;
        });

        $this->assertNull($site->read('/'));
        $site->forget('/');
    }
}
