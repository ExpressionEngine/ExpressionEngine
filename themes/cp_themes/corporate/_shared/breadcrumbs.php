<div id="breadCrumb">

		<ol>
			<li><?=$cp_current_site_label?></li>
			<?php
			if ($this->input->get('C') !== FALSE && $this->input->get('C') !== 'homepage'):
			?>
			<li>&#47;&nbsp;&nbsp;&nbsp;<a href="<?=BASE?>"><?=lang('main_menu')?></a></li>
			<?php
			endif;
		
			foreach($cp_breadcrumbs as $link => $title):
		
			echo '<li>&#47;&nbsp;&nbsp;&nbsp;<a href="'.$link.'">'.$title."</a></li>\n";
		
			endforeach;
			?>
		
			<li class="last">&#47;&nbsp;&nbsp;&nbsp;<?=$cp_page_title?></li>
		</ol>
		<div class="clear_left"></div>

</div>