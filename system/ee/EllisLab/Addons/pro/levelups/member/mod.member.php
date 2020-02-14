<?php

use EllisLab\Addons\Pro\Components\LiteLoader;

LiteLoader::loadIntoNamespace('member/mod.member.php');

class Member extends Lite\Member
{
	/**
	 * This is an example of where you would put a pro member function
	 */
	public function pro_member_tag()
	{
		// Do something cool
	}
}
