<?php GlobalHeader(); ?>

<script type="text/javascript">
$(document).ready(function(){
  $("#selectall").click(function(){
    selectAll(this, "selected_users[]");
  });

  $("#showrolelistlink").click(function(event){
    event.preventDefault();
    if ($("#rolelist").length == 0)
    {
      $.get("rolelist.php", function(data){
        $("body").append(data);
        $("#rolelist").dialog({width:750, height:450});
      });
    }
    else
    {
      $("#rolelist").dialog();
    }
  });
});
</script>

<h1><?php Translate("User management"); ?></h1>
<p class="hdesc"><?php Translate("Here you can see a list of all users which can be authenticated by your subversion server."); ?></p>

<?php HtmlFilterBox("userlist", 1); ?>

<form action="userlist.php" method="POST">
	<table id="userlist" class="datatable">
	
	<thead>
	<tr>
	  <th width="20">
	  	<?php if (HasAccess(ACL_MOD_USER, ACL_ACTION_DELETE) || HasAccess(ACL_MOD_ROLE,	ACL_ACTION_ASSIGN)) { ?>
	    <input type="checkbox" id="selectall">
	    <?php } ?>
	  </th>
	  <th>
	  	<?php Translate("User"); ?>
	  </th>
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
	          <?php if (IsProviderActive(PROVIDER_USER_EDIT) && HasAccess(ACL_MOD_USER, ACL_ACTION_DELETE)) { ?>
	          <input type="submit" name="delete" value="<?php Translate("Delete"); ?>" class="delbtn" onclick="return deletionPrompt('<?php Translate("Are you sure?"); ?>');">
	          <?php } ?>
	        </td>
	        <td align="right">
	          <?php if (IsProviderActive(PROVIDER_AUTHENTICATION) && HasAccess(ACL_MOD_ROLE, ACL_ACTION_ASSIGN)) { ?>
	          <small>(<a id="showrolelistlink" href="#"><?php Translate("Show roles"); ?></a>)</small>
	          <select name="selected_assign_role_name">
	            <option value="">--- <?php Translate("Role"); ?> ---</option>
	            <?php foreach (GetArrayValue("RoleList") as $r) { ?>
	            <option value="<?php print($r->name); ?>"><?php Translate($r->name); ?></option>
	            <?php } ?>
	          </select>
	          <input type="submit" name="assign_role" value="<?php Translate("Assign"); ?>" class="anbtn">
	          <?php } ?>
	        </td>
	      </tr>
	    </table>
	
	  </td>
	</tr>
	</tfoot>
	
	<tbody>
		<?php foreach (GetArrayValue("UserList") as $u) { ?>
		<tr>
		  <td>
        <?php if (HasAccess(ACL_MOD_USER, ACL_ACTION_DELETE) || HasAccess(ACL_MOD_ROLE,	ACL_ACTION_ASSIGN)) { ?>
        <input type="checkbox" name="selected_users[]" value="<?php print($u->name); ?>">
        <?php } ?>
      </td>
		  <td><a href="userview.php?username=<?php print($u->getEncodedName()); ?>"><?php print($u->name); ?></a></td>
		</tr>
		<?php } ?>
	</tbody>
	</table>
</form>

<?php GlobalFooter(); ?>