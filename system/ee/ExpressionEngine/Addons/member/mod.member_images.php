<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Member Management Images
 */
class Member_images extends Member
{
    /**
     * Signature Edit Form
     */
    public function edit_signature()
    {
        // Are signatures allowed?
        if (ee()->config->item('allow_signatures') == 'n') {
            return $this->_trigger_error('edit_signature', 'signatures_not_allowed');
        }

        // Create the HTML formatting buttons
        $buttons = '';

        ee()->load->library('html_buttons');

        ee()->html_buttons->allow_img = (ee()->config->item('sig_allow_img_hotlink') == 'y') ? true : false;
        $buttons = ee()->html_buttons->create_buttons();

        $query = ee()->db->select("signature, sig_img_filename, sig_img_width, sig_img_height")
            ->where('member_id', (int) ee()->session->userdata('member_id'))
            ->get('members');

        $template = $this->_load_element('signature_form');

        if (ee()->config->item('sig_allow_img_upload') == 'y') {
            $template = $this->_allow_if('upload_allowed', $template);
            $template = $this->_deny_if('upload_not_allowed', $template);
        } else {
            $template = $this->_allow_if('upload_not_allowed', $template);
            $template = $this->_deny_if('upload_allowed', $template);
        }
        if ($query->row('sig_img_filename') == '' or ee()->config->item('sig_allow_img_upload') == 'n') {
            $template = $this->_deny_if('image', $template);
            $template = $this->_allow_if('no_image', $template);
        } else {
            $template = $this->_allow_if('image', $template);
            $template = $this->_deny_if('no_image', $template);
        }

        $max_kb = (ee()->config->item('sig_img_max_kb') == '' or ee()->config->item('sig_img_max_kb') == 0) ? 50 : ee()->config->item('sig_img_max_kb');
        $max_w = (ee()->config->item('sig_img_max_width') == '' or ee()->config->item('sig_img_max_width') == 0) ? 100 : ee()->config->item('sig_img_max_width');
        $max_h = (ee()->config->item('sig_img_max_height') == '' or ee()->config->item('sig_img_max_height') == 0) ? 100 : ee()->config->item('sig_img_max_height');
        $max_size = str_replace('%x', $max_w, lang('max_image_size'));
        $max_size = str_replace('%y', $max_h, $max_size);
        $max_size .= ' - ' . $max_kb . 'KB';

        $data = array(
            'action' => $this->_member_path('update_signature'),
            'enctype' => 'multi',
            'id' => 'submit_post'
        );

        return $this->_var_swap(
            $template,
            array(
                'form_declaration' => ee()->functions->form_declaration($data),
                'path:signature_image' => ee()->config->slash_item('sig_img_url') . $query->row('sig_img_filename'),
                'signature_image_width' => $query->row('sig_img_width'),
                'signature_image_height' => $query->row('sig_img_height'),
                'signature' => ee()->functions->encode_ee_tags($query->row('signature'), true),
                'lang:max_image_size' => $max_size,
                'maxchars' => (ee()->config->item('sig_maxlength') == 0) ? 10000 : ee()->config->item('sig_maxlength'),
                'include:html_formatting_buttons' => $buttons,
            )
        );
    }

    /**
     * Update Signature
     */
    public function update_signature()
    {
        // Are signatures allowed?
        if (ee()->config->item('allow_signatures') == 'n') {
            return $this->_trigger_error('edit_signature', 'signatures_not_allowed');
        }

        // Do we have what we need in $_POST?
        $body = ee()->input->post('body', true);
        if (empty($body)
            && (empty($_FILES) && ee()->config->item('sig_allow_img_upload') == 'y')) {
            return ee()->functions->redirect($this->_member_path('edit_signature'));
        }

        $maxlength = (ee()->config->item('sig_maxlength') == 0)
            ? 10000
            : ee()->config->item('sig_maxlength');

        if (strlen($body) > $maxlength) {
            return ee()->output->show_user_error(
                'submission',
                sprintf(lang('sig_too_big'), $maxlength)
            );
        }

        ee()->db->update(
            'members',
            array('signature' => $body),
            array('member_id' => ee()->session->userdata('member_id'))
        );

        // Is there an image to upload or remove?
        if ((isset($_FILES['userfile']) && $_FILES['userfile']['name'] != '')
            or isset($_POST['remove'])) {
            return $this->upload_signature_image();
        }

        // Success message
        return $this->_var_swap(
            $this->_load_element('success'),
            array(
                'lang:heading' => lang('signature'),
                'lang:message' => lang('signature_updated')
            )
        );
    }

    /**
     * Avatar Edit Form
     */
    public function edit_avatar()
    {
        // Fetch the template tag data
        $tagdata = trim(ee()->TMPL->tagdata);

        // If there is tag data, it's a tag pair, otherwise it's a single tag which means it's a legacy speciality template.
        $template = '';
        if (! empty($tagdata)) {
            $template = ee()->TMPL->tagdata;
        } elseif (ee('Config')->getFile()->getBoolean('legacy_member_templates')) {
            $template = $this->_load_element('edit_avatar');
        }

        // Does the current user have an avatar?
        $query = ee()->db->select("avatar_filename, avatar_width, avatar_height")
            ->where('member_id', (int) ee()->session->userdata('member_id'))
            ->get('members');

        if ($query->row('avatar_filename') == '') {
            $template = $this->_deny_if('avatar', $template);
            $template = $this->_allow_if('no_avatar', $template);

            $avatar_filename = '';
            $cur_avatar_url = '';
            $avatar_width = '';
            $avatar_height = '';
        } else {
            $template = $this->_allow_if('avatar', $template);
            $template = $this->_deny_if('no_avatar', $template);

            $avatar_url = ee()->config->slash_item('avatar_url');
            $avatar_fs_path = ee()->config->slash_item('avatar_path');

            if (file_exists($avatar_fs_path . 'default/' . $query->row('avatar_filename'))) {
                $avatar_url .= 'default/';
            }

            $cur_avatar_url = $avatar_url . $query->row('avatar_filename');

            $avatar_filename = $query->row('avatar_filename');
            $avatar_width = $query->row('avatar_width') ;
            $avatar_height = $query->row('avatar_height') ;
        }

        //if it's EE template request, parse some variables
        if (! empty($tagdata)) {
            $template = $this->_var_swap($template, [
                'avatar_url' => $cur_avatar_url,
                'avatar_filename' => $avatar_filename,
                'avatar_width' => $avatar_width,
                'avatar_height' => $avatar_height
            ]);
        }

        // Can users upload their own images?
        $template = $this->_allow_if('can_upload_avatar', $template);

        // Are there pre-installed avatars?

        // We'll make a list of all folders in the "avatar" folder,
        // then check each one to see if they contain images.  If so
        // we will add it to the list

        $avatar_path = ee()->config->slash_item('avatar_path');

        $extensions = array('.gif', '.jpg', '.jpeg', '.png');

        $template = $this->_deny_if('installed_avatars', $template);

        // Set the default image meta values
        $max_kb = (ee()->config->item('avatar_max_kb') == '' or ee()->config->item('avatar_max_kb') == 0) ? 50 : ee()->config->item('avatar_max_kb');
        $max_w = (ee()->config->item('avatar_max_width') == '' or ee()->config->item('avatar_max_width') == 0) ? 100 : ee()->config->item('avatar_max_width');
        $max_h = (ee()->config->item('avatar_max_height') == '' or ee()->config->item('avatar_max_height') == 0) ? 100 : ee()->config->item('avatar_max_height');
        $max_size = str_replace('%x', $max_w, lang('max_image_size'));
        $max_size = str_replace('%y', $max_h, $max_size);
        $max_size .= ' - ' . $max_kb . 'KB';

        //if we run EE template parser, do some things differently
        if (! empty($tagdata)) {
            $data = [];
            if (ee()->TMPL->fetch_param('form_name', '') != "") {
                $data['name'] = ee()->TMPL->fetch_param('form_name');
            }

            $data['id'] = ee()->TMPL->form_id;
            $data['class'] = ee()->TMPL->form_class;
            $data['enctype'] = 'multi';

            $data['hidden_fields'] = array(
                'RET' => (ee()->TMPL->fetch_param('return', '') != "") ? ee()->functions->create_url(ee()->TMPL->fetch_param('return')) : ee()->functions->fetch_current_uri(),
                'ACT' => ee()->functions->fetch_action_id('Member', 'upload_avatar'));

            return ee()->functions->form_declaration($data) . $template . '</form>';
        }

        // Finalize the template
        return $this->_var_swap(
            $template,
            array(
                'form_declaration' => ee()->functions->form_declaration(
                    array(
                        'action' => $this->_member_path('upload_avatar'),
                        'enctype' => 'multi'
                    )
                ),
                'lang:max_image_size' => $max_size,
                'path:avatar_image' => $cur_avatar_url,
                'avatar_width' => $avatar_width,
                'avatar_height' => $avatar_height
            )
        );
    }

    /**
     * Browse Avatars
     */
    public function browse_avatars()
    {
        // Define the paths and get the images
        $avatar_path = ee()->config->slash_item('avatar_path') . ee()->security->sanitize_filename($this->cur_id) . '/';
        $avatar_url = ee()->config->slash_item('avatar_url') . ee()->security->sanitize_filename($this->cur_id) . '/';

        $avatars = $this->_get_avatars($avatar_path);

        // Did we succeed?
        if (count($avatars) == 0) {
            return $this->_trigger_error('edit_avatar', 'avatars_not_found');
        }

        $template = $this->_load_element('browse_avatars');

        // Check to see if the old style pagination exists
        // @deprecated 2.8
        if (stripos($template, LD . 'if pagination' . RD) !== false) {
            if (stripos($template, LD . 'paginate' . RD) !== false) {
                $template = str_replace('{paginate}', '{pagination_links}', $template);
            }

            $template = preg_replace("/{if pagination}(.*?){\/if}/uis", "{paginate}$1{/paginate}", $template);
            ee()->load->library('logger');
            ee()->logger->developer('{if paginate} has been deprecated, use normal {paginate} tags in your browse avatars template.', true, 604800);
        }

        // Load up pagination and start parsing
        ee()->load->library('pagination');
        $pagination = ee()->pagination->create();
        $pagination->position = 'inline';
        $template = $pagination->prepare($template);

        // Pagination anyone?
        $max_rows = 5;
        $max_cols = 3;
        $per_page = $max_rows * $max_cols;
        $total_rows = count($avatars);

        if ($total_rows > $per_page) {
            $pagination->build($total_rows, $per_page);
            $avatars = array_slice($avatars, $pagination->offset, $pagination->per_page);
        }

        // Build the table rows
        $avstr = '';
        $col_ct = 0;
        foreach ($avatars as $image) {
            if ($col_ct == 0) {
                $avstr .= "<tr>\n";
            }

            $avstr .= "<td align='center'><img src='" . $avatar_url . $image . "' border='0' alt='" . $image . "'/><br /><input type='radio' name='avatar' value='" . $image . "' /></td>\n";
            $col_ct++;

            if ($col_ct == $max_cols) {
                $avstr .= "</tr>";
                $col_ct = 0;
            }
        }

        if ($col_ct < $max_cols and count($avatars) >= $max_cols) {
            for ($i = $col_ct; $i < $max_cols; $i++) {
                $avstr .= "<td>&nbsp;</td>\n";
            }

            $avstr .= "</tr>";
        }

        if (substr($avstr, -5) != '</tr>') {
            $avstr .= "</tr>";
        }

        // Finalize the output
        $base_url = $this->_member_path('browse_avatars/' . $this->cur_id . '/');

        return $this->_var_swap($pagination->render($template), array(
            'form_declaration' => ee()->functions->form_declaration(
                array(
                    'action' => $this->_member_path('select_avatar'),
                    'hidden_fields' => array('referrer' => $base_url, 'folder' => $this->cur_id)
                )
            ),
            'avatar_set' => ucwords(str_replace("_", " ", $this->cur_id)),
            'avatar_table_rows' => $avstr
        ));
    }

    /**
     * Select Avatar From  Library
     */
    public function select_avatar()
    {
        if (ee()->input->get_post('avatar') === false or
            ee()->input->get_post('folder') === false) {
            return ee()->functions->redirect(ee()->input->get_post('referrer'));
        }

        $folder = ee()->security->sanitize_filename(ee()->input->get_post('folder'));
        $file = ee()->security->sanitize_filename(ee()->input->get_post('avatar'));

        $basepath = ee()->config->slash_item('avatar_path');
        $avatar = $avatar = $folder . '/' . $file;

        $allowed = $this->_get_avatars($basepath . $folder);

        if (! in_array($file, $allowed) or $folder == 'upload') {
            return $this->_trigger_error('edit_avatar', 'avatars_not_found');
        }

        // Fetch the avatar meta-data
        if (! function_exists('getimagesize')) {
            return $this->_trigger_error('edit_avatar', 'image_assignment_error');
        }

        $vals = @getimagesize($basepath . $avatar);
        $width = $vals['0'];
        $height = $vals['1'];

        // Update DB
        $member = ee()->session->getMember();
        $member->set(
            array(
                'avatar_filename' => $avatar,
                'avatar_width' => $width,
                'avatar_height' => $height
            )
        );
        $member->validate();
        $member->save();

        return $this->_var_swap(
            $this->_load_element('success'),
            array(
                'lang:heading' => lang('edit_avatar'),
                'lang:message' => lang('avatar_updated')
            )
        );
    }

    /**
     * List all Images in a Folder
     */
    protected function _get_avatars($avatar_path)
    {
        // Is this a valid avatar folder?
        $extensions = array('.gif', '.jpg', '.jpeg', '.png');

        if (! @is_dir($avatar_path) or ! $fp = @opendir($avatar_path)) {
            return array();
        }

        // Grab the image names

        $avatars = array();

        while (false !== ($file = readdir($fp))) {
            if (false !== ($pos = strpos($file, '.'))) {
                if (in_array(substr($file, $pos), $extensions)) {
                    $avatars[] = $file;
                }
            }
        }

        closedir($fp);

        return $avatars;
    }

    /**
     * Upload Avatar or Profile Photo
     */
    public function upload_avatar()
    {
        return $this->_upload_image('avatar');
    }

    /**
     * Upload Photo
     */
    public function upload_photo()
    {
        return $this->_upload_image('photo');
    }

    /**
     * Upload Signature
     */
    public function upload_signature_image()
    {
        return $this->_upload_image('sig_img');
    }

    /**
     * Upload Image
     */
    public function _upload_image($type = 'avatar')
    {
        ee()->load->library('members');

        $upload = ee()->members->upload_member_images($type, ee()->session->userdata('member_id'));

        $return = ee()->input->get_post('RET');

        if (! empty($return)) {
            if (is_numeric($return)) {
                $return_link = ee()->functions->form_backtrack($return);
            } else {
                $return_link = $return;
            }

            // Make sure it's an actual URL.
            if (substr($return_link, 0, 4) !== 'http' && substr($return_link, 0, 1) !== '/') {
                $return_link = '/' . $return_link;
            }
        }

        if (is_array($upload)) {
            switch ($upload[0]) {
                case 'success':
                    $edit_image = $upload[1];
                    $updated = $upload[2];

                    break;
                case 'redirect':
                    return ee()->functions->redirect(!empty($return) ? $return_link : $this->_member_path($upload[1][0]));

                    break;
                case 'var_swap':
                    return $this->_var_swap($this->_load_element($upload[1][0]), $upload[1][1]);

                    break;
                case 'error':
                    return call_user_func_array(array($this, '_trigger_error'), $upload[1]);

                    break;
            }
        }

        if (! empty($return)) {
            ee()->functions->redirect($return_link);
            exit;
        }

        // Success message
        return $this->_var_swap(
            $this->_load_element('success'),
            array(
                'lang:heading' => lang($edit_image),
                'lang:message' => lang($updated)
            )
        );
    }

    /**
     * Photo Edit Form
     */
    public function edit_photo()
    {
        // Are photos enabled?
        if (ee()->config->item('enable_photos') == 'n') {
            return $this->_trigger_error('edit_photo', 'photos_not_enabled');
        }

        // Fetch the photo template
        $template = $this->_load_element('edit_photo');

        // Does the current user have a photo?
        $query = ee()->db->select('photo_filename, photo_width, photo_height')
            ->where('member_id', (int) ee()->session->userdata('member_id'))
            ->get('members');

        if ($query->row('photo_filename') == '') {
            $template = $this->_deny_if('photo', $template);
            $template = $this->_allow_if('no_photo', $template);

            $cur_photo_url = '';
            $photo_width = '';
            $photo_height = '';
        } else {
            $template = $this->_allow_if('photo', $template);
            $template = $this->_deny_if('no_photo', $template);

            $cur_photo_url = ee()->config->slash_item('photo_url') . $query->row('photo_filename') ;
            $photo_width = $query->row('photo_width') ;
            $photo_height = $query->row('photo_height') ;
        }

        // Set the default image meta values
        $max_kb = (ee()->config->item('photo_max_kb') == '' or ee()->config->item('photo_max_kb') == 0) ? 50 : ee()->config->item('photo_max_kb');
        $max_w = (ee()->config->item('photo_max_width') == '' or ee()->config->item('photo_max_width') == 0) ? 100 : ee()->config->item('photo_max_width');
        $max_h = (ee()->config->item('photo_max_height') == '' or ee()->config->item('photo_max_height') == 0) ? 100 : ee()->config->item('photo_max_height');
        $max_size = str_replace('%x', $max_w, lang('max_image_size'));
        $max_size = str_replace('%y', $max_h, $max_size);
        $max_size .= ' - ' . $max_kb . 'KB';

        // Finalize the template
        return $this->_var_swap(
            $template,
            array(
                'form_declaration' => ee()->functions->form_declaration(
                    array(
                        'action' => $this->_member_path('upload_photo'),
                        'enctype' => 'multi'
                    )
                ),
                'lang:max_image_size' => $max_size,
                'path:member_photo' => $cur_photo_url,
                'photo_width' => $photo_width,
                'photo_height' => $photo_height,
                'name' => $query->row('photo_filename')
            )
        );
    }
}
// END CLASS

// EOF
