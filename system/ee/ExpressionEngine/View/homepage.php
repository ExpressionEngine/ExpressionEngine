<?php $this->extend('_templates/default-nav'); ?>

<script>
document.querySelector('.ee-main').classList.add('ee-main--dashboard')
</script>

<div class="dashboard">

<?php
if (!isset($ee_cp_viewmode) || empty($ee_cp_viewmode)) {
	include '_shared/dashboard/welcome.php';
} else {
	echo $dashboard;
}
?>

</div>
