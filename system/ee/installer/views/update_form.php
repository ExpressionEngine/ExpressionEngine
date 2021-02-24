<div class="panel">
  <div class="panel-heading" style="text-align: center;">
  	<h3><?=($header) ?: $title?></h3>
  </div>
  <div class="panel-body">
  	<div class="app-notice app-notice--inline app-notice---attention">
  		<div class="app-notice__tag">
  			<span class="app-notice__icon"></span>
  		</div>
  		<div class="app-notice__content">
  			<p><?=lang('update_note')?></p>
  			<p><?=lang('update_backup')?></p>
  		</div>
  	</div>
  	<form action="<?=$action?>" method="post">
  		<?php if ($show_advanced): ?>
  			<fieldset class="form-ctrls">
  				<label class="checkbox-label">
            <input type="checkbox" name="database_backup" value="1"> <div class="checkbox-label__text"><?=lang('update_should_get_database_backup')?></div>
          </label>
  			</fieldset>
  			<fieldset class="form-ctrls">
  				<label class="checkbox-label">
            <input type="checkbox" name="update_addons" value="1"> <div class="checkbox-label__text"><?=lang('update_should_update_addons')?></div>
          </label>
  			</fieldset>
  		<?php endif; ?>
      <div class="panel-footer" style="margin: 25px -25px -20px;">
        <div class="form-btns">
    			<input class="button button--primary button--large button--wide" type="submit" value="<?=lang('start_update')?>">
        </div>
      </div>
  	</form>
    </div>
</div>
