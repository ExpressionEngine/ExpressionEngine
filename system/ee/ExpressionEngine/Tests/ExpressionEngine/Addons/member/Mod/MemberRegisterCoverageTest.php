<?php

require_once __DIR__ . '/MemberRegisterTestBase.php';
require_once rtrim(PATH_ADDONS, '/') . '/member/mod.member_settings.php';

class MemberEditProfileRouterFixture extends Member_settings
{
    public function __construct()
    {
    }

    public function _load_element($which)
    {
        throw new RuntimeException('profile fields reached');
    }
}

class MemberRegisterCoverageTest extends MemberRegisterTestBase
{
    private function seedRegisterPost(array $post): void
    {
        $_POST = $post;
        $this->input->postValues = $post;
        ee('Request')->posts = $post;
    }

    private function primeRegisterDefaults(): void
    {
        $this->setConfigItem('allow_member_registration', 'y');
        ee()->session->userdataValues['is_banned'] = false;
        ee()->blockedlist->blocked = 'n';
        ee()->blockedlist->allowed = 'y';
    }

    public function testDoFormQueryUsesProvidedBoardIdWhenNumeric()
    {
        $this->setInputGetPost(['board_id' => '7']);

        $query = $this->callPrivateMethod('_do_form_query');

        $this->assertSame('forum_boards', $this->db->getTable);
        $this->assertSame(7, (int) $this->db->lastWhereBoardId);
        $this->assertSame(7, (int) $query->row('board_id'));
    }

    public function testDoFormQueryFallsBackToBoardIdOne()
    {
        $this->setInputGetPost([]);

        $query = $this->callPrivateMethod('_do_form_query');

        $this->assertSame('forum_boards', $this->db->getTable);
        $this->assertSame(1, (int) $this->db->lastWhereBoardId);
        $this->assertSame(1, (int) $query->row('board_id'));
    }

    public function testActivateMemberWithForumModeAndMissingIdShowsInvalidUrlMessage()
    {
        $this->setInputGetPost([
            'r' => 'f',
            'board_id' => 9,
        ]);
        $this->output->throwOnMessage = true;

        try {
            $this->subject->activate_member();
            $this->fail('Expected MemberRegisterTestStop to halt flow after show_message');
        } catch (MemberRegisterTestStop $exception) {
            $this->assertSame('show_message', $exception->getMessage());
        }

        $this->assertSame(9, (int) $this->db->lastWhereBoardId);
        $this->assertSame('invalid_url', $this->output->messages[0]['content']);
    }

    public function testActivateMemberWithUnknownAuthCodeShowsProblemActivatingMessage()
    {
        $this->setInputGetPost(['id' => 'auth-123']);
        $this->output->throwOnMessage = true;
        $this->modelService->memberRecord = null;

        try {
            $this->subject->activate_member();
            $this->fail('Expected MemberRegisterTestStop to halt flow after show_message');
        } catch (MemberRegisterTestStop $exception) {
            $this->assertSame('show_message', $exception->getMessage());
        }

        $this->assertSame('mbr_problem_activating', $this->output->messages[0]['content']);
    }

    public function testActivateMemberWithoutForumModeAndMissingIdUsesSiteIndexPath()
    {
        $this->setInputGetPost(['r' => 'x']);
        $this->output->throwOnMessage = true;

        try {
            $this->subject->activate_member();
            $this->fail('Expected MemberRegisterTestStop to halt flow after show_message');
        } catch (MemberRegisterTestStop $exception) {
            $this->assertSame('show_message', $exception->getMessage());
        }

        $this->assertSame('invalid_url', $this->output->messages[0]['content']);
    }

    public function testActivateMemberRedirectsWhenActivationRedirectConfigured()
    {
        $this->setInputGetPost(['id' => 'auth-code']);
        $this->setConfigItem('activation_redirect', 'members/activated');
        $this->setConfigItem('default_primary_role', 5);

        $this->modelService->memberRecord = new class {
            public $member_id = 25;
            public $role_id = 4;
            public $pending_role_id = 9;
            public $email = 'member@example.com';
            public function getId()
            {
                return $this->member_id;
            }
        };
        $this->modelService->roleRecords[9] = (object) ['role_id' => 7, 'is_locked' => 'n'];
        $this->modelService->roleRecords[7] = (object) ['role_id' => 7, 'is_locked' => 'n'];

        $result = $this->subject->activate_member();

        $this->assertSame('REDIRECT:https://example.com/members/activated', $result);
        $this->assertCount(2, $this->db->queries);
    }

    public function testActivateMemberShowsSuccessMessageWhenNoRedirect()
    {
        $this->setInputGetPost(['id' => 'auth-code-2']);
        $this->setConfigItem('activation_redirect', '');
        $this->setConfigItem('activation_auto_login', 'y');
        $this->setConfigItem('default_primary_role', 5);

        $this->modelService->memberRecord = new class {
            public $member_id = 51;
            public $role_id = 5;
            public $pending_role_id = 0;
            public $email = 'member@example.com';
            public function getId()
            {
                return $this->member_id;
            }
        };
        $this->modelService->roleRecords[5] = (object) ['role_id' => 5, 'is_locked' => 'n'];

        $result = $this->subject->activate_member();

        $this->assertNull($result);
        $this->assertSame(1, ee()->stats->updates);
        $this->assertStringContainsString('mbr_activation_success', $this->output->messages[0]['content']);
        $this->assertSame(1, Auth_result::$sessionCalls);
    }

    public function testActivateMemberShowsErrorWhenPendingRoleMissing()
    {
        $this->setInputGetPost(['id' => 'auth-pending-missing']);
        $this->setConfigItem('default_primary_role', 5);
        $this->modelService->memberRecord = new class {
            public $member_id = 71;
            public $role_id = 4;
            public $pending_role_id = 22;
            public $email = 'member@example.com';
            public function getId()
            {
                return $this->member_id;
            }
        };
        $this->modelService->roleRecords[5] = (object) ['role_id' => 5, 'is_locked' => 'n'];
        $this->output->throwOnUserError = true;

        try {
            $this->subject->activate_member();
            $this->fail('Expected MemberRegisterTestStop when pending role does not exist');
        } catch (MemberRegisterTestStop $exception) {
            $this->assertSame('show_user_error', $exception->getMessage());
        }

        $this->assertNotEmpty($this->output->userErrors);
    }

    public function testActivateMemberShowsErrorWhenResolvedRoleIsLocked()
    {
        $this->setInputGetPost(['id' => 'auth-role-locked']);
        $this->setConfigItem('default_primary_role', 5);
        $this->modelService->memberRecord = new class {
            public $member_id = 72;
            public $role_id = 4;
            public $pending_role_id = 0;
            public $email = 'member@example.com';
            public function getId()
            {
                return $this->member_id;
            }
        };
        $this->modelService->roleRecords[5] = (object) ['role_id' => 5, 'is_locked' => 'y'];
        $this->output->throwOnUserError = true;

        try {
            $this->subject->activate_member();
            $this->fail('Expected MemberRegisterTestStop when role is locked');
        } catch (MemberRegisterTestStop $exception) {
            $this->assertSame('show_user_error', $exception->getMessage());
        }

        $this->assertNotEmpty($this->output->userErrors);
    }

    public function testActivateMemberShowsErrorWhenResolvedRoleDoesNotExist()
    {
        $this->setInputGetPost(['id' => 'auth-role-missing']);
        $this->setConfigItem('default_primary_role', 5);
        $this->modelService->memberRecord = new class {
            public $member_id = 74;
            public $role_id = 4;
            public $pending_role_id = 0;
            public $email = 'member@example.com';
            public function getId()
            {
                return $this->member_id;
            }
        };
        $this->output->throwOnUserError = true;

        try {
            $this->subject->activate_member();
            $this->fail('Expected MemberRegisterTestStop when resolved role does not exist');
        } catch (MemberRegisterTestStop $exception) {
            $this->assertSame('show_user_error', $exception->getMessage());
        }

        $this->assertNotEmpty($this->output->userErrors);
    }

    public function testActivateMemberReturnsEarlyWhenValidateHookEndsScript()
    {
        $this->setInputGetPost(['id' => 'auth-hook-stop']);
        $this->setConfigItem('default_primary_role', 5);
        $this->modelService->memberRecord = new class {
            public $member_id = 73;
            public $role_id = 5;
            public $pending_role_id = 0;
            public $email = 'member@example.com';
            public function getId()
            {
                return $this->member_id;
            }
        };
        $this->modelService->roleRecords[5] = (object) ['role_id' => 5, 'is_locked' => 'n'];
        ee()->setMock('extensions', new class {
            public $end_script = false;
            public function call($name, ...$args)
            {
                if ($name === 'member_register_validate_members') {
                    $this->end_script = true;
                }
                return null;
            }
        });

        $result = $this->subject->activate_member();

        $this->assertNull($result);
    }

    public function testRegistrationFormParsesWhenNoCustomFieldsExist()
    {
        ee()->TMPL->tagdata = '{if captcha}<captcha>{/if}{custom_fields}<div>field</div>{/custom_fields}';
        ee()->TMPL->setMap([
            'include_assets' => 'n',
            'error_handling' => '',
        ]);
        ee()->TMPL->form_class = 'register';
        ee('Captcha')->required = false;

        $result = $this->subject->registration_form();

        $this->assertStringContainsString('<form id="register_member_form"', $result);
        $this->assertStringNotContainsString('{custom_fields}', $result);
        $this->assertStringNotContainsString('<captcha>', $result);
        $this->assertStringContainsString('</form>', $result);
    }

    public function testRegistrationFormRestoresFrontendRouterAfterLoadingCpLibraries()
    {
        ee()->TMPL->tagdata = '<p>Registration</p>';
        ee()->TMPL->setMap([
            'include_assets' => 'n',
            'error_handling' => '',
        ]);

        $this->subject->registration_form();

        $this->assertSame('ee', $this->router->class);
        $this->assertSame(['cp', 'ee'], $this->router->history);
    }

    public function testEditProfileRestoresFrontendRouterAfterLoadingCpLibraries()
    {
        ee()->TMPL->tagdata = '<p>Profile fields</p>';
        $subject = (new ReflectionClass(MemberEditProfileRouterFixture::class))->newInstanceWithoutConstructor();

        try {
            $subject->edit_profile();
            $this->fail('Expected the fixture to stop after router initialization.');
        } catch (RuntimeException $exception) {
            $this->assertSame('profile fields reached', $exception->getMessage());
        }

        $this->assertSame('ee', $this->router->class);
        $this->assertSame(['cp', 'ee'], $this->router->history);
        $this->assertSame(['form'], $this->load->helpers);
        $this->assertSame(['cp', 'javascript'], $this->load->libraries);
    }

    public function testRegistrationFormAppendsAssetsWhenRequested()
    {
        ee()->TMPL->tagdata = '{form_assets}';
        ee()->TMPL->setMap([
            'include_assets' => 'y',
            'error_handling' => '',
        ]);
        ee()->TMPL->form_class = '';

        $result = $this->subject->registration_form();

        $this->assertStringContainsString('<script>head</script>', $result);
    }

    public function testRegistrationFormParsesCustomFieldChunk()
    {
        ee()->TMPL->tagdata = '{custom_fields}{required}<strong>required</strong>{/required}{field_name}:{field}{/custom_fields}';
        ee()->TMPL->setMap([
            'include_assets' => 'n',
            'error_handling' => '',
        ]);
        $this->db->memberFieldRows = [[
            'm_field_id' => 1,
            'm_field_label' => 'Favorite Color',
            'm_field_description' => '',
            'm_field_required' => 'y',
        ]];

        $this->modelService->memberFieldModels = [
            1 => new class {
                public $m_field_reg = 'y';
                public $m_field_id = 1;
                public $m_field_name = 'favorite_color';
                public function getField()
                {
                    return new class {
                        public function setName($name)
                        {
                        }
                        public function getShortName()
                        {
                            return 'favorite_color';
                        }
                        public function getForm()
                        {
                            return '<input name="m_field_id_1" />';
                        }
                    };
                }
            },
        ];

        $result = $this->subject->registration_form();

        $this->assertStringContainsString('Favorite Color', $result);
        $this->assertStringContainsString('<input name="m_field_id_1" />', $result);
        $this->assertStringContainsString('<strong>required</strong>', $result);
    }

    public function testRegistrationFormLegacyTemplateHandlesInlineErrorsAndForumHiddenField()
    {
        ee()->setMock('Config', new class {
            public function getFile()
            {
                return new class {
                    public function getBoolean($key)
                    {
                        return true;
                    }
                };
            }
        });
        ee()->TMPL->tagdata = '';
        ee()->TMPL->setMap([
            'include_assets' => 'y',
            'error_handling' => 'inline',
            'datepicker' => 'y',
        ]);
        ee()->TMPL->form_class = '';
        $this->subject->in_forum = true;
        $this->subject->board_id = 42;
        $this->subject->loadedElement = '{custom_fields}{required}<span>required</span>{/required}{if field_description}<em>{field_description}</em>{/if}{field_name}:{field}{/custom_fields}{if captcha}<div>CAPTCHA_BLOCK</div>{/if}';

        $this->db->memberFieldRows = [[
            'm_field_id' => 1,
            'm_field_label' => 'Nickname',
            'm_field_description' => '',
            'm_field_required' => 'n',
        ]];
        $this->modelService->memberFieldModels = [
            1 => new class {
                public $m_field_reg = 'y';
                public $m_field_id = 1;
                public $m_field_name = 'nickname';
                public function getField()
                {
                    return new class {
                        public function setName($name)
                        {
                        }
                        public function getShortName()
                        {
                            return 'nickname';
                        }
                        public function getForm()
                        {
                            return '<input name="m_field_id_1" value="x" />';
                        }
                    };
                }
            },
        ];
        ee()->setMock('Captcha', new class {
            public function shouldRequireCaptcha()
            {
                return true;
            }
            public function create()
            {
                return '<captcha />';
            }
        });
        $this->setConfigItem('use_recaptcha', 'y');
        ee()->session->flashdataValues['field_values'] = ['username' => 'legacy-user'];

        $result = $this->subject->registration_form();

        $this->assertStringContainsString('<input name="m_field_id_1" value="x" />', $result);
        $this->assertStringContainsString('<captcha />', $result);
        $this->assertStringContainsString('<script>head</script>', $result);
        $this->assertSame('yes', ee()->TMPL->tagparams['inline_errors']);
    }

    public function testRegistrationFormLegacyTemplateHandlesNonRecaptchaCaptchaAndFieldToken()
    {
        ee()->setMock('Config', new class {
            public function getFile()
            {
                return new class {
                    public function getBoolean($key)
                    {
                        return true;
                    }
                };
            }
        });
        ee()->TMPL->tagdata = '';
        ee()->TMPL->setMap([
            'include_assets' => 'n',
            'error_handling' => '',
        ]);
        $this->subject->loadedElement = '{custom_fields}{required}<span>required</span>{/required}{if field_description}<em>{field_description}</em>{/if}{field_name}:{field}{/custom_fields}{field:nickname}{if captcha}<div>{captcha}{captcha_word}</div>{/if}';

        $this->db->memberFieldRows = [[
            'm_field_id' => 1,
            'm_field_label' => 'Nickname',
            'm_field_description' => 'Short profile name',
            'm_field_required' => 'y',
        ]];
        $this->modelService->memberFieldModels = [
            1 => new class {
                public $m_field_reg = 'y';
                public $m_field_id = 1;
                public $m_field_name = 'nickname';
                public function getField()
                {
                    return new class {
                        public function setName($name)
                        {
                        }
                        public function getShortName()
                        {
                            return 'nickname';
                        }
                        public function getForm()
                        {
                            return '<input name="m_field_id_1" value="nick" />';
                        }
                    };
                }
            },
        ];
        ee()->setMock('Captcha', new class {
            public function shouldRequireCaptcha()
            {
                return true;
            }
            public function create()
            {
                return '<captcha />';
            }
        });
        $this->setConfigItem('use_recaptcha', 'n');

        $result = $this->subject->registration_form();

        $this->assertStringContainsString('Short profile name', $result);
        $this->assertStringContainsString('<input name="m_field_id_1" value="nick" />', $result);
        $this->assertStringNotContainsString('{captcha_word}', $result);
    }

    public function testRegistrationFormShowsMessageWhenRegistrationDisabled()
    {
        $this->setConfigItem('allow_member_registration', 'n');
        ee()->TMPL->tagdata = 'x';
        $this->output->throwOnMessage = true;

        try {
            $this->subject->registration_form();
            $this->fail('Expected MemberRegisterTestStop when registration is disabled');
        } catch (MemberRegisterTestStop $exception) {
            $this->assertSame('show_message', $exception->getMessage());
        }

        $this->assertSame('mbr_registration_not_allowed', $this->output->messages[0]['content']);
    }

    public function testRegisterMemberReturnsFalseWhenRegistrationDisabled()
    {
        $this->setConfigItem('allow_member_registration', 'n');

        $result = $this->subject->register_member();

        $this->assertFalse($result);
    }

    public function testRegisterMemberReturnsBannedUserError()
    {
        $this->setConfigItem('allow_member_registration', 'y');
        ee()->session->userdataValues['is_banned'] = true;

        $result = $this->subject->register_member();

        $this->assertSame('SHOW_USER_ERROR', $result);
        $this->assertCount(1, $this->output->userErrors);
    }

    public function testRegisterMemberShowsFormErrorWhenRoleIsLocked()
    {
        $this->setConfigItem('allow_member_registration', 'y');
        $this->setConfigItem('default_primary_role', 5);
        ee()->session->userdataValues['is_banned'] = false;
        ee()->blockedlist->blocked = 'n';
        ee()->blockedlist->allowed = 'y';
        $this->input->postValues = [
            'username' => 'role-lock-user',
            'password' => 'secret-pass',
            'screen_name' => 'Role Lock',
            'email' => 'role-lock@example.com',
        ];
        $this->modelService->roleRecords[5] = (object) ['role_id' => 5, 'is_locked' => 'y'];
        $this->output->throwOnFormError = true;

        try {
            $this->subject->register_member();
            $this->fail('Expected MemberRegisterTestStop when role is locked');
        } catch (MemberRegisterTestStop $exception) {
            $this->assertSame('show_form_error', $exception->getMessage());
        }

        $this->assertNotEmpty($this->output->formErrors);
    }

    public function testRegisterMemberReturnsBlockedListError()
    {
        $this->setConfigItem('allow_member_registration', 'y');
        ee()->session->userdataValues['is_banned'] = false;
        ee()->blockedlist->blocked = 'y';
        ee()->blockedlist->allowed = 'n';

        $result = $this->subject->register_member();

        $this->assertSame('SHOW_USER_ERROR', $result);
        $this->assertCount(1, $this->output->userErrors);
    }

    public function testRegisterMemberStopsWhenExtensionEndsScriptEarly()
    {
        $this->setConfigItem('allow_member_registration', 'y');
        ee()->session->userdataValues['is_banned'] = false;
        ee()->blockedlist->blocked = 'n';
        ee()->blockedlist->allowed = 'y';
        ee()->setMock('extensions', new class {
            public $end_script = false;
            public function call($name, ...$args)
            {
                if ($name === 'member_member_register_start') {
                    $this->end_script = true;
                }
                return null;
            }
        });

        $result = $this->subject->register_member();

        $this->assertNull($result);
    }

    public function testRegisterMemberManualActivationHappyPathRedirects()
    {
        $this->primeRegisterDefaults();
        $this->setConfigItem('req_mbr_activation', 'manual');
        $this->setConfigItem('default_primary_role', 5);
        $this->seedRegisterPost([
            'username' => 'jane',
            'password' => 'secret-pass',
            'screen_name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);
        $this->input->getPost = [
            'FROM' => false,
        ];

        $this->modelService->roleRecords[5] = (object) ['role_id' => 5, 'is_locked' => 'n'];

        $result = $this->subject->register_member();

        $this->assertSame('REDIRECT:/return-success', $result);
    }

    public function testRegisterMemberReturnsFormErrorAliasesWhenValidatorFails()
    {
        $this->primeRegisterDefaults();
        $this->setConfigItem('default_primary_role', 5);
        $this->setConfigItem('registration_auto_login', false);
        $this->seedRegisterPost([
            'username' => 'failing-user',
            'password' => 'secret-pass',
            'password_confirm' => 'secret-pass',
            'email' => 'failing@example.com',
            'email_confirm' => 'mismatch@example.com',
            'screen_name' => 'Failing User',
            'm_field_id_1' => 'custom-value',
            'bio' => 'Bio text',
            'ACT' => '1',
            'RET' => '/',
        ]);
        $this->modelService->roleRecords[5] = (object) ['role_id' => 5, 'is_locked' => 'n'];
        $this->db->memberFieldRows = [[
            'm_field_id' => 1,
            'm_field_name' => 'custom_name',
            'm_field_label' => 'Custom Name',
            'm_field_type' => 'text',
            'm_field_list_items' => '',
            'm_field_required' => 'y',
        ]];
        $this->modelService->memberFieldModels = [
            1 => new class {
                public $m_field_id = 1;
                public function getField()
                {
                    return new class {
                        public function validate($value)
                        {
                            return true;
                        }
                    };
                }
            },
        ];
        $this->modelService->memberDisplayFields = [
            new class {
                public function getName()
                {
                    return 'email_confirm';
                }
                public function getShortName()
                {
                    return 'email_confirm';
                }
                public function getLabel()
                {
                    return 'Email Confirm';
                }
            },
        ];
        ee()->Validation->forceFailures = [
            'email_confirm' => ['matches[email]'],
        ];

        $result = $this->subject->register_member();

        $this->assertSame('SHOW_FORM_ERROR_ALIASES', $result);
        $this->assertArrayHasKey('field_values', ee()->session->flashdataValues);
        $this->assertArrayNotHasKey('password', ee()->session->flashdataValues['field_values']);
        $this->assertSame(['matches[email]'], $this->modelService->memberValidationAdded['email_confirm']);
    }

    public function testRegisterMemberShowsFormErrorWhenProtectedPrimaryRoleMissing()
    {
        $this->primeRegisterDefaults();
        $this->setConfigItem('default_primary_role', 5);
        $this->seedRegisterPost([
            'username' => 'role-missing',
            'password' => 'secret-pass',
            'screen_name' => 'Role Missing',
            'email' => 'role-missing@example.com',
        ]);
        $this->functions->protected = ['primary_role' => '999'];
        $this->modelService->roleRecords[5] = (object) ['role_id' => 5, 'is_locked' => 'n'];
        $this->output->throwOnFormError = true;

        try {
            $this->subject->register_member();
            $this->fail('Expected MemberRegisterTestStop when protected role is missing');
        } catch (MemberRegisterTestStop $exception) {
            $this->assertSame('show_form_error', $exception->getMessage());
        }

        $this->assertNotEmpty($this->output->formErrors);
    }

    public function testRegisterMemberShowsFormErrorWhenDefaultRoleDoesNotExist()
    {
        $this->primeRegisterDefaults();
        $this->setConfigItem('default_primary_role', 999);
        $this->seedRegisterPost([
            'username' => 'missing-default-role',
            'password' => 'secret-pass',
            'screen_name' => 'Missing Role',
            'email' => 'missing-role@example.com',
        ]);
        $this->output->throwOnFormError = true;

        try {
            $this->subject->register_member();
            $this->fail('Expected MemberRegisterTestStop when default role does not exist');
        } catch (MemberRegisterTestStop $exception) {
            $this->assertSame('show_form_error', $exception->getMessage());
        }

        $this->assertNotEmpty($this->output->formErrors);
    }

    public function testRegisterMemberEmailActivationForumPathSendsNotifications()
    {
        $this->primeRegisterDefaults();
        $this->setConfigItem('default_primary_role', 5);
        $this->setConfigItem('req_mbr_activation', 'email');
        $this->setConfigItem('new_member_notification', 'y');
        $this->setConfigItem('mbr_notification_emails', 'one@example.com,,two@example.com');
        $this->setConfigItem('require_terms_of_service', 'y');
        $this->setConfigItem('use_recaptcha', 'y');
        $this->setConfigItem('cp_url', 'https://example.com/admin');
        $this->functions->protected = ['primary_role' => '7'];
        $this->seedRegisterPost([
            'username' => 'email-user',
            'password' => 'secret-pass',
            'password_confirm' => 'secret-pass',
            'email' => 'email-user@example.com',
            'email_confirm' => 'email-user@example.com',
            'screen_name' => 'Email User',
            'captcha' => 'good-captcha',
            'accept_terms' => 'y',
            'bio' => 'Bio',
            'language' => 'english',
            'server_timezone' => 'UTC',
            'date_format' => 'us',
            'time_format' => '24',
            'include_seconds' => 'n',
            'm_field_id_1' => 'custom value',
        ]);
        $this->input->getPost = [
            'FROM' => 'forum',
            'board_id' => '9',
        ];
        $this->modelService->roleRecords[7] = (object) ['role_id' => 7, 'is_locked' => 'n'];
        $this->db->captchaCountResult = 1;
        $this->db->memberFieldRows = [[
            'm_field_id' => 1,
            'm_field_name' => 'custom_name',
            'm_field_label' => 'Custom Name',
            'm_field_type' => 'text',
            'm_field_list_items' => '',
            'm_field_required' => 'y',
        ]];
        $this->modelService->memberFieldModels = [
            1 => new class {
                public $m_field_id = 1;
                public function getField()
                {
                    return new class {
                        public function validate($value)
                        {
                            return true;
                        }
                    };
                }
            },
        ];
        ee()->setMock('Captcha', new class {
            public function shouldRequireCaptcha()
            {
                return true;
            }
            public function create()
            {
                return '<captcha />';
            }
        });

        $result = $this->subject->register_member();

        $emailLoads = array_values(array_filter($this->load->libraries, function ($lib) {
            return $lib === 'email';
        }));

        $this->assertSame('REDIRECT:/return-success', $result);
        $this->assertCount(2, $emailLoads);
        $this->assertNotEmpty($this->db->queries);
    }

    public function testRegisterMemberWithEmptyDefaultRoleUsesPendingAndAutoLogin()
    {
        $this->primeRegisterDefaults();
        $this->setConfigItem('default_primary_role', '');
        $this->setConfigItem('req_mbr_activation', 'none');
        $this->setConfigItem('registration_auto_login', false);
        $this->seedRegisterPost([
            'username' => 'autologin-user',
            'password' => 'secret-pass',
            'screen_name' => 'Auto Login',
            'email' => 'autologin@example.com',
        ]);
        $this->modelService->roleRecords[''] = (object) ['role_id' => 8, 'is_locked' => 'n'];
        Auth_result::reset();

        $result = $this->subject->register_member();

        $this->assertSame('REDIRECT:/return-success', $result);
        $this->assertSame(1, ee()->stats->updates);
        $this->assertSame(1, Auth_result::$sessionCalls);
    }

    public function testRegisterMemberReturnsEarlyWhenErrorHookEndsScript()
    {
        $this->primeRegisterDefaults();
        $this->setConfigItem('default_primary_role', 5);
        $this->seedRegisterPost([
            'username' => 'hook-stop-user',
            'password' => 'secret-pass',
            'screen_name' => 'Hook Stop',
            'email' => 'hook-stop@example.com',
        ]);
        $this->modelService->roleRecords[5] = (object) ['role_id' => 5, 'is_locked' => 'n'];
        ee()->setMock('extensions', new class {
            public $end_script = false;
            public function call($name, ...$args)
            {
                if ($name === 'member_member_register_errors') {
                    $this->end_script = true;
                }
                return null;
            }
        });

        $result = $this->subject->register_member();

        $this->assertNull($result);
    }

    public function testRegisterMemberReturnsEarlyWhenPostCreateHookEndsScript()
    {
        $this->primeRegisterDefaults();
        $this->setConfigItem('default_primary_role', 5);
        $this->setConfigItem('req_mbr_activation', 'manual');
        $this->seedRegisterPost([
            'username' => 'post-hook-stop-user',
            'password' => 'secret-pass',
            'screen_name' => 'Post Hook Stop',
            'email' => 'post-hook-stop@example.com',
        ]);
        $this->modelService->roleRecords[5] = (object) ['role_id' => 5, 'is_locked' => 'n'];
        ee()->setMock('extensions', new class {
            public $end_script = false;
            public function call($name, ...$args)
            {
                if ($name === 'member_member_register') {
                    $this->end_script = true;
                }
                return null;
            }
        });

        $result = $this->subject->register_member();

        $this->assertNull($result);
    }

    public function testRegisterMemberReturnsCaptchaErrorWhenCaptchaWordInvalid()
    {
        $this->primeRegisterDefaults();
        $this->setConfigItem('default_primary_role', 5);
        $this->setConfigItem('req_mbr_activation', 'manual');
        $this->setConfigItem('use_recaptcha', 'n');
        $this->seedRegisterPost([
            'username' => 'captcha-user',
            'password' => 'secret-pass',
            'screen_name' => 'Captcha User',
            'email' => 'captcha-user@example.com',
            'captcha' => 'bad-word',
        ]);
        $this->modelService->roleRecords[5] = (object) ['role_id' => 5, 'is_locked' => 'n'];
        $this->db->captchaCountResult = 0;
        ee()->setMock('Captcha', new class {
            public function shouldRequireCaptcha()
            {
                return true;
            }
            public function create()
            {
                return '<captcha />';
            }
        });

        $result = $this->subject->register_member();

        $this->assertSame('SHOW_USER_ERROR', $result);
    }

    public function testRegisterMemberReturnsFormAliasesWhenRecaptchaIsRequiredButMissing()
    {
        $this->primeRegisterDefaults();
        $this->setConfigItem('default_primary_role', 5);
        $this->setConfigItem('req_mbr_activation', 'manual');
        $this->setConfigItem('use_recaptcha', 'y');
        $this->seedRegisterPost([
            'username' => 'recaptcha-missing-user',
            'password' => 'secret-pass',
            'screen_name' => 'Recaptcha Missing',
            'email' => 'recaptcha-missing@example.com',
            'captcha' => '',
        ]);
        $this->modelService->roleRecords[5] = (object) ['role_id' => 5, 'is_locked' => 'n'];
        ee()->setMock('Captcha', new class {
            public function shouldRequireCaptcha()
            {
                return true;
            }
            public function create()
            {
                return '<captcha />';
            }
        });

        $result = $this->subject->register_member();

        $this->assertSame('SHOW_FORM_ERROR_ALIASES', $result);
    }

    public function testRegisterMemberReturnsFormAliasesWhenTermsRequiredButMissing()
    {
        $this->primeRegisterDefaults();
        $this->setConfigItem('default_primary_role', 5);
        $this->setConfigItem('req_mbr_activation', 'manual');
        $this->setConfigItem('require_terms_of_service', 'y');
        $this->seedRegisterPost([
            'username' => 'terms-missing-user',
            'password' => 'secret-pass',
            'screen_name' => 'Terms Missing',
            'email' => 'terms-missing@example.com',
            'accept_terms' => '',
        ]);
        $this->modelService->roleRecords[5] = (object) ['role_id' => 5, 'is_locked' => 'n'];

        $result = $this->subject->register_member();

        $this->assertSame('SHOW_FORM_ERROR_ALIASES', $result);
    }

    public function testStartMemberSessionLoadsAuthAndStartsSession()
    {
        Auth_result::reset();

        $this->callPrivateMethod('startMemberSession', [42]);

        $this->assertContains('auth', $this->load->libraries);
        $this->assertSame('members', $this->db->lastGetWhereTable);
        $this->assertSame(42, (int) $this->db->lastGetWhereConditions['member_id']);
        $this->assertSame([42], Auth_result::$constructed);
        $this->assertSame(1, Auth_result::$rememberCalls);
        $this->assertSame(1, Auth_result::$sessionCalls);
    }
}
