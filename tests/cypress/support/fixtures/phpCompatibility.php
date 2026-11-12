<?php
// Isolated content for installed-site PHP compatibility browser tests.
$root = realpath(__DIR__ . '/../../../..');
$_SERVER['DOCUMENT_ROOT'] = $root;
require __DIR__ . '/bootstrap.php';

function compatibilityModel($type, $key, $value, array $data) {
    $model = ee('Model')->get($type)->filter($key, $value)->first();
    if (!$model) {
        $model = ee('Model')->make($type, array_merge($data, array($key => $value)));
        $model->save();
    }
    return $model;
}

$captchaMode = getenv('PHP_COMPATIBILITY_CAPTCHA') ?: '';
ee()->config->update_site_prefs(array(
    'require_captcha' => $captchaMode ? 'y' : 'n',
    'captcha_require_members' => $captchaMode ? 'y' : 'n',
    'use_recaptcha' => 'n', 'captcha_font' => $captchaMode === 'truetype' ? 'y' : 'n',
    'captcha_path' => $root . '/images/uploads/php-compatibility/captcha/',
    'captcha_url' => '/images/uploads/php-compatibility/captcha/'
), 1);
$field = compatibilityModel('ChannelField', 'field_name', 'compat_choice', array(
    'site_id' => 1, 'field_label' => 'Compatibility choice', 'field_type' => 'select',
    'field_list_items' => "Red\nGreen", 'field_required' => 'n', 'field_search' => 'y',
    'field_fmt' => 'none', 'legacy_field_data' => 'n'
));
$channel = compatibilityModel('Channel', 'channel_name', 'compatibility', array(
    'site_id' => 1, 'channel_title' => 'PHP Compatibility', 'deft_status' => 'open'
));
$categories = compatibilityModel('CategoryGroup', 'group_name', 'PHP Compatibility', array('site_id' => 1, 'sort_order' => 'a'));
$news = compatibilityModel('Category', 'cat_url_title', 'compat-news', array('site_id' => 1, 'group_id' => $categories->group_id, 'cat_name' => 'News', 'parent_id' => 0, 'cat_order' => 1));
$personal = compatibilityModel('Category', 'cat_url_title', 'compat-personal', array('site_id' => 1, 'group_id' => $categories->group_id, 'cat_name' => 'Personal', 'parent_id' => 0, 'cat_order' => 2));
$author = ee('Model')->get('Member')->filter('role_id', 1)->first();
$channel->CustomFields = array($field);
$channel->CategoryGroups = array($categories);
$channel->Statuses = array(ee('Model')->get('Status', 1)->first());
// Exercise the controller's unsaved Channel Form settings/default author.
ee('Model')->get('ChannelFormSettings')->filter('channel_id', $channel->channel_id)->delete();
$channel->save();
$entry = compatibilityModel('ChannelEntry', 'url_title', 'compatibility-original', array(
    'site_id' => 1, 'channel_id' => $channel->channel_id, 'author_id' => $author->member_id,
    'title' => 'Compatibility original', 'status' => 'open', 'status_id' => 1,
    'entry_date' => time() - 60, 'allow_comments' => 'n'
));
$entry->entry_date = time() - 1;
$entry->{'field_id_' . $field->field_id} = 'Green';
$entry->Categories = array($news);
$entry->save();
$group = compatibilityModel('TemplateGroup', 'group_name', 'compatibility', array('site_id' => 1, 'is_site_default' => 'n'));

$form = <<<'HTML'
{exp:channel:form channel="compatibility" return="compatibility/saved/ENTRY_ID" error_handling="inline" ENTRY_ATTRIBUTE}
<label>Title <input name="title" value="{title}"></label>
<label>URL <input name="url_title" value="{url_title}"></label>
<select name="compat_choice" id="choice">{options:compat_choice}<option value="{option_value}"{selected}>{option_name}</option>{/options:compat_choice}</select>
{category_menu}<select id="categories" name="category[]" multiple>{select_options}</select>{/category_menu}
<div id="errors">{global_errors}{error}{/global_errors}{field_errors}{error}{/field_errors}</div>
{if captcha}<div id="captcha">{captcha}<input name="captcha" value="{captcha_word}"></div>{/if}
<input type="submit" value="Save compatibility entry">
{/exp:channel:form}
HTML;
// Exercise the legacy helper in an isolated PHP-enabled template.
$legacyCaptcha = <<<'HTML'
<?php
ee()->load->helper('captcha');
$captcha = create_captcha(array(
    'word' => 'example', 'img_path' => ee()->config->slash_item('captcha_path', 1),
    'img_url' => ee()->config->slash_item('captcha_url'), 'img_width' => 153, 'img_height' => 31,
    'font_path' => ee()->input->get('font') === 'truetype' ? APPPATH . 'fonts/texb.ttf' : ''
));
echo '<div id="legacy-captcha">' . $captcha['image'] . '</div>';
?>
HTML;
// Exercise runtime services through an installed-site request.
$runtimeServices = <<<'HTML'
<?php
$link = new \ExpressionEngine\Service\CustomMenu\Link('Compatibility', 'https://example.test/');
$request = new \ExpressionEngine\Library\Curl\GetRequest('https://example.test/', (object) array('q' => 'a b'));
ee()->load->helper('file');
require_once BASEPATH . 'libraries/template_router/Route.php';
$route = new EE_Route('/articles/{category:alpha_dash}/{entry:alpha_dash}', true);
echo '<div id="route-url">' . $route->build(array('category' => 'news', 'entry' => 'example')) . '</div>';
echo '<div id="menu-url">' . htmlspecialchars($link->url) . '</div>';
echo '<div id="query-url">' . htmlspecialchars($request->config[CURLOPT_URL]) . '</div>';
echo '<div id="invalid-file">' . (get_file_info("invalid\0path") === false ? 'rejected' : 'unexpected') . '</div>';
$circular = array('q' => 'value');
$circular['self'] = &$circular;
try {
    $request = new \ExpressionEngine\Library\Curl\PostRequest('https://example.test/', $circular);
    $result = $request->config[CURLOPT_POSTFIELDS];
} catch (\InvalidArgumentException $e) {
    $result = $e->getMessage();
}
echo '<div id="circular-query" data-php-version="' . PHP_VERSION_ID . '">' . htmlspecialchars($result) . '</div>';

?>
HTML;
$progressTemplate = <<<'HTML'
<?php
ee()->load->library('progress');
$step = ee()->input->get('step');
if ($step === 'write') {
    ee()->progress->update_state('Compatibility progress');
} elseif ($step === 'clear') {
    ee()->progress->clear_state();
} else {
    echo '<div id="progress">' . (ee()->progress->get_state() ?: 'empty') . '</div>';
}
?>
HTML;

$feedBaseUrl = rtrim(getenv('PHP_COMPATIBILITY_FEED_BASE_URL') ?: ee()->config->item('base_url'), '/') . '/';
$templates = array(
    'services' => $runtimeServices,
    'progress' => $progressTemplate,
    'tags' => '<div id="query-result">{exp:query sql="SELECT \'Query compatibility\' AS result"}{result}{/exp:query}</div><div id="query-empty">{exp:query sql="SELECT 1 AS result WHERE 1=0"}{if no_results}No query results{/if}{/exp:query}</div><div id="markdown">{exp:markdown}# Markdown compatibility{/exp:markdown}</div>',
    'legacy-captcha' => $legacyCaptcha,
    'new' => str_replace('ENTRY_ATTRIBUTE', '', $form),
    'edit' => str_replace('ENTRY_ATTRIBUTE', 'entry_id="' . $entry->entry_id . '"', $form),
    'saved' => '{exp:channel:entries channel="compatibility" entry_id="{segment_3}" dynamic="no"}<h1>{title}</h1><p id="choice">{compat_choice}</p>{categories}<p class="category">{category_name}</p>{/categories}{/exp:channel:entries}',
    'categories' => '<div id="heading">{exp:channel:category_heading category_id="' . $news->cat_id . '"}{category_name}{/exp:channel:category_heading}</div><div id="empty-heading">{exp:channel:category_heading channel="compatibility"}{category_name}{/exp:channel:category_heading}</div>{exp:channel:entries channel="compatibility" dynamic="no"}<p class="entry">{title}</p>{/exp:channel:entries}',
    'feed' => '{exp:rss_parser url="' . $feedBaseUrl . 'images/uploads/php-compatibility/feed.xml?fixture=' . time() . '" refresh="0"}<h1>{feed_title}</h1>{feed_items}<h2>{item_title}</h2><p>{item_description}</p>{/feed_items}{if feed_error}<p id="feed-error">{feed_error}</p>{/if}{/exp:rss_parser}',
    'search' => '{exp:pro_search:form result_page="compatibility/results" channel="compatibility" form_id="search"}<input name="keywords"><button type="submit">Search</button>{/exp:pro_search:form}',
    'results' => '{exp:pro_search:results query="{segment_3}" channel="compatibility" require_query="no"}<p class="result">{title}</p>{if no_results}<p id="no-results">No results</p>{/if}{/exp:pro_search:results}'
);
foreach ($templates as $name => $content) {
    $template = ee('Model')->get('Template')->filter('group_id', $group->group_id)->filter('template_name', $name)->first();
    if (!$template) {
        $template = ee('Model')->make('Template', array('site_id' => 1, 'group_id' => $group->group_id, 'template_name' => $name, 'template_type' => 'webpage', 'template_engine' => 'ee', 'allow_php' => 'n', 'cache' => 'n', 'last_author_id' => $author->member_id));
    }
    $template->allow_php = in_array($name, array('legacy-captcha', 'services', 'progress'), true) ? 'y' : 'n';
    $template->php_parse_location = 'i';
    $template->template_data = '<!doctype html><html><head><title>PHP compatibility</title></head><body>' . $content . '</body></html>';
    $template->save();
}
ee()->load->library('addons');
if (!ee('Model')->get('Module')->filter('module_name', 'Pro_search')->count()) {
    ee()->addons->install_modules(array('pro_search'));
}
compatibilityModel('Plugin', 'plugin_package', 'rss_parser', array(
    'plugin_name' => 'RSS Parser', 'plugin_version' => ee('Addon')->get('rss_parser')->getVersion(),
    'is_typography_related' => 'n'
));

if (!ee('Model')->get('Module')->filter('module_name', 'Query')->count()) {
    ee()->addons->install_modules(array('query'));
}
compatibilityModel('Plugin', 'plugin_package', 'markdown', array(
    'plugin_name' => 'Markdown', 'plugin_version' => ee('Addon')->get('markdown')->getVersion(),
    'is_typography_related' => 'y'
));

$folder = $root . '/images/uploads/php-compatibility/';
// The browser server and fixture runner may use different container users.
if (!is_dir($folder)) { mkdir($folder, 0777, true); }
chmod($folder, 0777);
if (!is_dir($folder . 'captcha')) { mkdir($folder . 'captcha', 0777); }
chmod($folder . 'captcha', 0777);
file_put_contents($folder . 'feed.xml', '<?xml version="1.0"?><rss version="2.0"><channel><title>Compatibility feed</title><link>http://example.test/</link><description>Local fixture</description><item><title>Feed item</title><link>http://example.test/item</link><description>Supplementary character: &#128512;</description><guid>compatibility</guid></item></channel></rss>');
chmod($folder . 'feed.xml', 0666);
$image = imagecreatetruecolor(120, 80);
imagefill($image, 0, 0, imagecolorallocate($image, 30, 140, 210));
imagepng($image, $folder . 'browser.png');
chmod($folder . 'browser.png', 0666);
// Match the web runtime's cache owner when the fixture runner is root.
if (function_exists('posix_geteuid') && posix_geteuid() === 0) {
    $owner = fileowner($root . '/system/user/cache');
    chown($folder, $owner);
    chown($folder . 'captcha', $owner);
    chown($folder . 'browser.png', $owner);
    chown($folder . 'feed.xml', $owner);
}

if (PHP_VERSION_ID < 80000) { imagedestroy($image); }
unset($image);
$destination = compatibilityModel('UploadDestination', 'name', 'Compatibility', array(
    'site_id' => 1, 'adapter' => 'local', 'server_path' => $folder,
    'url' => '{base_url}images/uploads/php-compatibility/', 'allowed_types' => 'img'
));
$destination->server_path = $folder;
$destination->url = '{base_url}images/uploads/php-compatibility/';
$destination->save();
$file = ee('Model')->get('File')->filter('upload_location_id', $destination->id)->filter('file_name', 'browser.png')->first();
if (!$file) {
    $file = ee('Model')->make('File', array(
        'site_id' => 1, 'title' => 'Compatibility image', 'upload_location_id' => $destination->id,
        'directory_id' => 0, 'file_name' => 'browser.png', 'mime_type' => 'image/png', 'file_type' => 'img',
        'uploaded_by_member_id' => $author->member_id, 'upload_date' => time(), 'modified_date' => time()
    ));
}
$file->file_hw_original = '80 120';
$file->file_size = filesize($folder . 'browser.png');
$file->save();
echo json_encode(array('news_id' => $news->cat_id, 'personal_id' => $personal->cat_id, 'channel_id' => $channel->channel_id, 'field_id' => $field->field_id, 'entry_id' => $entry->entry_id, 'file_id' => $file->file_id, 'upload_id' => $destination->id, 'php' => PHP_VERSION)) . "\n";
