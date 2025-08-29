<?php

require_once __DIR__ . '/Pro_searchTestBase.php';

class Pro_searchParamTest extends Pro_searchTestBase
{
    public function testParamReturnsNoResultsWhenMissing()
    {
        $this->setTemplateParams([]);
        $out = $this->pro->param();
        $this->assertSame('NO_RESULTS', $out);
    }

    public function testParamReturnsFormattedValue()
    {
        $this->setParamsStub(['color' => 'red|blue']);
        $this->setTemplateParams(['get' => 'color']);
        $out = $this->pro->param();
        $this->assertSame('red|blue', $out);
    }

    public function testParamListExpansionWithAs()
    {
        // color param has two values; with as=item, template should repeat
        $this->setParamsStub(['color' => 'red|blue']);
        $this->setTemplateParams(['get' => 'color', 'as' => 'item']);
        ee()->TMPL->tagdata = '{item}-';
        $out = $this->pro->param();
        $this->assertSame('red-blue-', $out);
    }
}


