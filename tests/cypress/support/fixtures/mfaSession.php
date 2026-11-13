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
foreach (['members', 'sessions', 'mfa_sessions', 'security_hashes'] as $table) {
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
    public function ip_address() { return '127.0.0.1'; }
    public function user_agent() { return 'MFA regression fixture'; }
    public function cookie($name)
    {
        return $this->cookies[$name] ?? false;
    }
    public function set_cookie($name, $value = '', $length = 0)
    {
        $data = is_array($name) ? $name : ['name' => $name, 'value' => $value, 'expire' => $length];
        if (strpos($data['name'], 'mfa_session_') === 0) {
            checkMfaSession(($data['policy_name'] ?? null) === 'mfa_session', 'MFA creation did not specify its cookie policy.');
        }
        $this->sent[] = [$data['name'], $data['value'], $data['expire']];
        $this->cookies[$data['name']] = $data['value'];
    }
    public function delete_cookie($name)
    {
        $data = is_array($name) ? $name : ['name' => $name];
        if (strpos($data['name'], 'mfa_session_') === 0) {
            checkMfaSession(($data['policy_name'] ?? null) === 'mfa_session', 'MFA deletion did not specify its cookie policy.');
        }
        unset($this->cookies[$data['name']]);
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
// Exercise the actual model hydration as well as the production lifecycle.
$loadSession = function ($id) {
    $runtime = new class extends EE_Session {
        public function __construct() {}
        public function fetch_guest_data() {}
    };
    $runtime->sdata = ['session_id' => $id, 'fingerprint' => ''];
    $runtime->validation = 'c';
    $runtime->session_length = 60;
    checkMfaSession($runtime->fetch_session_data(), 'Session could not be loaded.');
    $runtime->userdata = $runtime->sdata;
    return $runtime;
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
        checkMfaSession($fresh->restore(42, 'new-session') !== false, 'Fresh credentials could not restore approval.');
    }
} elseif (array_key_exists($scenario, $changedCredentials)) {
    // The request still holds the previously verified credential snapshot.
    $db->where('member_id', 42)->update('members', [$scenario => $changedCredentials[$scenario]]);
    $mfa->revoke_member(42);
    checkMfaSession($mfa->create($member, 'issuing-session') === false, 'Stale credentials issued approval.');
    checkMfaSession($approvals() === [] && $input->sent === [], 'Rejected issuance left approval or a cookie.');
} else {
    switch ($scenario) {
        case 'session-query-budget':
            ee()->config->set_item('app_version', '7.5.29');
            $queryLog = ee('Database')->getLog();
            $queryLog->saveQueries();
            $db->where('session_id', 'issuing-session')->update('sessions', [
                'sess_start' => ee()->localize->now, 'last_activity' => ee()->localize->now
            ]);
            unset($db->data_cache['field_names']['sessions']);
            foreach (['skip', 'show'] as $mfaFlag) {
                $db->where('session_id', 'issuing-session')->update('sessions', ['mfa_flag' => $mfaFlag]);
                $start = count($queryLog->getQueries());
                $runtime = $loadSession('issuing-session');
                $runtime->update_session();
                $queries = array_column(array_slice($queryLog->getQueries(), $start), 0);
                checkMfaSession(count($queries) === 2, 'Ordinary activity added queries: ' . json_encode($queries));
                checkMfaSession(!preg_match('/mfa_sessions|SHOW COLUMNS/i', implode("\n", $queries)), 'Ordinary activity queried MFA approval or schema.');
            }
            break;
        case 'logout-query-budget':
            $loginId = hash('sha256', 'issuing-session');
            $db->where('session_id', 'issuing-session')->update('sessions', ['mfa_login_id' => $loginId]);
            $approve();
            $queryLog = ee('Database')->getLog();
            $queryLog->saveQueries();
            unset($db->data_cache['table_names']);
            $start = count($queryLog->getQueries());
            $mfa->delete(42, 'issuing-session', $loginId);
            $queries = array_column(array_slice($queryLog->getQueries(), $start), 0);
            checkMfaSession(count($queries) === 3, 'Logout made unnecessary schema queries: ' . json_encode($queries));
            checkMfaSession($db->get_where('sessions', ['session_id' => 'issuing-session'])->num_rows() === 0, 'Logout kept the login.');
            checkMfaSession($approvals() === [], 'Logout kept approval.');
            break;
        case 'logout-mixed-collations':
        case 'logout-mixed-collations-with-approval':
            foreach (['sessions' => 'utf8mb4_unicode_ci', 'security_hashes' => 'utf8mb4_general_ci',
                'mfa_sessions' => 'utf8mb4_bin'] as $table => $collation) {
                $db->query('ALTER TABLE ' . $db->dbprefix($table) . ' CONVERT TO CHARACTER SET utf8mb4 COLLATE ' . $collation);
            }
            $db->insert('security_hashes', ['date' => ee()->localize->now, 'session_id' => 'issuing-session', 'hash' => 'logout-hash']);
            $db->where('session_id', 'issuing-session')->update('sessions', [
                'sess_start' => ee()->localize->now, 'last_activity' => ee()->localize->now
            ]);
            $runtime = $loadSession('issuing-session');
            $runtime->update_session();
            $runtime->userdata = $runtime->sdata;
            if ($scenario === 'logout-mixed-collations-with-approval') {
                $approve();
            } else {
                ee()->config->set_item('enable_mfa', 'n');
                ee()->config->set_item('mfa_session_length', 0);
            }
            ee()->remove('remember');
            ee()->set('remember', new class {
                /**
                 * Keep remembered-password storage outside this MFA logout scenario.
                 *
                 * @return void
                 */
                public function delete() {}
            });
            ee()->remove('mfa_session');
            ee()->set('mfa_session', new Mfa_session());
            $runtime->destroy();
            checkMfaSession($db->get_where('sessions', ['session_id' => 'issuing-session'])->num_rows() === 0, 'Mixed collations prevented logout.');
            checkMfaSession($db->get_where('security_hashes', ['session_id' => 'issuing-session'])->num_rows() === 0, 'Logout left security hashes.');
            checkMfaSession($approvals() === [], 'Logout left MFA approval.');
            checkMfaSession($db->get_where('sessions', ['session_id' => 'new-session'])->num_rows() === 1, 'Logout removed a different login.');
            break;
        case 'issue':
            $queryLog = ee('Database')->getLog();
            $queryLog->saveQueries();
            $start = count($queryLog->getQueries());
            $token = $approve();
            checkMfaSession(count($queryLog->getQueries()) - $start === 2, 'First verification made unnecessary cleanup queries.');
            $rows = $approvals();
            checkMfaSession(preg_match('/\A[a-f0-9]{64}\z/', $token) === 1, 'Invalid token shape.');
            checkMfaSession(count($rows) === 1, 'Expected one approval.');
            $row = $rows[0];
            checkMfaSession($row['token_hash'] === hash('sha256', $token), 'Raw or incorrect token stored.');
            checkMfaSession((int) $row['member_id'] === 42 && (int) $row['site_id'] === 1, 'Incorrect owner/site.');
            checkMfaSession((int) $row['verified_at'] === 2000000 && (int) $row['expires_at'] === 2086400, 'Incorrect lifetime.');
            checkMfaSession($row['mfa_login_id'] === hash('sha256', 'issuing-session'), 'Incorrect issuing session hash.');
            checkMfaSession(!in_array('issuing-session', $row, true) && !in_array($token, $row, true), 'Raw credential stored.');
            checkMfaSession($input->sent === [['mfa_session_1', $token, 86400]], 'Incorrect cookie lifetime.');
            checkMfaSession($mfa->restore(42, 'new-session') === $row['mfa_login_id'] && $flag() === 'skip', 'Approval did not restore its stable login identity.');
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
                    public function delete($table, $where = '', $limit = null, $reset_data = true) {
                        if ($this->beforeCleanup && $table === 'mfa_sessions' && isset($where['member_id'])) {
                            $callback = $this->beforeCleanup;
                            $this->beforeCleanup = null;
                            $callback();
                        }
                        return $this->db->delete($table, $where, $limit, $reset_data);
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
                checkMfaSession((new Mfa_session())->restore(42, 'new-session') !== false, 'Response delivered an unusable cookie.');
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
        case 'session-schema-upgrade':
            ee()->config->set_item('app_version', '7.5.28');
            ee()->config->set_item('mfa_session_length', 0);
            // The updater adds the column before normal session operations resume.
            $db->query('ALTER TABLE ' . $db->dbprefix('sessions') . ' DROP COLUMN mfa_login_id');
            unset($db->data_cache['field_names']['sessions']);
            $existingSessions = $db->select('session_id, member_id, mfa_flag')
                ->order_by('session_id')->get('sessions')->result_array();

            require_once SYSPATH . 'ee/installer/libraries/Progress.php';
            require_once SYSPATH . 'ee/installer/updates/ud_7_05_29.php';
            $updater = new \ExpressionEngine\Updater\Version_7_5_29\Updater();
            checkMfaSession($updater->do_update() === true, 'Session column migration failed.');
            checkMfaSession($db->select('session_id, member_id, mfa_flag')
                ->order_by('session_id')->get('sessions')->result_array() === $existingSessions,
                'Migration changed existing logins or their MFA status.');

            $runtime = $loadSession('issuing-session');
            checkMfaSession($runtime->sdata['mfa_login_id'] === '', 'Migrated login has an unexpected identity.');
            $runtime->update_session();
            $loginId = hash('sha256', 'issuing-session');
            checkMfaSession($runtime->sdata['mfa_login_id'] === $loginId, 'Existing login was not associated on first activity.');
            checkMfaSession($flag($runtime->sdata['session_id']) === 'skip', 'Existing MFA verification was lost.');
            checkMfaSession($updater->do_update() === true, 'Repeated migration failed.');
            checkMfaSession($db->get_where('sessions', ['session_id' => $runtime->sdata['session_id']])
                ->row('mfa_login_id') === $loginId, 'Repeated migration changed the login identity.');

            $newId = $runtime->create_new_session(42);
            checkMfaSession($db->get_where('sessions', ['session_id' => $newId])->row('mfa_login_id') === hash('sha256', $newId),
                'New login has no stable MFA association.');
            checkMfaSession($flag($newId) === 'show', 'New login inherited an old MFA verification.');
            break;
        case 'restored-rotated-site-logout':
        case 'restored-rotated-host-logout':
        case 'rotated-shared-site-logout':
        case 'rotated-shared-host-logout':
        case 'logout-during-rotation-before-write':
        case 'logout-during-rotation-after-write':
        case 'restore-during-rotated-logout':
        case 'verification-after-rotation-logout':
            // Use the production rotation and logout methods. Only guest-profile
            // loading and Remember Me are outside this MFA persistence scenario.
            ee()->remove('remember');
            ee()->set('remember', new class {
                public function delete() {}
            });
            $db->where('session_id', 'issuing-session')->update('sessions', [
                'sess_start' => ee()->localize->now, 'last_activity' => ee()->localize->now
            ]);
            $initial = $loadSession('issuing-session');
            $initial->update_session();
            $token = $approve();
            $originalApproval = $approvals()[0];
            $restoring = new Mfa_session(); // Another request holds the valid cookie.
            $issuingId = 'issuing-session';
            if (strpos($scenario, 'restored-rotated-') === 0) {
                // The original session expired; a password/remembered login has
                // reused its MFA approval before the browser ID rotates again.
                ee()->remove('mfa_session');
                ee()->set('mfa_session', new Mfa_session());
                $restored = $loadSession('new-session');
                $restored->restore_mfa();
                checkMfaSession($restored->sdata['mfa_flag'] === 'skip', 'MFA was not restored.');
                $db->where('session_id', 'issuing-session')->delete('sessions');
                $db->where('session_id', 'new-session')->update('sessions', ['sess_start' => ee()->localize->now]);
                $issuingId = 'new-session';
            }
            // Same member and browser metadata, but an independent device login.
            $input->cookies = [];
            $session('other-browser', 42, 'skip');
            $other = new Mfa_session();
            checkMfaSession($other->create($member, 'other-browser'), 'Other device could not verify.');
            $otherToken = end($input->sent)[1];
            $input->cookies = []; // Another host cannot send the original host-only cookie.
            if (in_array($scenario, ['rotated-shared-site-logout', 'restored-rotated-site-logout'], true)) {
                $input->cookies['mfa_session_1'] = $token;
                ee()->config->set_item('site_id', 2); // This site reads its own cookie.
            }
            ee()->remove('mfa_session');
            ee()->set('mfa_session', new Mfa_session());
            $rotating = $loadSession($issuingId);
            $logout = $loadSession($issuingId);
            ee()->localize->now += 61;
            $concurrent = strpos($scenario, 'logout-during-rotation-') === 0;
            if ($concurrent) {
                $interleavedDb = new class($db, $logout, $scenario) {
                    private $db;
                    private $logout;
                    private $after;
                    public $ran = false;
                    public function __construct($db, $logout, $scenario) {
                        $this->db = $db;
                        $this->logout = $logout;
                        $this->after = $scenario === 'logout-during-rotation-after-write';
                    }
                    public function __call($method, $arguments) { return $this->db->$method(...$arguments); }
                    public function query($sql, $binds = []) {
                        if (!$this->ran && preg_match('/^UPDATE .*sessions`? SET /', $sql)) {
                            $this->ran = true;
                            if (!$this->after) { $this->logout->destroy(); }
                            $result = $this->db->query($sql, $binds);
                            if ($this->after) { $this->logout->destroy(); }
                            return $result;
                        }
                        return $this->db->query($sql, $binds);
                    }
                };
                ee()->remove('db');
                ee()->set('db', $interleavedDb);
                try {
                    $rotating->update_session();
                    checkMfaSession($interleavedDb->ran, 'Concurrent logout did not run.');
                } finally {
                    ee()->remove('db');
                    ee()->set('db', $db);
                }
            } else {
                $rotating->update_session();
                $rotatedId = $rotating->sdata['session_id'];
                checkMfaSession($rotatedId !== $issuingId, 'Session did not rotate.');
                if ($scenario === 'verification-after-rotation-logout') {
                    checkMfaSession((new Mfa_session())->create($member, $rotatedId), 'Rotated login could not verify.');
                    $input->cookies = [];
                    ee()->remove('mfa_session');
                    ee()->set('mfa_session', new Mfa_session());
                } else {
                    $row = $db->get_where('mfa_sessions', ['token_hash' => hash('sha256', $token)])->row_array();
                    checkMfaSession($row['expires_at'] === $originalApproval['expires_at'], 'Rotation extended MFA lifetime.');
                }
                // Multiple rotations must retain the same logout association.
                ee()->localize->now += 61;
                $rotating = $loadSession($rotatedId);
                $rotating->update_session();
                if ($scenario === 'restore-during-rotated-logout') {
                    $interleavedDb = new class($db, $restoring) {
                        private $db;
                        private $restoring;
                        public $ran = false;
                        public function __construct($db, $restoring) { $this->db = $db; $this->restoring = $restoring; }
                        public function __call($method, $arguments) { return $this->db->$method(...$arguments); }
                        public function where($key, $value = null, $escape = true, $binary = false) {
                            // Interleave before building the final cleanup query,
                            // while the shared builder has no pending conditions.
                            if (!$this->ran && $key === 'token_hash') {
                                $this->ran = true;
                                checkMfaSession($this->restoring->restore(42, 'new-session') === false, 'Concurrent restore reused approval after the login was deleted.');
                            }
                            return $this->db->where($key, $value, $escape, $binary);
                        }
                    };
                    ee()->remove('db');
                    ee()->set('db', $interleavedDb);
                    try {
                        $loadSession($rotating->sdata['session_id'])->destroy();
                        checkMfaSession($interleavedDb->ran, 'Concurrent restore did not run.');
                    } finally {
                        ee()->remove('db');
                        ee()->set('db', $db);
                    }
                } else {
                    $loadSession($rotating->sdata['session_id'])->destroy();
                }
            }
            checkMfaSession(array_column($approvals(), 'token_hash') === [hash('sha256', $otherToken)], 'Logout left approval for the shared login or revoked another device.');
            checkMfaSession($db->get_where('sessions', ['session_id' => $rotating->sdata['session_id']])->num_rows() === 0, 'Concurrent logout left the rotated login active.');
            checkMfaSession($db->get_where('sessions', ['session_id' => 'other-browser'])->num_rows() === 1, 'Logout removed another device login.');
            ee()->config->set_item('site_id', 1);
            $db->where('session_id', 'new-session')->delete('sessions');
            $session('new-session'); // A later password login must ask for MFA.
            $input->cookies = ['mfa_session_1' => $token];
            $reject(new Mfa_session());
            $input->cookies = ['mfa_session_1' => $otherToken];
            checkMfaSession((new Mfa_session())->restore(42, 'new-session'), 'Other device approval was revoked.');
            checkMfaSession((new Mfa_session())->create($member, $rotating->sdata['session_id']) === false, 'Logged-out rotated session issued a new approval.');
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
            checkMfaSession($siteTwo->restore(42, 'new-session') !== false, 'Site two approval rejected.');
            $db->where('session_id', 'new-session')->update('sessions', ['mfa_flag' => 'show']);
            ee()->config->set_item('site_id', 1);
            $siteOne = new Mfa_session();
            checkMfaSession($siteOne->restore(42, 'new-session') !== false, 'Returning to site one lost approval.');
            $db->where('session_id', 'issuing-session')->delete('sessions');
            $siteOne->delete(42, 'issuing-session');
            checkMfaSession(!isset($input->cookies['mfa_session_1']) && isset($input->cookies['mfa_session_2']), 'Logout removed another site cookie.');
            $db->where('session_id', 'new-session')->update('sessions', ['mfa_flag' => 'show']);
            ee()->config->set_item('site_id', 2);
            checkMfaSession((new Mfa_session())->restore(42, 'new-session') !== false, 'Site one logout revoked site two login.');
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
            checkMfaSession($mfa->restore(42, 'new-session') !== false, 'Approval expired too soon.');
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
            checkMfaSession($mfa->restore(42, 'new-session') !== false, 'Login expiration revoked MFA approval.');
            break;
        case 'rotation':
            $old = $approve();
            ee()->localize->now += 10;
            $new = $approve();
            checkMfaSession($old !== $new && count($approvals()) === 1, 'Token was not replaced.');
            $input->cookies['mfa_session_1'] = $old;
            $reject(new Mfa_session());
            $input->cookies['mfa_session_1'] = $new;
            checkMfaSession((new Mfa_session())->restore(42, 'new-session') !== false, 'Replacement token rejected.');
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
            checkMfaSession($mfa->restore(42, 'new-session') !== false, 'Revocation affected another member.');
            break;
        default:
            throw new InvalidArgumentException('Unknown MFA scenario: ' . $scenario);
    }
}

// Temporary tables disappear when this CLI connection closes, including on failure.
echo json_encode(['scenario' => $scenario, 'passed' => true]);
