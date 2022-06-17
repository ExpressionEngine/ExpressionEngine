<?php $this->extend('ee:_templates/default-nav'); ?>

<script type="text/javascript">
document.querySelector('.ee-main').classList.add('ee-main--dashboard')
</script>

<?php if (isset($edit_mode) && $edit_mode == true) : ?>
    <?=form_open($header['action_buttons']['save']['href'], 'id="save-dashboard-layout-form"');?>
<?php endif; ?>

<div class="dashboard">

<?=$dashboard?>

</div>

<?php if (isset($edit_mode) && $edit_mode == true) : ?>
    </form>
<?php endif; ?>
