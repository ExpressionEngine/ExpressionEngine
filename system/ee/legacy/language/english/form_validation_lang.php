<?php

$lang['alpha']				= "This field may only contain alphabetical characters.";
$lang['alpha_dash']			= "This field may only contain alpha-numeric characters, underscores, and dashes.";
$lang['alpha_dash_space']	= "This field may only contain alpha-numeric characters, underscores, dashes, and spaces.";
$lang['alpha_dash_period']	= "This field may only contain alpha-numeric characters, underscores, dashes, and periods.";
$lang['alpha_numeric']		= "This field may only contain alpha-numeric characters.";
$lang['boolean']			= "This field must be a boolean value.";
$lang['enum']				= "This field must be one of: %s.";
$lang['limithtml']			= "This field can only contain the following HTML tags: %s. If you want to use angle brackets < in your text, but not HTML please try &amp;lt; to replace < and &amp;gt; to replace >.";
$lang['exact_length']		= "This field must be exactly %s characters in length.";
$lang['hex_color']			= "This field must contain a valid hex color code.";
$lang['integer']			= "This field must contain an integer.";
$lang['is_natural']			= "This field must contain only positive numbers.";
$lang['is_natural_no_zero']	= "This field must contain a number greater than zero.";
$lang['is_numeric']			= "This field must contain only numeric characters.";
$lang['matches']			= "This field does not match the %s field.";
$lang['max_length']			= "This field cannot exceed %s characters in length.";
$lang['min_length']			= "This field must be at least %s characters in length.";
$lang['numeric']			= "This field must contain only numbers.";
$lang['greater_than']		= "This field must be greater than: %s";
$lang['less_than']			= "This field must be less than: %s";
$lang['regex']				= "This field must match the regular expression `%s`.";
$lang['required']			= "This field is required.";
$lang['unique']				= "This field must be unique.";
$lang['valid_base64']		= "This field may only contain characters in the base64 character set (alpha-numeric, slash, plus, and equals).";
$lang['valid_email']		= "This field must contain a valid email address.";
$lang['unique_email']		= "This field must contain a unique email address.";
$lang['valid_emails']		= "This field must contain all valid email addresses.";
$lang['valid_ip']			= "This field must contain a valid IP.";
$lang['valid_url']			= "This field must contain a valid URL.";
$lang['invalid_xss_check']  = 'The data you submitted did not pass our security check. If you did not intend to submit this form, please <a href="%s">click here</a> and no settings will be changed.';
$lang['no_html']  			= 'This field cannot contain HTML.';
$lang['invalid_path']		= 'This path is either invalid or not writable.';

// Legacy form validation lib
$lang['file_exists']		= $lang['invalid_path'];
$lang['writable']			= $lang['invalid_path'];

// special and legacy things
$lang['isset']				= "The %s field must have a value.";
$lang['auth_password']		= "The password entered is incorrect.";

// EOF
