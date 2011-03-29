<?php

class Typography_test extends PHPUnit_Framework_TestCase
{
	static $cls;
	protected $table;
	
	public static function setUpBeforeClass()
	{
		$CI = get_instance();
		$CI->load->library('typography');
		self::$cls = get_class($CI->typography);
	}

	// --------------------------------------------------------------------
	
	public function setUp()
	{
		$cls = self::$cls;
		$this->type = new $cls;
	}

	// --------------------------------------------------------------------

	/**
	 * Tests the format_characters() function.
	 *
	 * this can and should grow.
	 */
	public function testFormatCharacters()
	{
		$strs = array(
			'"double quotes"' 				=> '&#8220;double quotes&#8221;',
			'"testing" in "theory" that is' => '&#8220;testing&#8221; in &#8220;theory&#8221; that is',
			"Here's what I'm" 				=> 'Here&#8217;s what I&#8217;m',
			'&' 							=> '&amp;',
			'&amp;' 						=> '&amp;',
			'&nbsp;'						=> '&nbsp;',
			'--'							=> '&#8212;',
			'foo...'						=> 'foo&#8230;',
			'foo..'							=> 'foo..',
			'foo...bar.'					=> 'foo&#8230;bar.',
			'test.  new'					=> 'test.&nbsp; new',
		);	
		
		foreach ($strs as $str => $expected)
		{
			$this->assertEquals($expected, $this->type->format_characters($str));		
		}
	}

	// --------------------------------------------------------------------

	public function testNl2brExceptPre()
	{
		$str = <<<EOH
Hello, I'm a happy string with some new lines.  

I like to skip.

Jump

and sing.

<pre>
I am inside a pre tag.  Please don't mess with me.

k?
</pre>

That's my story and I'm sticking to it.

The End.
EOH;

		$expected = <<<EOH
Hello, I'm a happy string with some new lines.  <br />
<br />
I like to skip.<br />
<br />
Jump<br />
<br />
and sing.<br />
<br />
<pre>
I am inside a pre tag.  Please don't mess with me.

k?
</pre><br />
<br />
That's my story and I'm sticking to it.<br />
<br />
The End.
EOH;
	
		$this->assertEquals($expected, $this->type->nl2br_except_pre($str));
	}

}