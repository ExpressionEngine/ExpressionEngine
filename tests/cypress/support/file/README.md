# Installation Pitfalls

You need to install QT:

    brew install qt5
    brew link --force qt5

## Upgrading to QT 5.0

If you previously used QT 4.0 (`brew install qt`) and want to upgrade to 5.0 to remove the deprecation notices, you'll have to uninstall or unlink QT 4.0:

    brew unlink qt
    
    OR

    brew uninstall qt

# Testing with your main installation

- Create a test db
- Follow the directions in config.rb to tell rspec where it is
- Add a new database group 'rspec' in database.php that points to
  the test db
- Conditionally flip the active group for fixture and capybara
  requests:

```php
if (strpos($_SERVER['HTTP_USER_AGENT'], 'capybara') !== FALSE  || defined('FIXTURE'))
{
    $active_group = 'rspec';
}
```

- If your install is not at "http://ee2/", then override all paths by
  updating the first two lines here and copying it to config.php:

```php
$base_url                      = 'http://whatever';
$base_path                     = '/dev/null';
$system_folder                 = APPPATH . '../';
$images_folder                 = 'images';
$images_path                   = $base_path . '/' . $images_folder;
$images_url                    = $base_url . '/' . $images_folder;
$config['index_page']          = '';
$config['site_index']          = '';
$config['base_url']            = $base_url . '/';
$config['site_url']            = $config['base_url'];
$config['cp_url']              = $config['base_url'] . 'admin.php';
$config['theme_folder_path']   = $base_path   . '/themes/';
$config['theme_folder_url']    = $base_url    . '/themes/';
$config['emoticon_path']       = $images_url  . '/smileys/';
$config['emoticon_url']        = $images_url  . '/smileys/';
$config['captcha_path']        = $images_path . '/captchas/';
$config['captcha_url']         = $images_url  . '/captchas/';
$config['avatar_path']         = $images_path . '/avatars/';
$config['avatar_url']          = $images_url  . '/avatars/';
$config['photo_path']          = $images_path . '/member_photos/';
$config['photo_url']           = $images_url  . '/member_photos/';
$config['sig_img_path']        = $images_path . '/signature_attachments/';
$config['sig_img_url']         = $images_url  . '/signature_attachments/';
$config['prv_msg_upload_path'] = $images_path . '/pm_attachments/';
```

