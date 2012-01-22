<?php GlobalHeader(); ?>

<h1><?php Translate("Create group"); ?></h1>
<p class="hdesc"><?php Translate("Create a new group to grant access to the repositories for a range of users."); ?></p>

<div>
  <form method="POST" action="groupcreate.php">
  
    <div class="form-field">
      <label for="name"><?php Translate("Group name"); ?></label>
      <input type="text" name="name" id="name" class="lineedit">
      <p>
        <b><?php Translate("Valid group names are"); ?>:</b> MyReaderGroup, my_reader-group4<br>
        <b><?php Translate("Invalid group names are"); ?>:</b> My Do$$ar group, My Group, Mööne Grüüpe
      </p>
    </div>

    <div class="formsubmit">
      <input type="submit" name="create" value="<?php Translate('Create'); ?>" class="addbtn">
    </div>
    
  </form>
  
  <p>
    <a href="grouplist.php">&#xAB; <?php Translate("Back to overview"); ?></a>
  </p>
  
</div>

<?php GlobalFooter(); ?>
