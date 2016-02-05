			<section class="product-bar <?php if ( ! empty($version_identifier)): ?>pre-release<?php endif ?>">
				<div class="snap">
					<div class="left">
						<?php
						$ver_title = lang('about_expressionengine');
						if (isset($new_version))
						{
							$ver_title = lang('out_of_date_upgrade');
							if ($new_version['security'])
							{
								$ver_title = lang('out_of_date_recommended');
							}
						}
						?>
						<p><b>ExpressionEngine</b> <span class="version<?php if (isset($new_version)): ?> out-of-date<?php if ($new_version['security']): ?>-vital<?php endif; endif ?>" title="<?=$ver_title?>"><?=$formatted_version?></span></p>
						<div class="version-info">
							<?php if (isset($new_version) && $new_version['security']): ?>
								<p class="alert inline warn"><?=lang('recommended_upgrade')?></p>
							<?php endif ?>
							<h3><?=lang('installed')?></h3>
							<p>
								ExpressionEngine <?=$formatted_version?><br>
								<em><?=lang('build') . ' ' . $ee_build_date?></em>
								<?php if ( ! empty($version_identifier)): ?>
									<br><em><?=lang('version_identifier') . ' ' . $version_identifier?></em>
								<?php endif ?>
							</p>
							<?php if (isset($new_version)): ?>
								<h3><?=lang('latest_version')?> (<a href="<?=ee()->cp->masked_url('https://store.ellislab.com/manage')?>" rel="external"><?=lang('download')?></a>)</h3>
								<p>
								ExpressionEngine <?=$new_version['version']?><br>
								<em><?=lang('build') . ' ' . $new_version['build']?></em>
							</p>
								<a href="" class="close">&#10006;</a>
								<div class="status out"><?=lang('out_of_date')?></div>
							<?php else: ?>
								<a href="" class="close">&#10006;</a>
								<div class="status"><?=lang('current')?></div>
							<?php endif ?>
						</div>
					</div>
					<div class="right">
						<p>
							<?php if (ee()->cp->allowed_group('can_access_footer_report_bug')): ?>
								<a href="https://support.ellislab.com/bugs/submit" rel="external"><?=lang('report_bug')?></a>

								<?php if (ee()->cp->allowed_group('can_access_footer_new_ticket') || ee()->cp->allowed_group('can_access_footer_user_guide')): ?>
									<b class="sep">&middot;</b>
								<?php endif; ?>
							<?php endif; ?>

							<?php if (ee()->cp->allowed_group('can_access_footer_new_ticket')): ?>
								<a href="https://support.ellislab.com" rel="external"><?=lang('new_ticket')?></a>

								<?php if (ee()->cp->allowed_group('can_access_footer_user_guide')): ?>
									<b class="sep">&middot;</b>
								<?php endif; ?>
							<?php endif; ?>

							<?php if (ee()->cp->allowed_group('can_access_footer_user_guide')): ?>
								<a href="https://ellislab.com/expressionengine/user-guide/" rel="external"><?=lang('user_guide')?></a>
							<?php endif; ?>
						</p>
					</div>
				</div>
			</section>
			<section class="footer">
				<div class="snap">
					<div class="left">
						<p>&copy;<?=date('Y')?> <a href="<?=ee()->cp->masked_url('https://ellislab.com/expressionengine')?>" rel="external">EllisLab</a>, Inc.<br><a class="scroll" href="#top"><?=lang('scroll_to_top')?></a></p>
					</div>
					<div class="right">
						<p>
						<?php if ($ee_license->isValid()): ?>
							<?=lang('license_no')?>: <?=$ee_license->getData('license_number')?>
							<br><?=lang('owned_by')?>: <a href="mailto:<?=$ee_license->getData('license_contact')?>">
								<?=($ee_license->getData('license_contact_name')) ?: $ee_license->getData('license_contact')?>
							</a>
						<?php else: ?>
							<?php if (ee()->cp->allowed_group('can_access_sys_prefs')): ?>
								<a class="btn no-reg" href="<?=ee('CP/URL')->make('settings/license')?>"><?=lang('register_now')?></a>
							<?php else: ?>
								<?=lang('not_entered')?>
							<?php endif ?>
						<?php endif; ?>
						</p>
					</div>
				</div>
			</section>
		</section>
		<div class="overlay"></div>

		<?=ee()->view->script_tag('jquery/jquery.js')?>
		<?=ee()->view->script_tag('common.js')?>
		<?php

		echo ee()->javascript->get_global();

		echo ee()->cp->render_footer_js();

		if (isset($_extra_library_src))
		{
			echo $_extra_library_src;
		}

		echo ee()->javascript->script_foot();

		foreach (ee()->cp->get_foot() as $item)
		{
			echo $item."\n";
		}
		?>
		<div id="idle-modal" class="modal-wrap modal-timeout hidden">
			<div class="modal">
				<div class="col-group snap">
					<div class="col w-16 last">
						<a class="m-close" href="#"></a>
						<div class="box">
							<h1>Log into <?=ee()->config->item('site_name')?> <span class="req-title"><?=lang('required_fields')?></span></h1>
							<?=form_open(ee('CP/URL')->make('login/authenticate'), array('class' => 'settings'))?>
								<div class="alert inline warn">
									<p><?=lang('session_timeout')?></p>
								</div>
								<fieldset class="col-group required">
									<div class="setting-txt col w-8">
										<h3><?=lang('username')?></h3>
										<em></em>
									</div>
									<div class="setting-field col w-8 last">
										<input type="text" value="<?=form_prep(ee()->session->userdata('username'))?>" disabled="disabled">
										<input type="hidden" name="username" value="<?=form_prep(ee()->session->userdata('username'))?>">
									</div>
								</fieldset>
								<fieldset class="col-group required last">
									<div class="setting-txt col w-8">
										<h3><?=lang('password')?></h3>
										<em></em>
									</div>
									<div class="setting-field col w-8 last">
										<input type="password" name="password" value="" id="logout-confirm-password">
									</div>
								</fieldset>
								<fieldset class="form-ctrls">
									<?=form_submit('submit', lang('login'), 'class="btn" data-work-text="'.lang('authenticating').'"')?>
								</fieldset>
							<?=form_close()?>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?=ee('CP/Alert')->getStandard()?>
	</body>
</html>
