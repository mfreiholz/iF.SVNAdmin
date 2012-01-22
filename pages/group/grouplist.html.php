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
  <th width="20"><?php if (IsProviderActive(PROVIDER_GROUP_EDIT)  && HasAccess(ACL_MOD_GROUP, ACL_ACTION_DELETE)) { ?><input type="checkbox" id="selectall"><?php } ?></th>
  <th><?php Translate("Group"); ?></th>
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
          <?php if (IsProviderActive(PROVIDER_GROUP_EDIT) && HasAccess(ACL_MOD_GROUP, ACL_ACTION_DELETE)) { ?>
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
<?php foreach (GetArrayValue("GroupList") as $g) { ?>
<tr>
  <td><?php if (IsProviderActive(PROVIDER_GROUP_EDIT) && HasAccess(ACL_MOD_GROUP, ACL_ACTION_DELETE)) { ?><input type="checkbox" name="selected_groups[]" value="<?php print($g->name); ?>"><?php } ?></td>
  <td><a href="groupview.php?groupname=<?php print($g->getEncodedName()); ?>"><?php print($g->name); ?></a></td>
</tr>
<?php } ?>
</tbody>

</table>
</form>

<?php GlobalFooter(); ?>
