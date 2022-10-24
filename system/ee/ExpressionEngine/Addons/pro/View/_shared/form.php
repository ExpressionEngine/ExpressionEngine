
  	<?php
    $form_class = '';
    if (isset($ajax_validate) && $ajax_validate == true) {
        $form_class .= 'ajax-validate';
    }
    $attributes = 'class="' . $form_class . '"';
    if (isset($has_file_input) && $has_file_input == true) {
        $attributes .= ' enctype="multipart/form-data"';
    }
    if (! isset($alerts_name)) {
        $alerts_name = 'shared-form';
    }
    ?>
  	<?=form_open($base_url, $attributes, (isset($form_hidden)) ? $form_hidden : array())?>

    		<?php if (isset($tabs)):?>
    			<?php $active_tab = (isset($active_tab)) ? $active_tab : 0; ?>
    			<div class="tab-wrap">
    				<div class="tab-bar">
    					<div class="tab-bar__tabs">
    					<?php
                            foreach (array_keys($tabs) as $i => $name):
                                $class = '';
                                if ($i == $active_tab) {
                                    $class = 'active';
                                }

                                if (strpos($tabs[$name], 'class="ee-form-error-message"') !== false) {
                                    $class .= ' invalid';
                                }
                            ?>
    						<button type="button" class="js-tab-button tab-bar__tab <?=$class?>" rel="t-<?=$i?>"><?=lang($name)?></button>
    					<?php endforeach; ?>
    					</div>
    				</div>
    		<?php endif; ?>

    			<?=ee('CP/Alert')->get($alerts_name)?>
    			<?php
                if (isset($extra_alerts)) {
                    foreach ($extra_alerts as $alert) {
                        echo ee('CP/Alert')->get($alert);
                    }
                }
                if (isset($tabs)):
                    foreach (array_values($tabs) as $i => $html):
                ?>
    				<div class="tab t-<?=$i?><?php if ($i == $active_tab) {
                    echo ' tab-open';
                }?>"><?=$html?></div>
    			<?php
                    endforeach;
                endif;

                $secure_form_ctrls = array();

                if (isset($sections['secure_form_ctrls'])) {
                    $secure_form_ctrls = $sections['secure_form_ctrls'];
                    unset($sections['secure_form_ctrls']);
                }
                foreach ($sections as $name => $settings) {
                    $this->embed('ee:_shared/form/section', array('name' => $name, 'settings' => $settings));
                }
                ?>
          <?php foreach ($secure_form_ctrls as $setting):
            $this->embed('ee:_shared/form/fieldset', ['setting' => $setting, 'group' => false]); ?>
          <?php endforeach ?>



		<?php if (isset($tabs)):?>
			</div>
		<?php endif; ?>


  	</form>
