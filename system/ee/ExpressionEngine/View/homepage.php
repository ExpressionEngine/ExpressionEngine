<?php $this->extend('_templates/default-nav'); ?>

<script>
document.querySelector('.ee-main').classList.add('ee-main--dashboard')
</script>

<div class="dashboard">

<?php
echo $dashboard;
?>

</div>
