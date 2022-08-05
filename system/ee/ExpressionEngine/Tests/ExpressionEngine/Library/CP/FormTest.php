<?php

namespace ExpressionEngine\Tests\Library\CP;

use ExpressionEngine\Library\CP\Form;
use PHPUnit\Framework\TestCase;

class _form extends Form
{
    public function getPrototype(): array
    {
        return $this->prototype;
    }

    public function getStructure(): array
    {
        return $this->structure;
    }

    public function getHidden(): array
    {
        return $this->hidden_fields;
    }

    public function geButtons(): array
    {
        return $this->buttons;
    }
}

/**
 * @covers \ExpressionEngine\Library\CP\Form
 */
class FormTest extends TestCase
{
    public function testSignatureAttributeExists(): Form
    {
        $form = new _form;
        $this->assertObjectHasAttribute('prototype', $form);
        $this->assertCount(15, $form->getPrototype());
        return $form;
    }

    /**
     * @depends testSignatureAttributeExists
     * @param Form $form
     * @return Form
     */
    public function testSaveBtnTextPrototypeKey(Form $form): array
    {
        $prototype = $form->getPrototype();
        $this->assertTrue(isset($prototype['save_btn_text']));
        $this->assertEquals('save', $prototype['save_btn_text']);
        return $prototype;
    }

    /**
     * @depends testSaveBtnTextPrototypeKey
     * @param Form $form
     * @return Form
     */
    public function testSaveBtnWorkingTextPrototypeKey(array $prototype): array
    {
        $this->assertTrue(isset($prototype['save_btn_text_working']));
        $this->assertEquals('saving', $prototype['save_btn_text_working']);
        return $prototype;
    }

    /**
     * @depends testSaveBtnWorkingTextPrototypeKey
     * @param array $prototype
     * @return array
     */
    public function testCpPageTitlePrototypeKey(array $prototype): array
    {
        $this->assertTrue(isset($prototype['cp_page_title']));
        $this->assertEquals('', $prototype['cp_page_title']);
        return $prototype;
    }

    /**
     * @depends testCpPageTitlePrototypeKey
     * @param array $prototype
     * @return array
     */
    public function testBaseUrlPrototypeKey(array $prototype): array
    {
        $this->assertTrue(isset($prototype['base_url']));
        $this->assertEquals('', $prototype['base_url']);
        return $prototype;
    }

    /**
     * @return Form
     */
    public function testStructureAttributeExists(): Form
    {
        $form = new _form;
        $this->assertObjectHasAttribute('structure', $form);
        $this->assertIsArray($form->getStructure());
        $this->assertCount(0, $form->getStructure());
        return $form;
    }

    /**
     * @return Form
     */
    public function testHiddenFieldsAttributeExists(): Form
    {
        $form = new _form;
        $this->assertObjectHasAttribute('hidden_fields', $form);
        $this->assertIsArray($form->getHidden());
        $this->assertCount(0, $form->getHidden());
        return $form;
    }

    /**
     * @return Form
     */
    public function testButtonsAttributeExists(): Form
    {
        $form = new _form;
        $this->assertObjectHasAttribute('buttons', $form);
        $this->assertIsArray($form->geButtons());
        $this->assertCount(0, $form->geButtons());
        return $form;
    }

    /**
     * @return Form
     */
    public function testTabAttributeExists(): Form
    {
        $form = new Form;
        $this->assertObjectHasAttribute('tab', $form);
        $this->assertFalse($form->isTab());
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form', $form->asTab());
        $this->assertTrue($form->isTab());
        return $form;
    }

    /**
     * @return void
     */
    public function testDefaultReturnStructureForAsArray()
    {
        $form = new Form;
        $array = $form->toArray();
        $this->assertArrayHasKey('save_btn_text', $array);
        $this->assertArrayHasKey('save_btn_text_working', $array);
        $this->assertArrayHasKey('cp_page_title', $array);
        $this->assertArrayHasKey('base_url', $array);
    }

    /**
     * @return Form
     */
    public function testsAsHeadingReturnInstance(): Form
    {
        $form = new Form;
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form', $form->asHeading());
        return $form;
    }

    /**
     * @depends testsAsHeadingReturnInstance
     * @param Form $form
     * @return Form
     */
    public function testAsHeadingSetsTabFalse(Form $form): Form
    {
        $this->assertFalse($form->isTab());
        return $form;
    }

    /**
     * @return Form
     */
    public function testAsFileUploadReturnInstance(): Form
    {
        $form = new Form;
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form', $form->asFileUpload(true));
        return $form;
    }

    /**
     * @depends testAsFileUploadReturnInstance
     * @param Form $form
     * @return Form
     */
    public function testAsFileUploadValue(Form $form): Form
    {
        $this->assertTrue($form->get('has_file_input'));
        return $form;
    }

    /**
     * @return Form
     */
    public function testAddAlertMethod(): Form
    {
        $form = new Form;
        $this->assertNull($form->get('extra_alerts'));
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form', $form->addAlert('test_alert'));
        $this->assertIsArray($form->get('extra_alerts'));

        $alerts = $form->get('extra_alerts');
        $this->assertCount(1, $alerts);
        $this->assertTrue(in_array('test_alert', $alerts));
        return $form;
    }

    /**
     * @depends testAddAlertMethod
     */
    public function testRemoveAlertMethod(Form $form)
    {
        $alerts = $form->get('extra_alerts');
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form', $form->removeAlert('test_alert'));
        $this->assertNull($form->get('extra_alerts'));
    }

    /**
     * @return void
     */
    public function testGetEmptyGroupReturnsAGroup(): Form
    {
        $form = new Form;
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\Group', $form->getGroup('test_group'));
        return $form;
    }

    /**
     * @depends testGetEmptyGroupReturnsAGroup
     * @param Form $form
     * @return Form
     */
    public function testValidGetGroupInstance(Form $form): Form
    {
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\Group', $form->getGroup('test_group'));
        return $form;
    }

    /**
     * @depends testValidGetGroupInstance
     * @param Form $form
     * @return void
     */
    public function testRemoveGroupReturnsTrue(Form $form): Form
    {
        $this->assertTrue($form->removeGroup('test_group'));
        return $form;
    }

    /**
     * @depends testRemoveGroupReturnsTrue
     * @param Form $form
     * @return void
     */
    public function testRemoveBadGroupReturnsFalse(Form $form)
    {
        $group = '_'.\random_bytes(10);
        $this->assertFalse($form->removeGroup($group));
    }

    /**
     * @return void
     */
    public function testGetEmptyButtonReturnsAButton(): Form
    {
        $form = new Form;
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\Button', $form->getButton('test_button'));
        return $form;
    }

    /**
     * @depends testGetEmptyButtonReturnsAButton
     * @param Form $form
     * @return Form
     */
    public function testValidGetButtonInstance(Form $form): Form
    {
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\Button', $form->getButton('test_button'));
        return $form;
    }

    /**
     * @depends testValidGetButtonInstance
     * @param Form $form
     * @return Form
     */
    public function testRemoveButtonReturnsTrue(Form $form): Form
    {
        $this->assertTrue($form->removeButton('test_button'));
        return $form;
    }

    /**
     * @depends testRemoveButtonReturnsTrue
     * @param Form $form
     * @return void
     */
    public function testRemoveBadButtonReturnsFalse(Form $form)
    {
        $button = '_'.\random_bytes(10);
        $this->assertFalse($form->removeButton($button));
    }

    /**
     * @return void
     */
    public function testGetEmptyHiddenFieldReturnsAHiddenField(): Form
    {
        $form = new Form;
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\Fields\Hidden', $form->getHiddenField('test_field'));
        return $form;
    }

    /**
     * @depends testGetEmptyHiddenFieldReturnsAHiddenField
     * @param Form $form
     * @return Form
     */
    public function testValidGetHiddenInstance(Form $form): Form
    {
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\Fields\Hidden', $form->getHiddenField('test_field'));
        return $form;
    }

    /**
     * @depends testValidGetHiddenInstance
     * @param Form $form
     * @return Form
     */
    public function testRemoveHiddenFieldReturnsTrue(Form $form): Form
    {
        $this->assertTrue($form->removeHiddenField('test_field'));
        return $form;
    }

    /**
     * @depends testRemoveHiddenFieldReturnsTrue
     * @param Form $form
     * @return void
     */
    public function testRemoveBadHiddenFieldReturnsFalse(Form $form)
    {
        $field = '_'.\random_bytes(10);
        $this->assertFalse($form->removeHiddenField($field));
    }

    /**
     * @return Form
     */
    public function testWithActionButtonReturnInstance(): Form
    {
        $form = new Form;
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form', $form->withActionButton('test-test', 'test-url'));
        return $form;
    }

    /**
     * @depends testWithActionButtonReturnInstance
     * @param Form $form
     * @return Form
     */
    public function testWithActionButtonStructure(Form $form): Form
    {
        $action_button = $form->get('action_button');
        $this->assertArrayHasKey('text', $action_button);
        $this->assertArrayHasKey('href', $action_button);
        $this->assertArrayHasKey('rel', $action_button);
        return $form;
    }

    /**
     * @depends testWithActionButtonStructure
     * @param Form $form
     * @return Form
     */
    public function testWithActionButtonValues(Form $form): Form
    {
        $action_button = $form->get('action_button');
        $this->assertEquals('test-test', $action_button['text']);
        $this->assertEquals('test-url', $action_button['href']);
        $this->assertEquals('', $action_button['rel']);
        return $form;
    }

    /**
     * @depends testWithActionButtonValues
     * @param Form $form
     * @return Form
     */
    public function testRemoveActionButtonReturnInstance(Form $form): Form
    {
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form', $form->withOutActionButton());
        return $form;
    }

    /**
     * @depends testRemoveActionButtonReturnInstance
     * @param Form $form
     * @return void
     */
    public function testActionButtonIsNullAfterRemoval(Form $form)
    {
        $this->assertNull($form->get('action_button'));
    }

    /**
     * @return Form
     */
    public function testGetSaveBtnTextDefaultValue(): Form
    {
        $form = new Form;
        $this->assertEquals('save', $form->getSaveBtnText());
        return $form;
    }

    /**
     * @depends testGetSaveBtnTextDefaultValue
     * @param Form $form
     * @return Form
     */
    public function testSetSaveBtnTextReturnInstance(Form $form): Form
    {
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form', $form->setSaveBtnText('my-save-text'));
        return $form;
    }

    /**
     * @depends testSetSaveBtnTextReturnInstance
     * @param Form $form
     * @return Form
     */
    public function testSetSaveBtnTextReallySetValue(Form $form): Form
    {
        $this->assertEquals('my-save-text', $form->getSaveBtnText());
        $this->assertEquals('my-save-text', $form->get('save_btn_text'));
        return $form;
    }

    /**
     * @return Form
     */
    public function testGetSaveBtnTextWorkingDefaultValue(): Form
    {
        $form = new Form;
        $this->assertEquals('saving', $form->getSaveBtnTextWorking());
        return $form;
    }

    /**
     * @depends testGetSaveBtnTextWorkingDefaultValue
     * @param Form $form
     * @return Form
     */
    public function testSetSaveBtnTextWorkingReturnInstance(Form $form): Form
    {
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form', $form->setSaveBtnTextWorking('my-save-text-working'));
        return $form;
    }

    /**
     * @depends testSetSaveBtnTextWorkingReturnInstance
     * @param Form $form
     * @return Form
     */
    public function testSetSaveBtnTextWorkingReallySetValue(Form $form): Form
    {
        $this->assertEquals('my-save-text-working', $form->getSaveBtnTextWorking());
        $this->assertEquals('my-save-text-working', $form->get('save_btn_text_working'));
        return $form;
    }

    /**
     * @return Form
     */
    public function testGetAjaxValidateDefaultValue(): Form
    {
        $form = new Form;
        $this->assertNull($form->getAjaxValidate());
        $this->assertNull($form->get('ajax_validate'));
        return $form;
    }

    /**
     * @depends testGetAjaxValidateDefaultValue
     * @param Form $form
     * @return Form
     */
    public function testSetAjaxValidateReturnInstance(Form $form): Form
    {
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form', $form->setAjaxValidate(true));
        return $form;
    }

    /**
     * @depends testSetAjaxValidateReturnInstance
     * @param Form $form
     * @return void
     */
    public function testSetAjaxValidateValues(Form $form)
    {
        $this->assertTrue($form->getAjaxValidate());
        $this->assertTrue($form->get('ajax_validate'));
    }

    /**
     * @return Form
     */
    public function testGetAlertsNameDefaultValue(): Form
    {
        $form = new Form;
        $this->assertNull($form->getAlertsName());
        $this->assertNull($form->get('alerts_name'));
        return $form;
    }

    /**
     * @depends testGetAlertsNameDefaultValue
     * @param Form $form
     * @return Form
     */
    public function testSetAlertsNameReturnInstance(Form $form): Form
    {
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form', $form->setAlertsName('test-alert-name'));
        return $form;
    }

    /**
     * @depends testSetAlertsNameReturnInstance
     * @param Form $form
     * @return void
     */
    public function testSetAlertsReturnValue(Form $form)
    {
        $this->assertEquals('test-alert-name', $form->getAlertsName());
        $this->assertEquals('test-alert-name', $form->get('alerts_name'));
    }

    /**
     * @return Form
     */
    public function testGetCpPageTitleAltDefaultValue(): Form
    {
        $form = new Form;
        $this->assertNull($form->getCpPageTitleAlt());
        $this->assertNull($form->get('cp_page_title_alt'));
        return $form;
    }

    /**
     * @depends testGetCpPageTitleAltDefaultValue
     * @param Form $form
     * @return Form
     */
    public function testSetCpPageTitleAltReturnInstance(Form $form): Form
    {
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form', $form->setCpPageTitleAlt('test-alt-cp-page-title-alt'));
        return $form;
    }

    /**
     * @depends testSetCpPageTitleAltReturnInstance
     * @param Form $form
     * @return void
     */
    public function testSetCpPageTitleAltReturnValue(Form $form)
    {
        $this->assertEquals('test-alt-cp-page-title-alt', $form->getCpPageTitleAlt());
        $this->assertEquals('test-alt-cp-page-title-alt', $form->get('cp_page_title_alt'));
    }

    /**
     * @return Form
     */
    public function testGetCpPageTitleDefaultValue(): Form
    {
        $form = new Form;
        $this->assertEquals('', $form->getCpPageTitle());
        $this->assertEquals('', $form->get('cp_page_title'));
        return $form;
    }

    /**
     * @depends testGetCpPageTitleDefaultValue
     * @param Form $form
     * @return Form
     */
    public function testSetCpPageTitleReturnInstance(Form $form): Form
    {
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form', $form->setCpPageTitle('test-alt-cp-page-title'));
        return $form;
    }

    /**
     * @depends testSetCpPageTitleReturnInstance
     * @param Form $form
     * @return void
     */
    public function testSetCpPageTitleReturnValue(Form $form)
    {
        $this->assertEquals('test-alt-cp-page-title', $form->getCpPageTitle());
        $this->assertEquals('test-alt-cp-page-title', $form->get('cp_page_title'));
    }

    /**
     * @return Form
     */
    public function testGetHideTopButtons(): Form
    {
        $form = new Form;
        $this->assertNull($form->getHideTopButtons());
        $this->assertNull($form->get('hide_top_buttons'));
        return $form;
    }

    /**
     * @depends testGetCpPageTitleDefaultValue
     * @param Form $form
     * @return Form
     */
    public function testSetHideTopButtonsReturnInstance(Form $form): Form
    {
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form', $form->setHideTopButtons(true));
        return $form;
    }

    /**
     * @depends testSetCpPageTitleReturnInstance
     * @param Form $form
     * @return void
     */
    public function testSetHideTopButtonsReturnValue(Form $form)
    {
        $this->assertTrue($form->getHideTopButtons());
        $this->assertTrue($form->get('hide_top_buttons'));
    }

    /**
     * @return Form
     */
    public function testGetBaseUrl(): Form
    {
        $form = new Form;
        $this->assertEquals('', $form->getBaseUrl());
        $this->assertEquals('', $form->get('base_url'));
        return $form;
    }

    /**
     * @depends testGetCpPageTitleDefaultValue
     * @param Form $form
     * @return Form
     */
    public function testSetBaseUrlReturnInstance(Form $form): Form
    {
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form', $form->setBaseUrl('test-base-url'));
        return $form;
    }

    /**
     * @depends testSetCpPageTitleReturnInstance
     * @param Form $form
     * @return void
     */
    public function testSetBaseUrlReturnValue(Form $form)
    {
        $this->assertEquals('test-base-url', $form->getBaseUrl());
        $this->assertEquals('test-base-url', $form->get('base_url'));
    }
}
