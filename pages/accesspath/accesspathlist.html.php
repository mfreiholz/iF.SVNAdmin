<?php GlobalHeader(); ?>

<script type="text/javascript">
$(document).ready(function(){
  $("#selectall").click(function(){
    selectAll(this, "selected_accesspaths[]");
  });
});
</script>

<h1><?php Translate("Access-Path management"); ?></h1>
<p class="hdesc"><?php Translate("Here you can see a list of all access path, which are defined in your subversion configuration."); ?></p>

<?php HtmlFilterBox("accesspathlist", 1); ?>

<form action="accesspathslist.php" method="POST">
<table id="accesspathlist" class="datatable">
<thead>
<tr>
	<th width="20"><?php if (IsProviderActive(PROVIDER_ACCESSPATH_EDIT) && HasAccess(ACL_MOD_ACCESSPATH, ACL_ACTION_DELETE)) : ?><input type="checkbox" id="selectall"><?php endif; ?></th>
	<th><?php Translate("Access-Path"); ?></th>
</tr>
</thead>

<tfoot>
	<tr>
		<td colspan="2">

			<table class="datatableinline">
            <colgroup>
              <col width="50%">
              <col width="50%">
            </colgroup>
            <tr>
              <td>
                <?php if (IsProviderActive(PROVIDER_ACCESSPATH_EDIT) && HasAccess(ACL_MOD_ACCESSPATH, ACL_ACTION_DELETE)) : ?>
                <input type="submit" name="delete" value="<?php Translate("Delete"); ?>" class="delbtn" onclick="return deletionPrompt('<?php Translate("Are you sure?"); ?>');">
                <?php endif; ?>
              </td>
              <td align="right">
                <?php if (IsProviderActive(PROVIDER_USER_VIEW) && HasAccess(ACL_MOD_PROJECTMANAGER, ACL_ACTION_ASSIGN)) : ?>
                <select name="selected_users[]">
                  <option value="">--- <?php Translate("Set project manager"); ?> ---</option>
                <?php foreach (GetArrayValue("UserList") as $u) : ?>
                  <option><?php print($u->name); ?></option>
                <?php endforeach; ?>
                </select>
                <input type="submit" name="assign_projectmanager" value="<?php Translate("Assign"); ?>">
                <?php endif; ?>
              </td>
            </tr>
          </table>

        </td>
      </tr>
      </tfoot>
      <tbody>
      <?php foreach (GetArrayValue("AccessPathList") as $ap) : ?>
      <tr>
        <td><?php if (IsProviderActive(PROVIDER_ACCESSPATH_EDIT) && HasAccess(ACL_MOD_ACCESSPATH, ACL_ACTION_DELETE)) : ?><input type="checkbox" name="selected_accesspaths[]" value="<?php print($ap->getPath()); ?>"><?php endif; ?></td>
        <td>
          <a href="accesspathview.php?accesspath=<?php print($ap->getEncodedPath()); ?>"><?php print($ap->getPath()); ?></a><br>
          <small><?php Translate("Managers"); ?>: <?php print($ap->getManagersAsString()); ?></small>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
      </table>
      </form>

<?php GlobalFooter(); ?>