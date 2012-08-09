<?php
if ($can_admin_sites)
{
	extend_template('default');
}
else
{
	extend_template('default', 'ee_right_nav');
}

$this->table->set_heading(lang('choose_site'));

foreach ($sites as $site_id => $site_name)
{
	$this->table->add_row(
		'<a href="'.BASE.AMP."C=sites".AMP."site_id=".$site_id.'">'.$site_name.'</a>'
	);
}

echo $this->table->generate();