<!-- accessories (footer tabs) -->
<div id="accessoriesDiv">

	<?php if (count($cp_accessories) != 0):?>
	<div id="accessoryTabs">
		<ul>
			<?php foreach ($cp_accessories as $acc): ?>
			<li><a href="#" class="<?=$acc->id?>"><?=$acc->name?><span class="accessoryHandle">&nbsp;</span></a></li>
			<?php endforeach; ?>
		</ul>
	</div> <!-- accessoryTabs -->
	<?php endif;?>

	<?php foreach ($cp_accessories as $acc): ?>
	<div id="<?=$acc->id?>" class="accessory">
		
		<?php foreach ($acc->sections as $heading => $contents): ?>
		<div class="accessorySection">
			<h5><?=$heading?></h5>
			
			<?=$contents?>
		</div> <!-- accessorySection -->
		<?php endforeach; ?>
		
		<div class="clear"></div>
	</div> <!-- <?=$acc->id?> -->
	<?php endforeach; ?>
	
</div> <!-- accessories -->