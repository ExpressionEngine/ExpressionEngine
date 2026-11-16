<?php

namespace ExpressionEngine\Tests\ExpressionEngine\Addons\Pro;

use ExpressionEngine\Addons\Pro\Service\Mfa\Mfa;
use ExpressionEngine\Dependency\OTPHP\InternalClock;
use ExpressionEngine\Dependency\OTPHP\TOTP;
use ExpressionEngine\Dependency\ParagonIE\ConstantTime\Base32;
use PHPUnit\Framework\TestCase;

class MfaVerificationTest extends TestCase
{
    /**
     * Load the add-on service used by the account forms.
     *
     * @return void
     */
    protected function setUp(): void
    {
        require_once PATH_ADDONS . 'pro/Service/Mfa/Mfa.php';
    }

    /**
     * Verify the current code for the member's saved setup.
     *
     * @return void
     */
    public function testCurrentMemberCodeIsAccepted()
    {
        $member = (object) ['unique_id' => 'member-fixture', 'backup_mfa_code' => md5('saved-setup')];
        $code = $this->currentCode($member);

        $this->assertTrue((new Mfa())->validateMemberOtp($code, $member));
    }

    /**
     * Keep a different code from confirming the member's setup.
     *
     * @return void
     */
    public function testDifferentMemberCodeIsNotAccepted()
    {
        $member = (object) ['unique_id' => 'member-fixture', 'backup_mfa_code' => md5('saved-setup')];
        $code = $this->currentCode($member);
        $differentCode = str_pad((string) (((int) $code + 1) % 1000000), 6, '0', STR_PAD_LEFT);

        $this->assertFalse((new Mfa())->validateMemberOtp($differentCode, $member));
    }

    /**
     * Require a six-digit string before checking the saved setup.
     *
     * @param mixed $code The supplied form value.
     * @return void
     * @dataProvider incompleteCodeProvider
     */
    public function testIncompleteCodeIsNotAccepted($code)
    {
        $member = (object) ['unique_id' => 'member-fixture', 'backup_mfa_code' => md5('saved-setup')];
        $service = $this->getMockBuilder(Mfa::class)->onlyMethods(['validateOtp'])->getMock();
        $service->expects($this->never())->method('validateOtp');

        $this->assertFalse($service->validateMemberOtp($code, $member));
    }

    /**
     * Provide ordinary missing and incomplete form values.
     *
     * @return array
     */
    public static function incompleteCodeProvider()
    {
        return [[null], [''], ['12345'], ['1234567'], ['abcdef'], [123456], [[]]];
    }

    /**
     * Preserve leading zeroes and use the saved member values.
     *
     * @return void
     */
    public function testCodeUsesTheSavedMemberSetup()
    {
        $member = (object) ['unique_id' => 'member-fixture', 'backup_mfa_code' => md5('saved-setup')];
        $service = $this->getMockBuilder(Mfa::class)->onlyMethods(['validateOtp'])->getMock();
        $service->expects($this->once())->method('validateOtp')
            ->with('012345', $member->unique_id . $member->backup_mfa_code)->willReturn(true);

        $this->assertTrue($service->validateMemberOtp('012345', $member));
    }

    /**
     * Generate the current code for a fixture member.
     *
     * @param object $member The fixture's saved MFA values.
     * @return string
     */
    private function currentCode($member)
    {
        return TOTP::createFromSecret(
            Base32::encodeUpper($member->unique_id . $member->backup_mfa_code),
            new InternalClock()
        )->now();
    }
}
