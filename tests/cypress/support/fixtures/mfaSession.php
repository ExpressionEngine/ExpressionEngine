<?php

// Run through Cypress's existing PHP/MySQL fixture environment, never HTTP.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/bootstrap.php';
require_once BASEPATH . 'libraries/Mfa_session.php';

function checkMfaSession($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

// Shadow the installed tables on this connection only. This exercises the real
// schema and MySQL driver without changing seeded members or login sessions.
$db = ee()->db;
$memberRow = $db->get('members')->row_array();
checkMfaSession(!empty($memberRow), 'The fixture requires a seeded member.');
foreach (['members', 'sessions', 'mfa_sessions'] as $table) {
    $name = $db->dbprefix($table);
    $schema = $db->query("SHOW CREATE TABLE {$name}")->row_array();
    $sql = preg_replace('/^CREATE TABLE /', 'CREATE TEMPORARY TABLE ', $schema['Create Table'], 1);
    checkMfaSession($db->query($sql) !== false, 'Could not isolate ' . $table . '.');
}

$member = (object) ['member_id' => 42, 'enable_mfa' => true,
    'password' => 'password-hash', 'unique_id' => 'member-secret', 'backup_mfa_code' => 'mfa-secret'];
$db->insert('members', array_merge($memberRow, (array) $member, ['enable_mfa' => 'y']));
$session = function ($id, $memberId = 42, $flag = 'show') use ($db) {
    $db->insert('sessions', ['session_id' => $id, 'member_id' => $memberId, 'mfa_flag' => $flag,
        'user_agent' => '', 'fingerprint' => '']);
};
$session('issuing-session', 42, 'skip');
$session('new-session');
ee()->config->set_item('enable_mfa', 'y');
ee()->config->set_item('mfa_session_length', 86400);
ee()->config->set_item('website_session_type', 'c');
ee()->config->set_item('site_id', 1);
ee()->localize->now = 2000000;

// Capture cookie I/O while leaving database operations on the real EE driver.
$input = new class {
    public $cookies = ['mfa_session_1' => false];
    public $sent = [];
    public function cookie($name)
    {
        return $this->cookies[$name] ?? false;
    }
    public function set_cookie($name, $value, $length)
    {
        $this->sent[] = [$name, $value, $length];
        $this->cookies[$name] = $value;
    }
    public function delete_cookie($name)
    {
        unset($this->cookies[$name]);
    }
};
ee()->remove('input');
ee()->set('input', $input);
$mfa = new Mfa_session();
$approve = function () use ($mfa, $member, $input) {
    checkMfaSession($mfa->create($member, 'issuing-session') === true, 'Approval was not issued.');
    return end($input->sent)[1];
};
$approvals = function () use ($db) {
    return $db->get('mfa_sessions')->result_array();
};
$flag = function ($id = 'new-session') use ($db) {
    return $db->get_where('sessions', ['session_id' => $id])->row('mfa_flag');
};
$reject = function ($request = null) use ($mfa, $flag) {
    checkMfaSession(($request ?: $mfa)->restore(42, 'new-session') === false, 'Invalid approval was accepted.');
    checkMfaSession($flag() === 'show', 'Rejected restore changed the session.');
};
$scenario = $argv[1] ?? '';
$invalidApprovals = [
    'deleted-approval' => null,
    'wrong-member' => ['member_id' => 43],
    'wrong-site' => ['site_id' => 2],
    'expires-now' => ['expires_at' => 2000000],
    'expired' => ['expires_at' => 1999999],
    'future-verification' => ['verified_at' => 2000001],
    'verification-boundary' => ['verified_at' => 1913600],
];
$changedCredentials = ['password' => 'changed-password', 'unique_id' => 'changed-uid',
    'backup_mfa_code' => 'changed-mfa', 'enable_mfa' => 'n'];

if (array_key_exists($scenario, $invalidApprovals)) {
    $approve();
    if ($invalidApprovals[$scenario] === null) {
        $db->where('member_id', 42)->delete('mfa_sessions');
    } else {
        $db->where('member_id', 42)->update('mfa_sessions', $invalidApprovals[$scenario]);
    }
    $reject();
} elseif (strpos($scenario, 'restore-after-') === 0) {
    $field = substr($scenario, strlen('restore-after-'));
    checkMfaSession(array_key_exists($field, $changedCredentials), 'Unknown credential field.');
    $approve();
    $restoring = new Mfa_session();
    // Stop between the member UPDATE and its afterUpdate revocation hook.
    $db->where('member_id', 42)->update('members', [$field => $changedCredentials[$field]]);
    checkMfaSession(count($approvals()) === 1, 'Approval must still exist during this gap.');
    $reject($restoring);
    $mfa->revoke_member(42);
    $reject($restoring);
    if ($field !== 'enable_mfa') {
        $currentMember = clone $member;
        $currentMember->$field = $changedCredentials[$field];
        $fresh = new Mfa_session();
        checkMfaSession($fresh->create($currentMember, 'issuing-session') === true, 'Fresh credentials could not issue approval.');
        checkMfaSession($fresh->restore(42, 'new-session') === true, 'Fresh credentials could not restore approval.');
    }
} elseif (array_key_exists($scenario, $changedCredentials)) {
    // The request still holds the previously verified credential snapshot.
    $db->where('member_id', 42)->update('members', [$scenario => $changedCredentials[$scenario]]);
    $mfa->revoke_member(42);
    checkMfaSession($mfa->create($member, 'issuing-session') === false, 'Stale credentials issued approval.');
    checkMfaSession($approvals() === [] && $input->sent === [], 'Rejected issuance left approval or a cookie.');
} else {
    switch ($scenario) {
        case 'issue':
            $token = $approve();
            $rows = $approvals();
            checkMfaSession(preg_match('/\A[a-f0-9]{64}\z/', $token) === 1, 'Invalid token shape.');
            checkMfaSession(count($rows) === 1, 'Expected one approval.');
            $row = $rows[0];
            checkMfaSession($row['token_hash'] === hash('sha256', $token), 'Raw or incorrect token stored.');
            checkMfaSession((int) $row['member_id'] === 42 && (int) $row['site_id'] === 1, 'Incorrect owner/site.');
            checkMfaSession((int) $row['verified_at'] === 2000000 && (int) $row['expires_at'] === 2086400, 'Incorrect lifetime.');
            checkMfaSession($row['issued_session_hash'] === hash('sha256', 'issuing-session'), 'Incorrect issuing session hash.');
            checkMfaSession(!in_array('issuing-session', $row, true) && !in_array($token, $row, true), 'Raw credential stored.');
            checkMfaSession($input->sent === [['mfa_session_1', $token, 86400]], 'Incorrect cookie lifetime.');
            checkMfaSession($mfa->restore(42, 'new-session') === true && $flag() === 'skip', 'Approval was not restored.');
            break;
        case 'overlapping-verifications':
        case 'overlapping-responses':
        case 'overlapping-expired-login-logout':
        case 'overlapping-recreated-login-logout':
            $old = $approve();
            $otherRequest = new Mfa_session();
            $firstRequest = new Mfa_session();
            $input->sent = [];
            if ($scenario !== 'overlapping-responses') {
                // Execute B after A's INSERT, before A's cleanup. All SQL still
                // runs through the real MySQL driver against temporary tables.
                $interleavedDb = new class($db) {
                    private $db;
                    public $beforeCleanup;
                    public function __construct($db) { $this->db = $db; }
                    public function __call($method, $arguments) { return $this->db->$method(...$arguments); }
                    public function query($sql, $binds = []) {
                        if ($this->beforeCleanup && preg_match('/^DELETE FROM .*mfa_sessions WHERE member_id/', $sql)) {
                            $callback = $this->beforeCleanup;
                            $this->beforeCleanup = null;
                            $callback();
                        }
                        return $this->db->query($sql, $binds);
                    }
                };
                $interleavedDb->beforeCleanup = function () use ($otherRequest, $member) {
                    checkMfaSession($otherRequest->create($member, 'issuing-session') === true, 'Second verification failed.');
                };
                ee()->remove('db');
                ee()->set('db', $interleavedDb);
                try {
                    checkMfaSession($firstRequest->create($member, 'issuing-session') === true, 'First verification failed.');
                    checkMfaSession($interleavedDb->beforeCleanup === null, 'Interleaving did not run.');
                } finally {
                    ee()->remove('db');
                    ee()->set('db', $db);
                }
            } else {
                // Both requests received the old cookie, even if their writes
                // finish sequentially. Either response may arrive last.
                checkMfaSession($firstRequest->create($member, 'issuing-session') === true, 'First verification failed.');
                checkMfaSession($otherRequest->create($member, 'issuing-session') === true, 'Second verification failed.');
            }
            checkMfaSession(count($approvals()) === 2, 'Verified requests deleted each other.');
            $tokens = array_column($input->sent, 1);
            checkMfaSession(count(array_unique($tokens)) === 2, 'Expected two fresh response tokens.');
            $input->cookies['mfa_session_1'] = $old;
            $reject(new Mfa_session());
            foreach ($tokens as $token) {
                $input->cookies['mfa_session_1'] = $token;
                checkMfaSession((new Mfa_session())->restore(42, 'new-session') === true, 'Response delivered an unusable cookie.');
                $db->where('session_id', 'new-session')->update('sessions', ['mfa_flag' => 'show']);
            }
            $db->where('session_id', 'issuing-session')->delete('sessions');
            if ($scenario === 'overlapping-expired-login-logout') {
                (new Mfa_session())->delete(0, '');
            } elseif ($scenario === 'overlapping-recreated-login-logout') {
                $db->where('session_id', 'new-session')->delete('sessions');
                (new Mfa_session())->delete(42, 'new-session');
                $session('new-session'); // A later login must reject both old tokens.
            } else {
                (new Mfa_session())->delete(42, 'issuing-session');
            }
            checkMfaSession($approvals() === [], 'Logout left an overlapping approval.');
            foreach ($tokens as $token) {
                $input->cookies['mfa_session_1'] = $token;
                $reject(new Mfa_session());
            }
            break;
        case 'multisite-cookie-isolation':
            $siteOneToken = $approve();
            $session('site-two-issuing', 42, 'skip');
            ee()->config->set_item('site_id', 2);
            $siteTwo = new Mfa_session();
            $reject($siteTwo);
            checkMfaSession($siteTwo->create($member, 'site-two-issuing') === true, 'Site two could not verify.');
            checkMfaSession(isset($input->cookies['mfa_session_1'], $input->cookies['mfa_session_2']), 'Sites overwrote the same cookie.');
            checkMfaSession($input->cookies['mfa_session_1'] === $siteOneToken, 'Site two replaced site one approval.');
            checkMfaSession($siteTwo->restore(42, 'new-session') === true, 'Site two approval rejected.');
            $db->where('session_id', 'new-session')->update('sessions', ['mfa_flag' => 'show']);
            ee()->config->set_item('site_id', 1);
            $siteOne = new Mfa_session();
            checkMfaSession($siteOne->restore(42, 'new-session') === true, 'Returning to site one lost approval.');
            $db->where('session_id', 'issuing-session')->delete('sessions');
            $siteOne->delete(42, 'issuing-session');
            checkMfaSession(!isset($input->cookies['mfa_session_1']) && isset($input->cookies['mfa_session_2']), 'Logout removed another site cookie.');
            $db->where('session_id', 'new-session')->update('sessions', ['mfa_flag' => 'show']);
            ee()->config->set_item('site_id', 2);
            checkMfaSession((new Mfa_session())->restore(42, 'new-session') === true, 'Site one logout revoked site two login.');
            break;
        case 'case-sensitive-credentials':
            $approve();
            $db->where('member_id', 42)->update('members', ['password' => strtoupper($member->password)]);
            $reject();
            checkMfaSession($mfa->create($member, 'issuing-session') === false, 'Case-insensitive credential check accepted stale verification.');
            break;
        case 'unbound-approval':
            $approve();
            $db->where('member_id', 42)->update('mfa_sessions', ['credential_hash' => '']);
            $reject();
            break;
        case 'unknown-token':
        case 'disabled-member':
        case 'deleted-member':
            $approve();
            if ($scenario === 'unknown-token') {
                $input->cookies['mfa_session_1'] = str_repeat('b', 64);
            } elseif ($scenario === 'disabled-member') {
                $db->where('member_id', 42)->update('members', ['enable_mfa' => 'n']);
            } else {
                $db->where('member_id', 42)->delete('members');
            }
            $reject(new Mfa_session());
            break;
        case 'session-isolation':
            $approve();
            $session('other-member', 43);
            $session('enrollment', 42, 'required');
            checkMfaSession($mfa->restore(43, 'other-member') === false, 'Another member reused approval.');
            checkMfaSession($mfa->restore(42, 'other-member') === false, 'Another member session was approved.');
            checkMfaSession($mfa->restore(42, 'enrollment') === false, 'Enrollment bypassed.');
            checkMfaSession($mfa->restore(42, 'missing-session') === false, 'Missing session approved.');
            checkMfaSession($flag('other-member') === 'show' && $flag('enrollment') === 'required', 'Session isolation failed.');
            break;
        case 'fixed-window':
            $approve();
            $before = $approvals();
            ee()->localize->now += 86399;
            checkMfaSession($mfa->restore(42, 'new-session') === true, 'Approval expired too soon.');
            checkMfaSession($approvals() === $before && count($input->sent) === 1, 'Restore extended approval.');
            ee()->localize->now++;
            $db->where('session_id', 'new-session')->update('sessions', ['mfa_flag' => 'show']);
            $reject();
            break;
        case 'longer-window':
        case 'shorter-window':
            $approve();
            $shorter = $scenario === 'shorter-window';
            ee()->config->set_item('mfa_session_length', $shorter ? '3600' : 604800);
            ee()->localize->now += $shorter ? 3600 : 86400;
            $reject();
            break;
        case 'expired-login-session':
            $approve();
            $db->where('session_id', 'issuing-session')->delete('sessions');
            checkMfaSession($mfa->restore(42, 'new-session') === true, 'Login expiration revoked MFA approval.');
            break;
        case 'rotation':
            $old = $approve();
            ee()->localize->now += 10;
            $new = $approve();
            checkMfaSession($old !== $new && count($approvals()) === 1, 'Token was not replaced.');
            $input->cookies['mfa_session_1'] = $old;
            $reject(new Mfa_session());
            $input->cookies['mfa_session_1'] = $new;
            checkMfaSession((new Mfa_session())->restore(42, 'new-session') === true, 'Replacement token rejected.');
            checkMfaSession((int) $approvals()[0]['expires_at'] === 2086410, 'Replacement lifetime incorrect.');
            break;
        case 'unverified-session':
            checkMfaSession($mfa->create($member, 'new-session') === false, 'Unverified session issued approval.');
            checkMfaSession($mfa->create($member, 'nonexistent') === false, 'Missing session issued approval.');
            checkMfaSession($approvals() === [] && $input->sent === [], 'Rejected issuance left approval or a cookie.');
            break;
        case 'revocation-before-restore':
            $approve();
            $restoring = new Mfa_session();
            $mfa->revoke_member(42);
            $reject($restoring);
            break;
        case 'verification-before-logout':
        case 'logout-before-verification':
            $approve();
            $logoutRequest = new Mfa_session();
            if ($scenario === 'verification-before-logout') {
                $approve();
            }
            $db->where('session_id', 'issuing-session')->delete('sessions');
            $logoutRequest->delete(42, 'issuing-session');
            if ($scenario === 'logout-before-verification') {
                checkMfaSession($mfa->create($member, 'issuing-session') === false, 'Logged-out session issued approval.');
            }
            checkMfaSession($approvals() === [] && empty($input->cookies['mfa_session_1']), 'Logout left approval or a cookie.');
            $reject();
            break;
        case 'browser-and-member-revocation':
            $first = $approve();
            $input->cookies['mfa_session_1'] = false;
            $session('other-browser', 42, 'skip');
            $other = new Mfa_session();
            checkMfaSession($other->create($member, 'other-browser') === true, 'Second browser failed.');
            $second = $input->cookies['mfa_session_1'];
            $input->cookies['mfa_session_1'] = $first;
            $db->where('session_id', 'issuing-session')->delete('sessions');
            (new Mfa_session())->delete(42, 'issuing-session');
            checkMfaSession(array_column($approvals(), 'token_hash') === [hash('sha256', $second)], 'Logout affected another browser.');
            // Member revocation applies across sites as well as browsers.
            $db->where('member_id', 42)->update('mfa_sessions', ['site_id' => 2]);
            $mfa->revoke_member(42);
            checkMfaSession($approvals() === [], 'Member revocation left approval.');
            break;
        case 'other-member-revocation':
            $approve();
            $mfa->revoke_member(43);
            checkMfaSession($mfa->restore(42, 'new-session') === true, 'Revocation affected another member.');
            break;
        default:
            throw new InvalidArgumentException('Unknown MFA scenario: ' . $scenario);
    }
}

// Temporary tables disappear when this CLI connection closes, including on failure.
echo json_encode(['scenario' => $scenario, 'passed' => true]);
