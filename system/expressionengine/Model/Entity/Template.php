<?PHP
namespace EllisLab\ExpressionEngine\Model\Entity;

use EllisLab\ExpressionEngine\Model\Entity\Entity as Entity;

class TemplateEntity extends Entity {
	// Structural definition stuff
	protected $id_name = 'template_id';
	protected $table_name = 'exp_templates';
	protected $relations = array(
		'site_id' => 'SiteEntity',
		'group_id' => 'TemplateGroupEntity',
		'last_author_id' => 'MemberEntity'
	);

	// Properties
	public $template_id; 
	public $site_id;
	public $group_id; 
	public $template_name;
	public $save_template_file;
	public $template_type;
	public $template_data;
	public $template_notes;
	public $edit_date;
	public $last_author_id;
	public $cache;
	public $refresh;
	public $no_auth_bounce;
	public $enable_http_auth;
	public $allow_php;
	public $php_parse_location;
	public $hits;


}
