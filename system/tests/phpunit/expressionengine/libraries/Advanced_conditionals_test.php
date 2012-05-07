<?php


define("LD", '{');
define("RD", '}');

function unique_marker($which)
{
	return 'static:'.$which.':marker';
}

// @todo anything above this line needs to be moved elsewhere

require_once APPPATH.'libraries/Template.php';

class Template_extended extends EE_Template {

	public function __construct()
	{
		$test = CI_TestCase::instance();
		$cls =& $test->ci_core_class('cfg');

		// set predictable config values
		$test->ci_set_config(array(
			'protect_javascript' => 'n'
		));

		$test->config = new $cls;
	}
}


/**
 * Advanced Conditionals Test Runner
 */
class Advanced_conditionals_test extends CI_TestCase {

	public function set_up()
	{
		$this->template = new Template_extended();

		// initialize stubs
		// the testing methods will configure them as needed
		$this->_func_stub = $this->getMock('EE_Functions', array('prep_conditionals', 'evaluate'));
		$this->_load_stub = $this->getMock('EE_Loader', array('helper'));
	}

	private function _reference()
	{
		$this->_func_stub
			->expects($this->any())
			->method('prep_conditionals')
			->will($this->returnValue('{if TRUE} yeppers {if:else} noppers {/if} <?php if (TRUE):?> yes <?php else:?> no <?php endif;?>'));
		
		$this->_func_stub
			->expects($this->any())
			->method('prep_conditionals')
			->will($this->returnCallback(function() {
				return '{if TRUE} yeppers {if:else} noppers {/if} <?php if (TRUE):?> yes <?php else:?> no <?php endif;?>';
			}));

		$this->_func_stub
			->expects($this->any())
			->method('evaluate')
			->will( $this->returnCallback(array($this, '_eval')) );
	}

		// --------------------------------------------------------------------

	/**
	 * Test Simple EE Tags to PHP Replacements
	 *
	 * Tests the rewriting of EE {if}s to php conditionals. The test
	 * strings in this method should be chosen so that they are not
	 * affected by any of the prepping.
	 *
	 * 1. Define start string
	 * 2. Mock prep_conditionals to check that the string has not changed
	 * 3. Pass string through prep_conditionals mock unchanged
	 * 4. Check string for rewriting before the eval
	 *
	 * @dataProvider post_prep_data_provider
	 */
	public function test_post_prep_replacements($str, $pre_eval_expected, $final_expected)
	{
		// @todo
		// segment_vars
		// embed_vars

		// make sure the input makes it through unscathed
		$this->_func_stub
			->expects($this->any())
			->method('prep_conditionals')
			->will($this->_assert_equal_and_pass_through($str));

		// setup result assertion
		$this->_func_stub
			->expects($this->any())
			->method('evaluate')
			->will($this->_assert_equal_and_callback($pre_eval_expected, $this->_eval_callback()));

		$this->_setup_clean();


		$result = $this->template->advanced_conditionals($str);
		$this->assertEquals($final_expected, $result);
	}

	// --------------------------------------------------------------------

	/**
	 * Data Provider for the above test
	 */
	public static function post_prep_data_provider()
	{
		// input, pre/post_prep, post eval
		return array(

			// if true
			array(
				'{if TRUE}true_showing{/if}',
				'<?php if(TRUE) : ?>true_showing<?php endif; ?>',
				'true_showing'
			),

			// if false
			array(
				'{if FALSE}false_showing{/if}',
				'<?php if(FALSE) : ?>false_showing<?php endif; ?>',
				''
			),

			// if true, else
			array(
				'{if TRUE}true_showing{if:else}else_showing{/if}',
				'<?php if(TRUE) : ?>true_showing<?php else : ?>else_showing<?php endif; ?>',
				'true_showing'
			),

			// if false, else
			array(
				'{if FALSE}false_showing{if:else}else_showing{/if}',
				'<?php if(FALSE) : ?>false_showing<?php else : ?>else_showing<?php endif; ?>',
				'else_showing'
			),

			// if true, elseif
			array(
				'{if TRUE}true_showing{if:elseif FALSE}else_if_showing{/if}',
				'<?php if(TRUE) : ?>true_showing<?php elseif(FALSE) : ?>else_if_showing<?php endif; ?>',
				'true_showing'
			),

			// if false, elseif
			array(
				'{if FALSE}false_showing{if:elseif TRUE}else_if_showing{/if}',
				'<?php if(FALSE) : ?>false_showing<?php elseif(TRUE) : ?>else_if_showing<?php endif; ?>',
				'else_if_showing'
			),

			// if false, elseif false, else
			array(
				'{if FALSE}false_showing{if:elseif FALSE}else_if_showing{if:else}else_showing{/if}',
				'<?php if(FALSE) : ?>false_showing<?php elseif(FALSE) : ?>else_if_showing<?php else : ?>else_showing<?php endif; ?>',
				'else_showing'
			),

			// multiline if
			array(
				"{if\n TRUE\n}if_showing\n{/if}\n",
				"<?php if(TRUE\n) : ?>if_showing\n<?php endif; ?>\n",
				"if_showing\n"
			),

			// nested if true
			array(
				'{if TRUE}outer_showing {if TRUE}inner_showing{/if} {/if}',
				'<?php if(TRUE) : ?>outer_showing <?php if(TRUE) : ?>inner_showing<?php endif; ?> <?php endif; ?>',
				'outer_showing inner_showing '
			),

			// nested else
			array(
				'{if FALSE}outer_false {if:else}outer_else {if TRUE}inner_showing{/if} {/if}',
				'<?php if(FALSE) : ?>outer_false <?php else : ?>outer_else <?php if(TRUE) : ?>inner_showing<?php endif; ?> <?php endif; ?>',
				'outer_else inner_showing '
			),
		);
	}

	// --------------------------------------------------------------------

	/**
	 * @dataProvider in_group_data_provider
	 */
	public function test_in_group($str, $pre_prep_expected, $final_expected)
	{
		$str = "{if in_group('1')}yes{/if}";

		// make sure the input gets converted correctly
		$this->_func_stub
			->expects($this->any())
			->method('prep_conditionals')
			->will($this->_assert_equal_and_pass_through($pre_prep_expected));

		$this->_setup_clean();

		// setup result assertion
		$this->_func_stub
			->expects($this->any())
			->method('evaluate')
			->will($this->returnCallback($this->_eval_callback()));



		$result = $this->template->advanced_conditionals($str);
		$this->assertEquals($final_expected, $result);
	}

	// --------------------------------------------------------------------

	public static function in_group_data_provider()
	{
		return array(
			array(
				"{if in_group('1')}yes{/if}",
				"{if TRUE}yes{/if}",
				'yes'
			),
			array(
				"{if in_group('1|2')}yes{/if}",
				"{if TRUE}yes{/if}",
				'yes'
			),
			array(
				"{if in_group('5')}yes{/if}",
				"{if FALSE}yes{/if}",
				''
			),
		);
	}

	// --------------------------------------------------------------------

	/**
	 * Utility method to set up a basic no frills test
	 */
	protected function _setup_clean()
	{
		$this->ci_instance_var('functions', $this->_func_stub);

		$this->_set_localize();
		$this->_disable_helpers();
		$this->_set_globals($this->_default_global_vars());
		$this->_set_session($this->_default_userdata());

		$this->ci_set_config('protect_javascript', 'y');
		$this->template->EE =& get_instance();
	}

	protected function _disable_helpers()
	{
		$this->_load_stub
			->expects($this->any())
			->method('helper')
			->will($this->returnValue(TRUE));

		$this->ci_instance_var('load', $this->_load_stub);
	}

	protected function _set_globals($globals)
	{
		$this->config->_global_vars = $globals;
		$this->ci_instance_var('config', $this->config);
	}

	protected function _set_session($userdata)
	{
		$session = new StdClass;
		$session->userdata = $userdata;
		$this->ci_instance_var('session', $session);
	}

	protected function _set_localize()
	{
		$localize = new StdClass;
		$localize->now = $_SERVER['REQUEST_TIME'];
		$this->ci_instance_var('localize', $localize);
	}

	protected function _default_userdata()
	{
		return array(
			'member_id'		=> 1,
			'group_id'		=> 1,
			'group_description' => 'group_description',
			'group_title' => 'group_title',
			'username' => 'username',
			'screen_name' => 'screen_name',
			'email' => 'email',
			'ip_address' => '127.0.0.2',
			'location' => 'location',
			'total_entries' => 0,
			'total_comments' => 0,
			'private_messages' => 0,
			'total_forum_posts' => 0,
			'total_forum_topics' => 0,
			'total_forum_replies' => 0
		);
	}

	protected function _default_global_vars()
	{
		return array();
	}

	// -----------------------
	// ------ Callbacks ------
	// -----------------------

	public function _eval_callback()
	{
		return function($str) {
			return eval('?>'.$str);
		};
	}


	// ------------------------------
	// ------ Callback Helpers ------
	// ------------------------------

	/**
	 * Validates the callback parameter and then calls the callback
	 */
	protected function _assert_equal_and_callback($expected_in, $callback)
	{
		$self = $this;
		return $this->returnCallback(function($in) use ($self, $expected_in, $callback) {
			$self->assertEquals($expected_in, $in);
			return $callback($in);
		});
	}

	/**
	 * Validates the callback parameter and returns it
	 */
	protected function _assert_equal_and_pass_through($expected_in)
	{
		return $this->_assert_equal_and_callback($expected_in, function($str) {
			return $str;
		});
	}

	/**
	 * Validates the callback parameter and returns a new value
	 */
	protected function _assert_equal_and_return($expected_in, $return)
	{
		return $this->_assert_equal_and_callback($expected_in, function($str) use ($return) {
			return $return;
		});
	}
}