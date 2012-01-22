<?php GlobalHeader(); ?>

<h1><?php Translate("Create user"); ?></h1>
<p class="hdesc"><?php Translate("Create a new user to grant access to the repositories."); ?></p>

<div>
  <form method="POST" action="usercreate.php">
  
    <div class="form-field">
      <label for="username"><?php Translate("Username"); ?></label>
      <input type="text" name="username" id="username" class="lineedit">
    </div>

    <div class="form-field">
      <label for="password"><?php Translate("Password"); ?></label>
      <input type="password" name="password" id="password" class="lineedit">
    </div>

    <div class="form-field">
      <label for="password2"><?php Translate("Re-type password"); ?></label>
      <input type="password" name="password2" id="password2" class="lineedit">
    </div>

    <div class="formsubmit">
      <input type="submit" name="create" value="<?php Translate("Create"); ?>" class="addbtn">
    </div>
  </form>
  
  <p>
    <a href="userlist.php">&#xAB; <?php Translate("Back to overview"); ?></a>
  </p>
  
</div>

<?php GlobalFooter(); ?>