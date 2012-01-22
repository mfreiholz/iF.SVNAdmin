<?php GlobalHeader(); ?>

<h1><?php Translate("Change password of user"); ?> <?php PrintStringValue("Username"); ?></h1>
<p class="hdesc"><?php Translate("Change the password of your subversion account."); ?></p>
<div>
	<form method="POST" action="userchangepass.php">
		<input type="hidden" name="username" value="<?php PrintStringValue("Username"); ?>">

		<div class="form-field">
		    <label for="password"><?php Translate("New password"); ?>:</label>
		    <input type="password" name="password" id="password" class="lineedit">
		</div>

		<div class="form-field">
			<label for="password2"><?php Translate("Re-type password"); ?>:</label>
			<input type="password" name="password2" id="password2" class="lineedit">
		</div>

		<div class="formsubmit">
			<input type="submit" name="changepass" value="<?php Translate("Change password"); ?>" class="addbtn">
		</div>

	</form>
</div>

<?php GlobalFooter(); ?>