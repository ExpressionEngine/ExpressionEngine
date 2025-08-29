<?php

require_once __DIR__ . '/Pro_searchTestBase.php';

class Pro_searchCatchSearchTest extends Pro_searchTestBase
{
    public function testCatchSearchRedirectsToCreatedUrl()
    {
        // Mock input->post/get
        ee()->setMock('input', new class {
            public function post($k){ return null; }
            public function get_post($k){ return null; }
        });

        // Provide $_GET/$_POST values through superglobals access the code uses
        $_GET = ['keywords' => 'alpha', 'category' => '5'];
        $_POST = [];

        // Run
        $this->pro->catch_search();

        $redirect = $this->getLastRedirect();
        $this->assertNotNull($redirect);
        $this->assertStringContainsString('https://', $redirect);
        // With default encode_query=y, should include base64 payload segment
        $this->assertStringContainsString('/search/results/', $redirect);
    }

    public function testCatchSearchSetsFlashdataOnRequiredMissing()
    {
        ee()->setMock('input', new class {
            public function post($k){ return $k === 'params' ? base64_encode(json_encode(['required' => 'keywords'])) : null; }
            public function get_post($k){ return null; }
        });

        // No keywords provided, triggers required error and redirect back to referer
        $_GET = [];
        $_POST = ['params' => base64_encode(json_encode(['required' => 'keywords']))];
        $_SERVER['HTTP_REFERER'] = 'https://example.com/form';

        // Override functions->redirect to emulate exit on redirect
        $func = new class extends ProSearchFakeFunctions {
            public function redirect($url){ $this->lastRedirect = $url; throw new RuntimeException('redirect'); }
        };
        ee()->setMock('functions', $func);

        try {
            $this->pro->catch_search();
            $this->fail('Expected redirect exception');
        } catch (RuntimeException $e) {
            $this->assertSame('redirect', $e->getMessage());
        }

        $this->assertSame('https://example.com/form', $func->lastRedirect);
        $this->assertSame('fields_missing', ee()->session->flashdata['error_message'] ?? null);
    }
}


