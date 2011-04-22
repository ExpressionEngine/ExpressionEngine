<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

//field specific options
$config['safecracker_option_fields'][] = 'pt_checkboxes';
$config['safecracker_option_fields'][] = 'pt_radio_buttons';
$config['safecracker_option_fields'][] = 'pt_dropdown';
$config['safecracker_option_fields'][] = 'pt_multiselect';
$config['safecracker_option_fields'][] = 'pt_pill';
$config['safecracker_file_fields'][] = 'simple_s3_uploader';
$config['safecracker_require_save_call']['playa'] = TRUE;
//$config['safecracker_field_extra_js']['playa'] = "$('.filter').filter('.search').height(18).find('input, label').height(18);$('.filter label span span').css({'font-family':'Helvetica, Arial, sans-serif', 'font-size':'12px'});";
$config['safecracker_field_extra_js']['iain_wymeditor'] = "$('.wymeditor_product_text').parents('form').find('input[type=submit]').addClass('wymupdate');";
$config['safecracker_post_error_callbacks']['nsm_tiny_mce'] = 'html_entity_decode';


//a temporary fix until ellislab clears this bug, where filemanager is out of place, may not be necessary with new filemanager?
$config['safecracker_field_extra_js']['file'] = '$(".choose_file").click(function(){$(".ui-dialog").css({position:"absolute",top:($(window).height()-$(".ui-dialog").height())/2,left:($(window).width()-$(".ui-dialog").width())/2});$(window).scrollTop(0);});';

/* End of file config.php */
/* Location: ./system/expressionengine/config/safecracker/config.php */