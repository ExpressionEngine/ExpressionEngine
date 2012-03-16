<?php

require_once APPPATH.'libraries/Template.php';

class Template_test extends CI_TestCase
{
	public function set_up()
	{
		$this->template = $this->getMockBuilder('EE_Template')
			->setMethods(array('log_item'))	// specifying a method lets us keep EE_Template
			->disableOriginalConstructor()	// intact while still disabling the constructor
			->getMock();
	}

	// --------------------------------------------------------------------

	public function test_convert_xml_declaration()
	{
		$str = "<?xml randomjunk ?>";
		$expected = "<XXML randomjunk /XXML>";
		$result = $this->template->convert_xml_declaration($str);

		$this->assertEquals($expected, $result);


		$str = "<?xml randomnewline\njunk ?>";
		$expected = "<?xml randomnewline\njunk ?>";
		$result = $this->template->convert_xml_declaration($str);

		$this->assertEquals($expected, $result, "XML Regex matching multiline");


		$str = "<?xml randomjunk ?> ?>";
		$expected = "<XXML randomjunk /XXML> ?>";
		$result = $this->template->convert_xml_declaration($str);

		$this->assertEquals($expected, $result, "XML Regex too greedy");
	}

	// --------------------------------------------------------------------

	public function test_restore_xml_declaration()
	{
		$str = "<XXML randomjunk /XXML>";
		$expected = "<?xml randomjunk ?>";
		$result = $this->template->restore_xml_declaration($str);

		$this->assertEquals($expected, $result);


		$str = "<XXML random\nnewline /XXML>";
		$expected = "<XXML random\nnewline /XXML>";
		$result = $this->template->restore_xml_declaration($str);

		$this->assertEquals($expected, $result, "XML Regex matching multiline");


		$str = "<XXML randomjunk /XXML> /XXML>";
		$expected = "<?xml randomjunk ?> /XXML>";
		$result = $this->template->restore_xml_declaration($str);

		$this->assertEquals($expected, $result, "XML Regex too greedy");
	}

	// --------------------------------------------------------------------

	public function test_remove_ee_comments()
	{
		$str = "some {exp:template} awesome {!-- removed --} stuff {/exp:template}";
		$expected = "some {exp:template} awesome  stuff {/exp:template}";
		$result = $this->template->remove_ee_comments($str);

		$this->assertEquals($expected, $result);
	}

	// --------------------------------------------------------------------


}