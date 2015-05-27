<?php GlobalHeader(); ?>

<h1><?php Translate('You do not have permission to access this page.'); ?></h1>
<p>
	<?php Translate('Required permissions:'); ?><br>
	<b><?php Translate('Module:'); ?></b> <?php PrintStringValue("Module"); ?><br>
	<b><?php Translate('Action:'); ?></b> <?php PrintStringValue("Action"); ?>
</p>

<?php GlobalFooter(); ?>