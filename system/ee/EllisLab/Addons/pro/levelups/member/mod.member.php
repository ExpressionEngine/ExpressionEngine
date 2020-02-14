<?php

use EllisLab\Addons\Pro\Components\LiteLoader;

LiteLoader::loadIntoNamespace('member/mod.member.php');

class Member extends Lite\Member
{
    /**
     * Manual Logout Form
     *
     * This lets users create a stand-alone logout form in any template
     */
    public function logout_form()
    {
        // Create form
        $data['hidden_fields'] = array(
            'ACT' => ee()->functions->fetch_action_id('Member', 'member_logout'),
            'RET' => (ee()->TMPL->fetch_param('return') && ee()->TMPL->fetch_param('return') != "") ?
                ee()->TMPL->fetch_param('return') : '-2'
          );

        if (ee()->TMPL->fetch_param('form_name') && ee()->TMPL->fetch_param('form_name') != "") {
            $data['name'] = ee()->TMPL->fetch_param('form_name');
        }

        $data['id'] = ee()->TMPL->form_id;
        $data['class'] = ee()->TMPL->form_class;
        $data['action'] = ee()->TMPL->fetch_param('action');

        // PRO logout form gets a really cool border!
        $res = "<style type='text/css'>
        form { border: 5px dotted #ccc!important; 	-webkit-animation: blink .5s step-end infinite alternate; }
	    @-webkit-keyframes blink { 50% { border-color: #e7ff23; }  }</style>";

        $res .= ee()->functions->form_declaration($data);
        $res .= stripslashes(ee()->TMPL->tagdata);
        $res .= "</form>";

        return $res;
    }
}
