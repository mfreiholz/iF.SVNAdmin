<?php GlobalHeader(); ?>

<h1><?php Translate("Define administrator"); ?></h1>

<?php if (GetBoolValue("NoUserEditActive")): ?>
<p>
  <?php Translate("You have not defined an \"UserEditProviderType\", but the system also could not find any user from the configured user view provider."); ?><br>
  <?php Translate("You should now create a new user in your configured backend (e.g.: LDAP/Active Directory)."); ?>
</p>
<p><?php Translate("If you think your configuration is incorrect, <a href=\"settings.php?firststart=1\">click here.</a>"); ?></p>
<?php endif; ?>

<?php if (GetBoolValue("DefaultUserCreated")): ?>
<div class="hintmsg">
  <?php Translate("Could not find existing users with your configuration. Created default Administrator account."); ?>
  <p>
    <?php Translate("User"); ?>: <b>admin</b><br>
    <?php Translate("Password"); ?>: <b>admin</b>
  </p>
</div>
<p>
  <?php Translate("You can <a href=\"login.php\"><b>login now.</b></a>"); ?>
</p>
<?php endif; ?>


<?php if (!GetBoolValue("DefaultUserCreated") && GetBoolValue("ShowUserSelection")): ?>
<div class="hintmsg">
  <?php Translate("No administrator defined! Select the user for the administator privileges, please."); ?>
</div>

<form method="post" action="settings.php">
  <table class="datatable settings">
    <tbody>
      <tr>
        <td><?php Translate("User"); ?></td>
        <td>
          <select name="selected_users[]">
            <?php foreach(GetArrayValue("UserList") as $u): ?>
            <option value="<?php print($u->getEncodedName()); ?>"><?php print($u->getName()); ?></option>
            <?php endforeach; ?>
          </select>
        </td>
      </tr>
    </tbody>
  </table>
  <br>
  <input type="hidden" name="setadmin" value="1">
  <input type="submit" name="saveadmin" value="<?php Translate("Save"); ?>">
</form>
<?php endif; ?>

<?php GlobalFooter(); ?>