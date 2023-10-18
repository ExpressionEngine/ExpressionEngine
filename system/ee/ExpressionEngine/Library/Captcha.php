<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Library;

/**
 * CAPTCHA
 */
class Captcha
{
    public $cached_captcha;

    /**
     * Returns a boolean indicating if a CAPTCHA should be displayed or not
     * according to the site's CAPTCHA settings
     *
     * @return	boolean
     */
    public function shouldRequireCaptcha()
    {
        return bool_config_item('require_captcha') &&
            (
                !
                // The only case we don't need to show captcha is if the
                // member is logged in but captcha_require_members is off
                (! bool_config_item('captcha_require_members') &&
                    ee()->session->userdata('member_id') != 0)
            );
    }

    /**
     * Generate CAPTCHA
     *
     * @param	string	$old_word	Word to make CAPTCHA image out of
     * @param	boolean	$force_word	Boolean to skip CAPTCHA creation
     * @return	string	HTML of image tag referencing CAPTCHA
     */
    public function create($old_word = '', $force_word = false)
    {
        if (ee()->config->item('use_recaptcha') == 'y') {
            $key = ee()->config->item('recaptcha_site_key');
            $secret = ee()->config->item('recaptcha_site_secret');
            $action_id = ee()->functions->fetch_action_id('member', 'recaptcha_check');

            $js = "<script>var eeRecaptchaEndpoint ='" . ee()->functions->fetch_site_index(0, 0) . QUERY_MARKER . 'ACT=' . $action_id . "'; var eeRecaptchaKey ='" . $key . "'; </script>";
            $js .= "<script async src=\"https://www.google.com/recaptcha/api.js?render=" . $key . "\"></script>";
            $js .= '<script type="text/javascript" src="' . URL_THEMES_GLOBAL_ASSET . 'javascript/' . PATH_JS . '/recaptcha.js"></script>';
            $js .= "<input type=\"hidden\" name=\"captcha\" style=\"display: none\" id=\"eeReCaptcha\" value=\"\">";
            return $js;
        }

        // If not using ReCAPTCHA, then
        if (ee()->config->item('captcha_require_members') == 'n' &&
            ee()->session->userdata['member_id'] != 0 &&
            $force_word == false) {
            return '';
        }

        // -------------------------------------------
        // 'create_captcha_start' hook.
        //  - Allows rewrite of how CAPTCHAs are created
        //
        if (ee()->extensions->active_hook('create_captcha_start') === true) {
            $edata = ee()->extensions->call('create_captcha_start', $old_word);
            if (ee()->extensions->end_script === true) {
                return $edata;
            }
        }
        // -------------------------------------------

        $img_path = ee()->config->slash_item('captcha_path', 1);
        $img_url = ee()->config->slash_item('captcha_url');
        $use_font = (ee()->config->item('captcha_font') == 'y') ? true : false;

        $font_face = "texb.ttf";
        $font_size = 16;

        $expiration = 60 * 60 * 2;  // 2 hours

        $img_width = 140;	// Image width
        $img_height = 30;	// Image height

        if ($img_path == '' or
            $img_url == '' or
            ! @is_dir($img_path) or
            ! is_really_writable($img_path) or
            ! extension_loaded('gd')) {
            return false;
        }

        if (substr($img_url, -1) != '/') {
            $img_url .= '/';
        }

        // Remove old images - add a bit of randomness so we aren't doing this every page access
        $now = microtime(true);

        if ((mt_rand() % 100) < 5) {
            ee('Model')->get('Captcha')
                ->filter('date', '<', time() - $expiration)->delete();

            $current_dir = @opendir($img_path);

            while ($filename = @readdir($current_dir)) {
                if ($filename != "." and $filename != ".." and $filename != "index.html") {
                    $name = str_replace(".jpg", "", $filename);

                    if (($name + $expiration) < microtime(true)) {
                        @unlink($img_path . $filename);
                    }
                }
            }

            @closedir($current_dir);
        }

        // Fetch and insert word
        if ($old_word == '') {
            $words = ee()->config->loadFile('captcha');
            $word = $words[array_rand($words)];

            if (ee()->config->item('captcha_rand') == 'y') {
                $word .= random_string('nozero', 2);
            }

            $captcha = ee('Model')->make('Captcha');
            $captcha->date = ee()->localize->now;
            $captcha->ip_address = ee()->input->ip_address();
            $captcha->word = $word;
            $captcha->save();
        } else {
            $word = $old_word;
        }

        $this->cached_captcha = $word;

        // Determine angle and position
        $length = strlen($word);
        $angle = ($length >= 6) ? rand(-($length - 6), ($length - 6)) : 0;
        $x_axis = rand(6, (int) (360 / $length) - 16);
        $y_axis = ($angle >= 0) ? rand($img_height, $img_width) : rand(6, $img_height);

        // Create image
        $im = ImageCreate($img_width, $img_height);

        // Assign colors
        $bg_color = ImageColorAllocate($im, 255, 255, 255);
        $border_color = ImageColorAllocate($im, 153, 102, 102);
        $text_color = ImageColorAllocate($im, 204, 153, 153);
        $grid_color = imagecolorallocate($im, 255, 182, 182);
        $shadow_color = imagecolorallocate($im, 255, 240, 240);

        // Create the rectangle
        ImageFilledRectangle($im, 0, 0, $img_width, $img_height, $bg_color);

        // Create the spiral pattern
        $theta = 1;
        $thetac = 6;
        $radius = 12;
        $circles = 20;
        $points = 36;

        for ($i = 0; $i < ($circles * $points) - 1; $i++) {
            $theta = $theta + $thetac;
            $rad = $radius * ($i / $points);
            $x = ($rad * cos($theta)) + $x_axis;
            $y = ($rad * sin($theta)) + $y_axis;
            $theta = $theta + $thetac;
            $rad1 = $radius * (($i + 1) / $points);
            $x1 = ($rad1 * cos($theta)) + $x_axis;
            $y1 = ($rad1 * sin($theta)) + $y_axis;
            imageline($im, (int) $x, (int) $y, (int) $x1, (int) $y1, $grid_color);
            $theta = $theta - $thetac;
        }

        //imageline($im, $img_width, $img_height, 0, 0, $grid_color);

        // Write the text
        $font_path = APPPATH . 'fonts/' . $font_face;

        if ($use_font == true) {
            if (! file_exists($font_path)) {
                $use_font = false;
            }
        }

        if ($use_font == false or ! function_exists('imagettftext')) {
            $font_size = 5;
            ImageString($im, $font_size, $x_axis, $img_height / 3.8, $word, $text_color);
        } else {
            imagettftext($im, $font_size, $angle, $x_axis, $img_height / 1.5, $text_color, $font_path, $word);
        }

        // Create the border
        imagerectangle($im, 0, 0, $img_width - 1, $img_height - 1, $border_color);

        // Generate the image
        $img_name = $now . '.jpg';

        ImageJPEG($im, $img_path . $img_name);

        $img = "<img src=\"$img_url$img_name\" width=\"$img_width\" height=\"$img_height\" style=\"border:0;\" alt=\" \" />";

        ImageDestroy($im);

        return $img;
    }
}

// EOF
