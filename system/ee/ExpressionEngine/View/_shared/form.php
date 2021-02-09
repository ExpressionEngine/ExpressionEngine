<div class="panel">
  <div class="form-standard">
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
      <div class="panel-heading">
        <div class="form-btns form-btns-top">
    			<div class="title-bar title-bar--large">
    				<h3 class="title-bar__title"><?=ee('Format')->make('Text', (isset($cp_page_title_alt)) ? $cp_page_title_alt : $cp_page_title)->attributeSafe()->compile()?></h3>

    			<div class="title-bar__extra-tools">
    			<?php if (isset($action_button)):
                    $rel = isset($action_button['rel']) ? $action_button['rel'] : ''; ?>
    				<a class="button button--primary" href="<?=$action_button['href']?>" rel="<?=$rel?>"><?=lang($action_button['text'])?></a>
    			<?php elseif (! isset($hide_top_buttons) or ! $hide_top_buttons): ?>
    				<?php $this->embed('ee:_shared/form/buttons'); ?>
    			<?php endif ?>
    			</div>
    			</div>
    		</div>
      </div>
      <div class="panel-body">
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
                    $this->embed('_shared/form/section', array('name' => $name, 'settings' => $settings));
                }
                ?>
          <?php foreach ($secure_form_ctrls as $setting):
            $this->embed('ee:_shared/form/fieldset', ['setting' => $setting, 'group' => false]); ?>
          <?php endforeach ?>
        </div>



		<?php if (isset($tabs)):?>
			</div>
		<?php endif; ?>

    <div class="panel-footer">


      <div class="form-btns">
        <?php $this->embed('ee:_shared/form/buttons'); ?>
      </div>
    </div>

  	</form>
  </div>
</div>
