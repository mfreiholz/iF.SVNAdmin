<?php GlobalHeader(); ?>

<script type="text/javascript">
$(document).ready(function(){
  $("#selectall").click(function(){
    selectAll(this, "selected_groups[]");
  });
});
</script>

<h1><?php Translate("Group management"); ?></h1>
<p class="hdesc"><?php Translate("Here you can see a list of all groups, which are defined in your subversion configuration."); ?></p>

<?php HtmlFilterBox("grouplist", 1); ?>

<form id="grouplist" action="grouplist.php" method="POST">
<table class="datatable">
<thead>
<tr>
  <?php if (IsProviderActive(PROVIDER_GROUP_EDIT)  && HasAccess(ACL_MOD_GROUP, ACL_ACTION_DELETE)) { ?>
    <th width="20">
      <input type="checkbox" id="selectall">
    </th>
  <?php } ?>
  <th width="50" align="center"><?php Translate("Index"); ?></th>
  <th><?php Translate("Groups"); ?></th>
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
          <?php if (IsProviderActive(PROVIDER_GROUP_EDIT) && HasAccess(ACL_MOD_GROUP, ACL_ACTION_DELETE)) { ?>
            <input type="text" name="reason" class="reasonedit" placeholder="<?php Translate("The reason for delete group"); ?>">

            <input type="submit" name="delete" value="<?php Translate('Delete'); ?>" class="delbtn" onclick="return deletionPrompt('<?php Translate("Are you sure?"); ?>');">
          <?php } ?>
        </td>
        <td align="right">
        </td>
      </tr>
    </table>
  </td>
</tr>
</tfoot>

<tbody>
<?php $Index = 1; foreach (GetArrayValue("GroupList") as $g) { ?>
<tr>
  <?php if (IsProviderActive(PROVIDER_GROUP_EDIT) && HasAccess(ACL_MOD_GROUP, ACL_ACTION_DELETE)) { ?>
    <td>
      <input type="checkbox" name="selected_groups[]" value="<?php print($g->name); ?>">
    </td>
  <?php } ?>
  <td align="center"><?php print($Index); ?></td>
  <td><a href="groupview.php?groupname=<?php print($g->getEncodedName()); ?>"><?php print($g->name); ?></a></td>
</tr>
<?php $Index++; } ?>
</tbody>

</table>
</form>

<?php GlobalFooter(); ?>
