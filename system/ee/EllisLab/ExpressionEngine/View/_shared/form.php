<div class="form-standard">
	<?php
	$form_class = '';
	if (isset($ajax_validate) && $ajax_validate == TRUE)
	{
		$form_class .= 'ajax-validate';
	}
	$attributes = 'class="'.$form_class.'"';
	if (isset($has_file_input) && $has_file_input == TRUE)
	{
		$attributes .= ' enctype="multipart/form-data"';
	}
	if ( ! isset($alerts_name))
	{
		$alerts_name = 'shared-form';
	}
	?>
	<?=form_open($base_url, $attributes, (isset($form_hidden)) ? $form_hidden : array())?>
		<div class="form-btns form-btns-top">
			<div class="title-bar title-bar--large">
				<h1 class="title-bar__title"><?=ee('Format')->make('Text', (isset($cp_page_title_alt)) ? $cp_page_title_alt : $cp_page_title)->attributeSafe()->compile()?></h1>

			<div class="title-bar__extra-tools">
			<?php if (isset($action_button)):
				$rel = isset($action_button['rel']) ? $action_button['rel'] : ''; ?>
				<a class="button button--action" href="<?=$action_button['href']?>" rel="<?=$rel?>"><?=lang($action_button['text'])?></a>
			<?php elseif ( ! isset($hide_top_buttons) OR ! $hide_top_buttons): ?>
				<?php $this->embed('ee:_shared/form/buttons'); ?>
			<?php endif ?>
			</div>
			</div>
		</div>
		<?php if (isset($tabs)):?>
			<?php $active_tab = (isset($active_tab)) ? $active_tab : 0; ?>
			<div class="tab-wrap">
				<div class="tab-bar">
					<div class="tab-bar__tabs">
					<?php
						foreach (array_keys($tabs) as $i => $name):
							$class = '';
							if ($i == $active_tab)
							{
								$class = 'active';
							}

							if (strpos($tabs[$name], 'class="ee-form-error-message"') !== FALSE)
							{
								$class .= ' invalid';
							}
						?>
						<a class="js-tab-button tab-bar__tab <?=$class?>" href="" rel="t-<?=$i?>"><?=lang($name)?></a>
					<?php endforeach; ?>
					</div>
				</div>
		<?php endif; ?>

			<?=ee('CP/Alert')->get($alerts_name)?>
			<?php
			if (isset($extra_alerts))
			{
				foreach ($extra_alerts as $alert)
				{
					echo ee('CP/Alert')->get($alert);
				}
			}
			if (isset($tabs)):
				foreach (array_values($tabs) as $i => $html):
			?>
				<div class="tab t-<?=$i?><?php if ($i == $active_tab) echo ' tab-open'?>"><?=$html?></div>
			<?php
				endforeach;
			endif;

			$secure_form_ctrls = array();

			if (isset($sections['secure_form_ctrls']))
			{
				$secure_form_ctrls = $sections['secure_form_ctrls'];
				unset($sections['secure_form_ctrls']);
			}
			foreach ($sections as $name => $settings)
			{
				$this->embed('_shared/form/section', array('name' => $name, 'settings' => $settings));
			}
			?>

			<div class="form-btns<?php if (count($secure_form_ctrls)): ?> form-btns-auth<?php endif ?>">
				<?php foreach ($secure_form_ctrls as $setting):
					$this->embed('ee:_shared/form/fieldset', ['setting' => $setting, 'group' => FALSE]); ?>
				<?php endforeach ?>

				<?php $this->embed('ee:_shared/form/buttons'); ?>
			</div>

		<?php if (isset($tabs)):?>
		</div>
		<?php endif; ?>
	</form>
</div>
