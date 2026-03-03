<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Tests\Library\Security;

use ExpressionEngine\Library\Security\SignedToken;
use PHPUnit\Framework\TestCase;

class SignedTokenTest extends TestCase
{
    public function testIssueAndValidate()
    {
        $token = new SignedToken('test-key', ['purpose' => 'live_preview']);
        $issued = $token->issue(['member_id' => 1, 'channel_id' => 2]);

        $claims = $token->validate($issued);

        $this->assertIsArray($claims);
        $this->assertSame(1, $claims['member_id']);
        $this->assertSame(2, $claims['channel_id']);
        $this->assertSame('live_preview', $claims['purpose']);
    }

    public function testRejectsTamperedToken()
    {
        $token = new SignedToken('test-key');
        $issued = $token->issue(['member_id' => 1]);

        $parts = explode('.', $issued, 2);
        $tampered = $parts[0] . '.' . strrev($parts[1]);

        $this->assertNull($token->validate($tampered));
    }

    public function testRejectsExpiredToken()
    {
        $token = new SignedToken('test-key');
        $issued = $token->issue(['member_id' => 1], -120, time());

        $this->assertNull($token->validate($issued));
    }

    public function testRejectsPurposeMismatch()
    {
        $token = new SignedToken('test-key', ['purpose' => 'live_preview']);
        $issued = $token->issue(['member_id' => 1]);

        $validator = new SignedToken('test-key', ['purpose' => 'other']);

        $this->assertNull($validator->validate($issued));
    }
}
