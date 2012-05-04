<?php GlobalHeader(); ?>

<h1><?php Translate("Group"); ?>: <?php PrintStringValue("GroupName"); ?></h1>
<p class="hdesc"><?php Translate("On this page you can see all assigned users to this group."); ?></p>


<?php if (IsProviderActive(PROVIDER_GROUP_VIEW) && HasAccess(ACL_MOD_GROUP, ACL_ACTION_VIEW)) : ?>
<h2><?php Translate("Users of group"); ?></h2>
<form action="groupview.php?groupname=<?php PrintStringValue('GroupNameEncoded'); ?>" method="POST">
  <input type="hidden" name="selected_groups[]" value="<?php PrintStringValue('GroupName'); ?>">

  <?php HtmlFilterBox("groupuserlist", 1); ?>

  <table id="groupuserlist" class="datatable">
    <thead>
    <tr>
      <th width="20">#</th>
      <th><?php Translate("User"); ?></th>
    </tr>
    </thead>

    <tfoot>
    <tr>
      <td colspan="2">

        <?php if (IsProviderActive(PROVIDER_GROUP_EDIT)): ?>
        <table class="datatableinline">
          <colgroup>
            <col width="50%">
            <col width="50%">
          </colgroup>
          <tr>
            <td>
              <?php if (HasAccess(ACL_MOD_GROUP, ACL_ACTION_UNASSIGN)): ?>
              <input type="submit" name="unassign" value="<?php Translate('Unassign'); ?>" class="unbtn">
              <?php endif; ?>
            </td>
            <td align="right">
              <?php if (HasAccess(ACL_MOD_GROUP, ACL_ACTION_ASSIGN)): ?>
              <select name="selected_users[]">
                <option value="">--- <?php Translate("User"); ?> ---</option>
                <?php foreach(GetArrayValue("AllUserList") as $u): ?>
                <option value="<?php print($u->name); ?>"><?php print($u->name); ?></option>
                <?php endforeach; ?>
              </select>
              <input type="submit" name="assign_usergroup" value="<?php Translate('Assign'); ?>" class="anbtn">
              <?php endif; ?>
            </td>
          </tr>
        </table>
        <?php endif; ?>

      </td>
    </tr>
    </tfoot>

    <tbody>
    <?php foreach(GetArrayValue("UserList") as $u): ?>
    <tr>
      <td>
        <?php if (IsProviderActive(PROVIDER_GROUP_EDIT) && HasAccess(ACL_MOD_GROUP, ACL_ACTION_UNASSIGN)): ?>
        <input type="checkbox" name="selected_users[]" value="<?php print($u->name); ?>">
        <?php endif; ?>
      </td>
      <td>
        <a href="userview.php?username=<?php print($u->getEncodedName()); ?>"><?php print($u->name); ?></a>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>

  </table><br>
</form>
<?php endif; ?>


<?php if (IsProviderActive(PROVIDER_ACCESSPATH_VIEW) && HasAccess(ACL_MOD_ACCESSPATH, ACL_ACTION_VIEW)): ?>
<h2><?php Translate("Permissions of group"); ?></h2>
<form action="groupview.php?groupname=<?php PrintStringValue('GroupNameEncoded'); ?>" method="POST">
<input type="hidden" name="selected_groups[]" value="<?php PrintStringValue('GroupNameEncoded'); ?>">

  <?php HtmlFilterBox("grouplist", 1); ?>

  <table id="grouplist" class="datatable">
    <thead>
    <tr>
      <th width="20">#</th>
      <th><?php Translate("Access-Path"); ?></th>
      <th><?php Translate("Permission"); ?></th>
    </tr>
    </thead>

    <tfoot>
    <tr>
      <td colspan="3">
        <table class="datatableinline">
          <colgroup>
            <col width="50%">
            <col width="50%">
          </colgroup>
          <tr>
            <td>
              <?php if (IsProviderActive(PROVIDER_ACCESSPATH_EDIT) && HasAccess(ACL_MOD_ACCESSPATH, ACL_ACTION_UNASSIGN)): ?>
              <input type="submit" name="unassign_permission" value="<?php Translate('Unassign'); ?>" class="unbtn">
              <?php endif; ?>
            </td>
            <td align="right">
            </td>
          </tr>
        </table>
      </td>
    </tr>
    </tfoot>

    <tbody>
    <?php foreach (GetArrayValue("AccessPathList") as $ap): ?>
    <tr>
      <td>
        <?php if (IsProviderActive(PROVIDER_ACCESSPATH_EDIT) && HasAccess(ACL_MOD_ACCESSPATH, ACL_ACTION_UNASSIGN)): ?>
        <input type="checkbox" name="selected_accesspaths[]" value="<?php echo $ap->getPath(); ?>">
        <?php endif; ?>
      </td>
      <td>
        <a href="accesspathview.php?accesspath=<?php echo $ap->getEncodedPath(); ?>"><?php echo $ap->getPath(); ?></a>
      </td>
      <td>
        <?php echo $ap->getPerm(); ?>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table><br>
</form>
<?php endif; ?>

<?php GlobalFooter(); ?>
