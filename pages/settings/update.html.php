<?php GlobalHeader(); ?>

<h1><?php Translate("Synchronize"); ?></h1>
<p class="hdesc"><?php Translate("Synchronize your data."); ?></p>

<div class="hintmsg">
	<?php Translate("Your current application configuration uses a data provider, which requires updates from time to time. Here you can force an update now, if you didn't configure any cron-jobs or Windows task to perform this update."); ?>
</div>

<form action="update.php" method="post">
	<input type="hidden" name="update" value="true">
	<div style="text-align:center; margin-top:20px;">
		<div><input type="submit" value="<?php Translate("Synchronize now"); ?>"></div>
		<small>(<?php Translate("Note: This could take a few seconds. Do not press this button twice!"); ?>)</small>
	</div>
</form>

<?php GlobalFooter(); ?>