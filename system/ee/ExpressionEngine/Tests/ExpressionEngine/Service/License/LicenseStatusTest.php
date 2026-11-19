<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Tests\Service\License;

use ExpressionEngine\Service\Encrypt\Encrypt;
use ExpressionEngine\Service\License\LicenseStatus;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

require_once APPPATH . 'helpers/multibyte_helper.php';

class LicenseStatusTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    const DAY = 86400;
    const CRYPT_KEY = 'a-session-crypt-key';
    const LICENSE = 'ABCD1234EFGH5678';

    protected static $keys = [];

    protected $now;
    protected $cache;
    protected $curl;
    protected $encrypt;

    public static function setUpBeforeClass(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $private = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);

            if ($private === false) {
                self::$keys = [];

                return;
            }

            self::$keys[] = [
                'private' => $private,
                'public' => openssl_pkey_get_details($private)['key'],
            ];
        }
    }

    protected function setUp(): void
    {
        $this->now = 1700000000;

        $clock = function () {
            return $this->now;
        };

        $this->cache = new FakeFileCache($clock);
        $this->curl = new FakeCurl();
        $this->encrypt = new Encrypt('default-key');
    }

    public function testThereIsNoStatusBeforeAnyCheck()
    {
        $status = $this->status();

        $this->assertFalse($status->statusFor('pro'));
        $this->assertFalse($status->secondsSinceUpdate());
        $this->assertNull($status->getError());
    }

    public function testReportsEachAddonFromTheLatestResponse()
    {
        $status = $this->status();

        $this->assertTrue($status->store($this->response('success', ['pro' => 'valid', 'structure' => 'update_available', 'forum' => 'expired'])));

        $this->assertSame('valid', $status->statusFor('pro'));
        $this->assertSame('update_available', $status->statusFor('structure'));
        $this->assertSame('expired', $status->statusFor('forum'));
        $this->assertSame('na', $status->statusFor('not_in_the_response'));

        $this->now += 90;
        $this->assertSame(90, $status->secondsSinceUpdate());
    }

    public function testAProblemWithTheSiteLicenseOverridesAValidAddon()
    {
        $status = $this->status();
        $status->store($this->response('invalid_domain', ['pro' => 'valid', 'forum' => 'trial']));

        $this->assertSame('invalid_domain', $status->statusFor('pro'));
        $this->assertSame('trial', $status->statusFor('forum'));
    }

    public function testStatusIsSharedByEveryRequest()
    {
        $this->status()->store($this->response('success', ['pro' => 'valid']));

        $this->assertSame('valid', $this->status()->statusFor('pro'));
    }

    /**
     * @dataProvider unusableResponses
     */
    public function testAnUnusableResponseLeavesTheLastKnownStatusInPlace($response)
    {
        $status = $this->status();
        $status->store($this->response('success', ['pro' => 'valid']));

        $this->assertFalse($status->store($response));
        $this->assertSame('valid', $status->statusFor('pro'));
    }

    public function unusableResponses()
    {
        return [
            'not a response' => ['<html>Bad gateway</html>'],
            'empty' => [[]],
            'no outcome' => [['addons' => [['slug' => 'pro', 'status' => 'expired']]]],
            'blank outcome' => [['messageType' => '', 'addons' => [['slug' => 'pro', 'status' => 'expired']]]],
            'malformed add-on list' => [['messageType' => 'success', 'addons' => 'pro']],
        ];
    }

    public function testStatusesAreReducedToPlainIdentifiers()
    {
        $status = $this->status();
        $status->store([
            'messageType' => 'success',
            'addons' => [
                ['slug' => 'pro', 'status' => '"><script>alert(1)</script>', 'update' => '<b>1.0</b>'],
                ['slug' => 'forum', 'status' => ['valid']],
            ],
        ]);

        $this->assertMatchesRegularExpression('/^[a-z0-9_]+$/', $status->statusFor('pro'));
        $this->assertSame('na', $status->statusFor('forum'));
    }

    public function testTheLastKnownStatusStaysInForceForFourteenDaysOfMissedChecks()
    {
        $this->status()->store($this->response('success', ['pro' => 'valid', 'forum' => 'na']));

        // The first control panel page served after a check was missed
        $this->now += 2 * self::DAY;
        $this->servePage();
        $this->assertGreaterThan(60, $this->status()->secondsSinceUpdate(), 'The browser must still be asked to check');

        // The browser did not manage it, so the site tries and fails too
        $this->now += 700;
        $this->servePage();
        $this->assertCount(1, $this->curl->requests);

        $this->now += 6 * self::DAY;
        $this->servePage();
        $this->assertSame('valid', $this->status()->statusFor('pro'));
        $this->assertFalse($this->status()->verificationOverdue());

        $this->now += 2 * self::DAY;
        $this->servePage();
        $this->assertSame('valid', $this->status()->statusFor('pro'));
        $this->assertTrue($this->status()->verificationOverdue(), 'A week of missed checks is worth mentioning');

        $this->now += 7 * self::DAY;
        $this->servePage();
        $this->assertSame('unverified', $this->status()->statusFor('pro'));
        $this->assertSame('na', $this->status()->statusFor('forum'), 'Add-ons with nothing to verify are left alone');
        $this->assertSame('na', $this->status()->statusFor('never_listed'));
    }

    public function testASiteNobodyIsLoggedInToKeepsItsStatus()
    {
        $this->status()->store($this->response('success', ['pro' => 'valid']));

        // Checks only happen from the control panel, so none were missed
        $this->now += 90 * self::DAY;
        $this->assertSame('valid', $this->status()->statusFor('pro'));
        $this->assertFalse($this->status()->verificationOverdue());

        // Someone logs in again: the status holds while the check is made
        $this->servePage();
        $this->assertSame('valid', $this->status()->statusFor('pro'));
        $this->assertFalse($this->status()->verificationOverdue());

        $this->status()->store($this->response('success', ['pro' => 'valid']));

        $this->now += 90 * self::DAY;
        $this->assertSame('valid', $this->status()->statusFor('pro'));
    }

    public function testASuccessfulCheckBringsAnUnverifiedStatusBack()
    {
        $this->status()->store($this->response('success', ['pro' => 'valid']));

        $this->now += 2 * self::DAY;
        $this->missACheck();
        $this->now += 15 * self::DAY;

        $status = $this->status();
        $this->assertSame('unverified', $status->statusFor('pro'));

        $status->store($this->response('success', ['pro' => 'valid']));

        $this->assertSame('valid', $status->statusFor('pro'));
        $this->assertFalse($status->verificationOverdue());
    }

    public function testAShortVisitWithABlockedBrowserDoesNotStartTheClock()
    {
        $this->status()->store($this->response('success', ['pro' => 'valid']));

        // One page, the browser's check is blocked, and nobody comes back
        $this->now += 30 * self::DAY;
        $this->servePage();
        $this->assertSame([], $this->curl->requests, 'The site has not had its turn yet');

        $this->now += 30 * self::DAY;
        $this->assertSame('valid', $this->status()->statusFor('pro'));
        $this->assertFalse($this->status()->verificationOverdue());

        // On the next visit the site checks for itself before anything else
        $this->curl->body = json_encode($this->response('success', ['pro' => 'valid']));
        $this->servePage();

        $this->assertCount(1, $this->curl->requests);
        $this->assertSame('valid', $this->status()->statusFor('pro'));

        $this->now += 30 * self::DAY;
        $this->assertSame('valid', $this->status()->statusFor('pro'));
    }

    public function testTheClockStartsWhenTheSitesOwnCheckFails()
    {
        $this->status()->store($this->response('success', ['pro' => 'valid']));

        $this->now += 30 * self::DAY;
        $this->servePage();

        // The next visit, a month later: the site's own check fails
        $this->now += 30 * self::DAY;
        $this->servePage();
        $this->assertCount(1, $this->curl->requests);
        $this->assertSame('valid', $this->status()->statusFor('pro'));

        $this->now += 13 * self::DAY;
        $this->assertSame('valid', $this->status()->statusFor('pro'));

        $this->now += 2 * self::DAY;
        $this->assertSame('unverified', $this->status()->statusFor('pro'));
    }

    public function testASiteThatRecoversBetweenVisitsIsNotPaused()
    {
        $this->status()->store($this->response('success', ['pro' => 'valid']));

        $this->now += 2 * self::DAY;
        $this->missACheck();

        // Nobody logs in for three weeks. The license service is reachable
        // again by then, and the first page back finds that out.
        $this->now += 21 * self::DAY;
        $this->curl->body = json_encode($this->response('success', ['pro' => 'valid']));
        $this->servePage();

        $this->assertSame('valid', $this->status()->statusFor('pro'));
        $this->assertFalse($this->status()->verificationOverdue());
    }

    public function testAnAnswerThatArrivesWhileTheSitesOwnCheckIsFailingIsKept()
    {
        $this->status()->store($this->response('success', ['pro' => 'expired']));

        $this->now += 2 * self::DAY;
        $this->servePage();
        $this->now += 700;

        // The license was renewed, and the browser gets that answer in while
        // the site's own request is still waiting on an error
        $this->curl->code = '503';
        $this->curl->during = function () {
            $this->status()->store($this->response('success', ['pro' => 'valid']));
        };
        $this->servePage();

        $this->assertCount(1, $this->curl->requests);
        $this->assertSame('valid', $this->status()->statusFor('pro'));

        $this->now += 20 * self::DAY;
        $this->assertSame('valid', $this->status()->statusFor('pro'), 'A check that just succeeded must not start the clock');
        $this->assertFalse($this->status()->verificationOverdue());
    }

    public function testAKnownProblemWithALicenseIsStillReportedWhenChecksKeepFailing()
    {
        $this->status()->store($this->response('success', ['pro' => 'expired', 'forum' => 'invalid', 'wiki' => 'trial']));

        $this->now += 2 * self::DAY;
        $this->missACheck();
        $this->now += 15 * self::DAY;

        $status = $this->status();
        $this->assertTrue($status->verificationOverdue());
        $this->assertSame('expired', $status->statusFor('pro'), 'Someone who needs to renew should still be told so');
        $this->assertSame('invalid', $status->statusFor('forum'));
        $this->assertSame('unverified', $status->statusFor('wiki'));
    }

    public function testAStatusStoredByAnEarlierVersionIsTreatedLikeAnyOther()
    {
        // The stored format before checks carried a time of their own
        $data = [
            'appVer' => '7.5.28',
            'license' => self::LICENSE,
            'validLicense' => true,
            'licenseStatus' => 'success',
            'site_id' => 1,
            'site_url' => 'https://example.com/',
            'addons' => [
                'pro' => ['slug' => 'pro', 'version' => '2.0.0', 'status' => 'valid', 'update' => 'false'],
                'forum' => ['slug' => 'forum', 'version' => '1.0.0', 'status' => '"><i>', 'update' => 'false'],
            ],
        ];
        $data['sha'] = hash('sha256', json_encode($data));
        $encrypted = $this->encrypt->encode(json_encode($data), self::CRYPT_KEY);
        $this->cache->save(LicenseStatus::CACHE_KEY, $encrypted . '||s=' . hash('sha256', $encrypted), 0);

        // Upgrading does not count against the site
        $this->now += 40 * self::DAY;
        $this->missACheck();
        $this->assertSame('valid', $this->status()->statusFor('pro'));
        $this->assertMatchesRegularExpression('/^[a-z0-9_]+$/', $this->status()->statusFor('forum'));

        // Rewriting the stored status must not restart its clock
        $this->now += 13 * self::DAY;
        $this->servePage();
        $this->assertSame('valid', $this->status()->statusFor('pro'));

        $this->now += 2 * self::DAY;
        $this->assertSame('unverified', $this->status()->statusFor('pro'));
    }

    public function testAnAlteredStoredStatusIsNotTrusted()
    {
        $this->status()->store($this->response('success', ['pro' => 'valid']));
        $this->cache->items[LicenseStatus::CACHE_KEY]['data'] = 'x' . $this->cache->items[LicenseStatus::CACHE_KEY]['data'];

        $status = $this->status();

        $this->assertFalse($status->statusFor('pro'));
        $this->assertSame('license_error_file_broken', $status->getError());
    }

    public function testAStatusEncryptedForAnotherSiteIsNotTrusted()
    {
        $this->status()->store($this->response('success', ['pro' => 'valid']));

        $status = new LicenseStatus($this->cache, $this->encrypt, 'another-sites-key', $this->site(), null, []);

        $this->assertFalse($status->statusFor('pro'));
        $this->assertSame('license_error_file_broken', $status->getError());
    }

    public function testAReadOnlyStoredStatusIsNotTrusted()
    {
        $this->status()->store($this->response('success', ['pro' => 'valid']));
        $this->cache->writable = false;

        $status = $this->status();

        $this->assertFalse($status->statusFor('pro'));
        $this->assertSame('license_error_file_not_writable', $status->getError());
    }

    public function testASignedResponseFromAnyKnownKeyIsBelieved()
    {
        $status = $this->status($this->publicKeys());

        // What sits outside the signed payload is ignored
        $response = $this->response('expired', ['pro' => 'expired'])
            + $this->signed(2, ['messageType' => 'success', 'addons' => [['slug' => 'pro', 'status' => 'valid']]]);

        $this->assertTrue($status->store($response));
        $this->assertSame('valid', $status->statusFor('pro'));
    }

    /**
     * @dataProvider untrustworthySignedResponses
     */
    public function testASignedResponseThatCannotBeTrustedChangesNothing($build)
    {
        $this->requireKeys();

        $status = $this->status([self::$keys[0]['public']]);
        $status->store($this->response('expired', ['pro' => 'valid']));

        $this->assertFalse($status->store($build($this)));
        $this->assertSame('expired', $status->statusFor('pro'));
    }

    public function untrustworthySignedResponses()
    {
        $valid = ['messageType' => 'success', 'addons' => [['slug' => 'pro', 'status' => 'valid']]];

        return [
            'signed by an unknown key' => [function ($test) use ($valid) {
                return $test->signed(1, $valid);
            }],
            'payload changed after signing' => [function ($test) use ($valid) {
                $signed = $test->signed(0, ['messageType' => 'expired'] + $valid);
                $signed['payload'] = base64_encode(json_encode($valid + ['license' => self::LICENSE, 'issued_at' => 1700000000]));

                return $signed;
            }],
            'signature is not base64' => [function ($test) use ($valid) {
                return ['signature' => '***'] + $test->signed(0, $valid);
            }],
            'issued for another license' => [function ($test) use ($valid) {
                return $test->signed(0, ['license' => 'SOMEONEELSES0000'] + $valid);
            }],
            'does not say when it was issued' => [function ($test) use ($valid) {
                return $test->signed(0, ['issued_at' => null] + $valid);
            }],
        ];
    }

    public function testReplayingAnOlderSignedResponseDoesNotBuyMoreTime()
    {
        $status = $this->status($this->publicKeys());
        $old = $this->signed(0, [
            'messageType' => 'success',
            'issued_at' => $this->now - 13 * self::DAY,
            'addons' => [['slug' => 'pro', 'status' => 'valid']],
        ]);

        $this->assertTrue($status->store($old));
        $this->assertSame('valid', $status->statusFor('pro'));

        $this->now += 2 * self::DAY;
        $status = $this->status($this->publicKeys());
        $status->store($old);

        $this->assertSame('unverified', $status->statusFor('pro'));
    }

    public function testUnsignedResponsesAreStillAccepted()
    {
        $status = $this->status($this->publicKeys());

        $this->assertTrue($status->store($this->response('success', ['pro' => 'valid'])));
        $this->assertSame('valid', $status->statusFor('pro'));

        $this->assertTrue($status->store($this->signed(0, ['messageType' => 'expired', 'addons' => [['slug' => 'pro', 'status' => 'valid']]])));
        $this->assertSame('expired', $status->statusFor('pro'));
    }

    /**
     * @dataProvider sitesWithoutAUsableKey
     */
    public function testASiteWithoutAUsableKeyReadsTheUnsignedPartOfASignedResponse(array $public_keys)
    {
        $status = $this->status($public_keys);
        $response = $this->response('success', ['pro' => 'valid']) + $this->signed(0, ['messageType' => 'expired']);

        $this->assertTrue($status->store($response));
        $this->assertSame('valid', $status->statusFor('pro'));
    }

    public function sitesWithoutAUsableKey()
    {
        return [
            'no keys' => [[]],
            'a key in a format that cannot be read' => [['ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAACAQDb']],
            'a truncated key file' => [["-----BEGIN PUBLIC KEY-----\nMIICIjANBgkqhkiG9w0BAQEFAAOCAg8A\n"]],
        ];
    }

    public function testAKeyThatCannotBeReadDoesNotGetInTheWayOfOneThatCan()
    {
        $this->requireKeys();

        $status = $this->status(['ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAACAQDb', self::$keys[1]['public']]);

        $this->assertTrue($status->store($this->signed(1, ['messageType' => 'success', 'addons' => [['slug' => 'pro', 'status' => 'valid']]])));
        $this->assertSame('valid', $status->statusFor('pro'));

        $this->assertFalse($status->store($this->signed(0, ['messageType' => 'expired', 'addons' => [['slug' => 'pro', 'status' => 'expired']]])));
        $this->assertSame('valid', $status->statusFor('pro'));
    }

    public function testTheBrowserGetsTheFirstTryBeforeTheSiteAsksForItself()
    {
        $status = $this->status();
        $status->store($this->response('success', ['pro' => 'valid']));

        $this->now += 23 * 3600;
        $this->assertFalse($this->status()->backupCheckDue(), 'A recent check needs no backup');

        $this->now += 2 * 3600;
        $this->assertFalse($this->status()->backupCheckDue(), 'The page being served may still check from the browser');

        $this->now += 300;
        $this->assertFalse($this->status()->backupCheckDue());

        $this->now += 400;
        $this->assertTrue($this->status()->backupCheckDue());
    }

    public function testASiteThatHasNeverBeenCheckedAlsoWaitsForTheBrowser()
    {
        $this->assertFalse($this->status()->backupCheckDue());

        // The browser check succeeds in the meantime
        $this->status()->store($this->response('success', ['pro' => 'valid']));

        $this->now += 3600;
        $this->assertFalse($this->status()->backupCheckDue());
        $this->assertSame([], $this->curl->requests);
    }

    public function testASiteStillWaitingForItsFirstAnswerHasNoStatus()
    {
        // Neither the browser nor the site gets through
        $this->servePage();
        $this->now += 700;
        $this->servePage();
        $this->assertCount(1, $this->curl->requests);

        $status = $this->status();
        $this->assertFalse($status->statusFor('pro'), 'Keeping track of attempts is not an answer');
        $this->assertFalse($status->secondsSinceUpdate(), 'The browser should keep trying');
        $this->assertFalse($status->verificationOverdue());

        // The site waits before each further attempt of its own
        $this->now += 1800;
        $this->servePage();
        $this->assertCount(1, $this->curl->requests);

        $this->now += 1900;
        $this->servePage();
        $this->assertCount(2, $this->curl->requests);
    }

    public function testASiteThatCannotSaveAnythingDoesNotAskOnEveryPage()
    {
        $this->cache->writable = false;

        for ($page = 0; $page < 5; $page++) {
            $this->servePage();
            $this->now += 700;
        }

        $this->assertSame([], $this->curl->requests);
    }

    public function testTheSiteAsksTheLicenseServiceItselfAndStoresTheAnswer()
    {
        $this->status()->backupCheckDue();
        $this->now += 700;

        $status = $this->status();
        $this->assertTrue($status->backupCheckDue());

        $this->curl->body = json_encode($this->response('success', ['pro' => 'valid']));
        $request = ['license' => self::LICENSE, 'meta' => [['source' => 'server']]];

        $this->assertTrue($status->runBackupCheck($request));
        $this->assertSame('valid', $status->statusFor('pro'));
        $this->assertFalse($status->backupCheckDue());

        $this->assertCount(1, $this->curl->requests);
        $this->assertSame(LicenseStatus::CHECK_URL, $this->curl->requests[0]['url']);
        $this->assertSame($request, $this->curl->requests[0]['data']);
        $this->assertLessThanOrEqual(10, $this->curl->requests[0]['request']->config[CURLOPT_TIMEOUT], 'A page load must not hang on the license service');
    }

    /**
     * The site's own check sends what the browser would have sent.
     *
     * @return void
     */
    public function testTheSitesOwnCheckDescribesTheSiteToTheLicenseService()
    {
        $this->status()->store($this->response('success', ['pro' => 'valid']));
        $this->now += 20 * self::DAY;

        $db = Mockery::mock();
        $db->shouldReceive('select->get->result')->once()
            ->andReturn([(object) ['module_name' => 'Pro', 'module_version' => '1.2.3']]);
        $db->shouldReceive('engine')->once()->andReturn('mysql');
        $db->shouldReceive('version_number')->once()->andReturn('8.0');
        $access = $this->getMockBuilder(\stdClass::class)->addMethods(['requiresValidLicense'])->getMock();
        $access->expects($this->once())->method('requiresValidLicense')->willReturn(true);
        ee()->setMock('db', $db);
        ee()->setMock('pro:Access', $access);

        try {
            $this->assertFalse($this->status()->backupCheckDue(), 'The browser goes first');
            $this->now += 700;

            $status = $this->status();
            $this->assertTrue($status->backupCheckDue());
            $this->assertSame('valid', $status->statusFor('pro'));
            $this->curl->body = json_encode($this->response('success', ['pro' => 'update_available']));
            $this->assertTrue($status->runSiteCheck());
            $this->assertSame('update_available', $this->status()->statusFor('pro'));
            $this->assertFalse($this->status()->backupCheckDue());
            $this->assertSame(
                ['slug' => 'pro', 'name' => 'Pro', 'version' => '1.2.3'],
                $this->curl->requests[0]['data']['addons']['pro']
            );
            $this->assertSame('server', $this->curl->requests[0]['data']['meta'][0]['source']);
            $this->assertSame($this->site()['site_url'], $this->curl->requests[0]['data']['meta'][0]['site_url']);
        } finally {
            ee()->resetMocks();
        }
    }

    public function testAChangedLicenseKeyStartsAFreshCheck()
    {
        $this->status()->store($this->response('success', ['pro' => 'valid']));
        $this->assertFalse($this->status()->backupCheckDue());

        $status = $this->statusForSite(['license' => 'WXYZ1234ABCD5678']);

        $this->assertFalse($status->statusFor('pro'), 'An answer for the old key says nothing about the new one');
        $this->assertFalse($status->secondsSinceUpdate());
        $this->assertTrue($status->backupCheckDue(), 'A new key is checked without waiting');
        $this->assertFalse($status->runBackupCheck(['license' => 'WXYZ1234ABCD5678']));
        $this->assertFalse($status->backupCheckDue(), 'The new key has its own retry delay');
    }

    /**
     * One license key may serve several addresses: staging and production,
     * the sites of a multi-site install, or a site answering to more than one
     * hostname.
     *
     * @dataProvider otherAddressesForTheSameLicense
     */
    public function testTheSameLicenseAtAnotherAddressKeepsItsStatus(array $other_address)
    {
        $this->status()->store($this->response('success', ['pro' => 'valid']));

        $elsewhere = $this->statusForSite($other_address);

        $this->assertSame('valid', $elsewhere->statusFor('pro'));
        $this->assertSame(0, $elsewhere->secondsSinceUpdate());
        $this->assertFalse($elsewhere->backupCheckDue());
    }

    public function otherAddressesForTheSameLicense()
    {
        return [
            'another hostname' => [['site_url' => 'https://www.example.com/']],
            'another site ID' => [['site_id' => 2]],
            'both' => [['site_url' => 'https://staging.example.com/', 'site_id' => 3]],
        ];
    }

    public function testChangingAddressDoesNotBringForwardTheSitesOwnCheck()
    {
        $this->status()->store($this->response('success', ['pro' => 'valid']));
        $this->now += 2 * self::DAY;

        // Requests arriving under one hostname after another
        foreach (['https://example.com/', 'https://www.example.com/', 'https://203.0.113.9/', 'https://example.com/'] as $site_url) {
            $this->now += 1;
            $status = $this->statusForSite(['site_url' => $site_url]);

            if ($status->backupCheckDue()) {
                $status->runBackupCheck(['license' => self::LICENSE]);
            }

            $this->assertSame('valid', $status->statusFor('pro'));
        }

        $this->assertSame([], $this->curl->requests, 'The browser still has its turn, whatever the hostname');

        $this->now += 700;
        foreach (['https://example.com/', 'https://www.example.com/', 'https://203.0.113.9/'] as $site_url) {
            $status = $this->statusForSite(['site_url' => $site_url]);

            if ($status->backupCheckDue()) {
                $status->runBackupCheck(['license' => self::LICENSE]);
            }
        }

        $this->assertCount(1, $this->curl->requests, 'One attempt, then the retry delay applies to every hostname');
    }

    /**
     * Accept the existing license server's intentional Pro fail-open answer.
     *
     * @return void
     */
    public function testTheLegacyFailOpenResponseRestoresProAvailability()
    {
        $this->status()->store($this->response('success', ['pro' => 'valid']));
        $this->now += 2 * self::DAY;
        $this->missACheck();
        $this->now += 15 * self::DAY;
        $this->assertSame('unverified', $this->status()->statusFor('pro'));

        $this->curl->body = json_encode([
            'messageType' => 'success',
            'addons' => [['slug' => 'pro', 'name' => 'ExpressionEngine Pro', 'status' => 'valid', 'update' => null]],
        ]);

        $status = $this->status();
        $this->assertTrue($status->backupCheckDue());
        $this->assertTrue($status->runBackupCheck(['license' => self::LICENSE]));
        $this->assertSame('valid', $status->statusFor('pro'));
        $this->assertFalse($status->verificationOverdue());
    }

    /**
     * @dataProvider failedBackupChecks
     */
    public function testAFailedBackupCheckKeepsTheStatusAndWaitsBeforeTryingAgain($body, $code, $throws)
    {
        $this->status()->store($this->response('success', ['pro' => 'valid']));
        $this->now += 3 * self::DAY;
        $this->status()->backupCheckDue();
        $this->now += 700;

        $this->curl->body = $body;
        $this->curl->code = $code;
        $this->curl->throws = $throws;

        $status = $this->status();
        $this->assertTrue($status->backupCheckDue());
        $this->assertFalse($status->runBackupCheck(['license' => self::LICENSE]));

        $this->assertSame('valid', $status->statusFor('pro'));

        $this->now += 1800;
        $this->assertFalse($this->status()->backupCheckDue());

        $this->now += 1900;
        $this->assertTrue($this->status()->backupCheckDue());
    }

    public function failedBackupChecks()
    {
        return [
            'could not connect' => ['', '0', true],
            'server error' => ['{"messageType":"expired","addons":[{"slug":"pro","status":"expired"}]}', '503', false],
            'not JSON' => ['<html>Maintenance</html>', '200', false],
            'JSON without an outcome' => ['{"error":"busy"}', '200', false],
        ];
    }

    public function testASiteThatCannotMakeRequestsCarriesOn()
    {
        $this->status()->store($this->response('success', ['pro' => 'valid']));

        $status = new LicenseStatus($this->cache, $this->encrypt, self::CRYPT_KEY, $this->site(), null, [], function () {
            return $this->now;
        });

        $this->assertFalse($status->runBackupCheck(['license' => self::LICENSE]));
        $this->assertSame('valid', $status->statusFor('pro'));
    }

    /**
     * What happens when a control panel page is served: a missed check is
     * noticed and, when it is the site's turn, it asks the license service
     * itself. Those requests fail unless a test arranges an answer.
     */
    protected function servePage()
    {
        $status = $this->status();

        if ($status->backupCheckDue()) {
            $status->runBackupCheck(['license' => self::LICENSE]);
        }
    }

    /**
     * The browser's check is blocked and the site's own check then fails:
     * two control panel pages, far enough apart for the site to take its turn.
     */
    protected function missACheck()
    {
        $this->servePage();
        $this->now += 700;
        $this->servePage();
    }

    protected function statusForSite(array $changes)
    {
        return new LicenseStatus($this->cache, $this->encrypt, self::CRYPT_KEY, array_replace($this->site(), $changes), $this->curl, [], function () {
            return $this->now;
        });
    }

    protected function status(array $public_keys = [])
    {
        return new LicenseStatus($this->cache, $this->encrypt, self::CRYPT_KEY, $this->site(), $this->curl, $public_keys, function () {
            return $this->now;
        });
    }

    protected function site()
    {
        return [
            'license' => self::LICENSE,
            'site_id' => 1,
            'site_url' => 'https://example.com/',
            'app_version' => '7.5.29',
        ];
    }

    protected function response($outcome, array $addons = [])
    {
        $list = [];

        foreach ($addons as $slug => $status) {
            $list[] = ['slug' => $slug, 'status' => $status, 'update' => 'false'];
        }

        return ['messageType' => $outcome, 'addons' => $list];
    }

    protected function publicKeys()
    {
        $this->requireKeys();

        return array_column(self::$keys, 'public');
    }

    public function signed($key, array $payload)
    {
        $this->requireKeys();

        $payload += ['license' => self::LICENSE, 'issued_at' => $this->now];
        $payload = base64_encode(json_encode($payload));

        openssl_sign($payload, $signature, self::$keys[$key]['private'], OPENSSL_ALGO_SHA384);

        return ['payload' => $payload, 'signature' => base64_encode($signature)];
    }

    protected function requireKeys()
    {
        if (empty(self::$keys)) {
            $this->markTestSkipped('This PHP build cannot generate RSA keys');
        }
    }
}

class FakeFileCache
{
    public $items = [];
    public $writable = true;
    protected $clock;

    public function __construct($clock)
    {
        $this->clock = $clock;
    }

    public function get($key)
    {
        return isset($this->items[$key]) ? $this->items[$key]['data'] : false;
    }

    public function save($key, $data, $ttl = 60)
    {
        if (! $this->writable) {
            return false;
        }

        $this->items[$key] = ['data' => $data, 'ttl' => $ttl, 'mtime' => call_user_func($this->clock)];

        return true;
    }

    public function delete($key)
    {
        unset($this->items[$key]);

        return true;
    }

    public function get_metadata($key)
    {
        if (! isset($this->items[$key])) {
            return false;
        }

        return [
            'expire' => $this->items[$key]['mtime'] + $this->items[$key]['ttl'],
            'mtime' => $this->items[$key]['mtime'],
            'data' => $this->items[$key]['data'],
        ];
    }

    public function is_writable($key)
    {
        return $this->writable;
    }
}

class FakeCurl
{
    public $body = '';
    public $code = '200';
    public $throws = false;
    public $requests = [];

    // Called while a request is out, for things that happen in the meantime
    public $during = null;

    public function post($url, $data)
    {
        $request = new FakeCurlRequest($this);
        $this->requests[] = ['url' => $url, 'data' => $data, 'request' => $request];

        return $request;
    }
}

class FakeCurlRequest
{
    public $config = [];
    protected $curl;

    public function __construct(FakeCurl $curl)
    {
        $this->curl = $curl;
    }

    public function exec()
    {
        if ($this->curl->during) {
            call_user_func($this->curl->during);
        }

        if ($this->curl->throws) {
            throw new \Exception('Could not resolve host');
        }

        return $this->curl->body;
    }

    public function getHeader($key)
    {
        return ($key === 'http_code') ? $this->curl->code : false;
    }
}

// EOF
