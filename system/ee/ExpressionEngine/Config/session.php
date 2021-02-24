<?php
/*
|--------------------------------------------------------------------------
| Session Variables
|--------------------------------------------------------------------------
|
| 'session_cookie_name' = the name you want for the cookie
| 'encrypt_sess_cookie' = TRUE/FALSE (boolean).  Whether to encrypt the cookie
| 'session_expiration'  = the number of SECONDS you want the session to last.
|  by default sessions last 7200 seconds (two hours).  Set to zero for no expiration.
| 'time_to_update' = how many seconds between CI refreshing Session Information
|
*/

return array(
    'sess_cookie_name' => 'ci_session',
    'sess_expiration' => 7200,
    'sess_encrypt_cookie' => false,
    'sess_use_database' => false,
    'sess_table_name' => 'ci_sessions',
    'sess_match_ip' => false,
    'sess_match_useragent' => true,
    'sess_time_to_update' => 300
);

// EOF
