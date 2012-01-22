<?php GlobalHeader(); ?>

<!-- 
  Javascript
-->
<script type="text/javascript">
$(document).ready(function(){
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

<h1><?php Translate("User"); ?>: <?php PrintStringValue("Username"); ?></h1>
<p class="hdesc"><?php Translate("On this page you can see all assignments of the user."); ?></p>

<?php if (IsProviderActive(PROVIDER_USER_EDIT) && HasAccess(ACL_MOD_USER, ACL_ACTION_CHANGEPASS_OTHER)) { ?>
<!-- 
  Change password button
-->
<div style="text-align:right;">
  <form method="get" action="userchangepass.php">
    <input type="hidden" name="username" value="<?php PrintStringValue("UsernameEncoded"); ?>">
    <input type="submit" value="<?php Translate("Change password"); ?>">
  </form>
</div>
<?php } ?>


<?php if (IsProviderActive(PROVIDER_AUTHENTICATION) && HasAccess(ACL_MOD_ROLE, ACL_ACTION_VIEW)) { ?>
<!--
  Roles of user
-->
<h2><?php Translate("Roles of user"); ?></h2>
<form action="userview.php?username=<?php PrintStringValue("UsernameEncoded"); ?>" method="POST">
	<input type="hidden" name="selected_users[]" value="<?php PrintStringValue("Username"); ?>">
	<table class="datatable">
	
	<thead>
	  <tr>
	    <th width="20">#</th>
	    <th><?php Translate("Role"); ?></th>
	    <th><?php Translate("Description"); ?></th>
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
	          <?php if (HasAccess(ACL_MOD_ROLE, ACL_ACTION_UNASSIGN)) { ?>
	          <input type="submit" name="unassign_role" value="<?php Translate("Unassign"); ?>" class="unbtn">
	          <?php } ?>
	        </td>
	        <td align="right">
	          <?php if (HasAccess(ACL_MOD_ROLE, ACL_ACTION_ASSIGN)) { ?>
	          <small>(<a id="showrolelistlink" href="#"><?php Translate("Show roles"); ?></a>)</small>
	          <select name="selected_assign_role_name">
	            <option value="">--- <?php Translate("Role"); ?> ---</option>
	            <?php foreach (GetArrayValue("RoleListAll") as $r) { ?>
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
	<?php foreach (GetArrayValue("RoleList") as $r) { ?>
	<tr>
	  <td><?php if (HasAccess(ACL_MOD_ROLE, ACL_ACTION_UNASSIGN)) { ?><input type="checkbox" name="selected_roles[]" value="<?php print($r->name); ?>"><?php } ?></td>
	  <td><?php Translate($r->name); ?></td>
	  <td><?php Translate($r->description); ?></td>
	</tr>
	<?php } ?>
	</tbody>
	
	</table><br>
</form>
<?php } ?>


<?php if (IsProviderActive(PROVIDER_GROUP_VIEW) && HasAccess(ACL_MOD_GROUP, ACL_ACTION_VIEW)) { ?>
<!--
  Groups of user
-->
<h2><?php Translate("Groups of user"); ?></h2>
<form action="userview.php?username=<?php PrintStringValue("UsernameEncoded"); ?>" method="POST">
<input type="hidden" name="selected_users[]" value="<?php PrintStringValue("Username"); ?>">

<?php HtmlFilterBox("usergrouplist", 1); ?>

<table id="usergrouplist" class="datatable">
	<thead>
	<tr>
	  <th width="20">#</th>
	  <th><?php Translate("Group"); ?></th>
	</tr>
	</thead>
	
	<tfoot>
	<tr>
	  <td colspan="2">
	    <?php if (IsProviderActive(PROVIDER_GROUP_EDIT)) { ?>
	    <table class="datatableinline">
	      <colgroup>
	        <col width="50%">
	        <col width="50%">
	      </colgroup>
	      <tr>
	        <td>
	          <?php if (HasAccess(ACL_MOD_GROUP, ACL_ACTION_UNASSIGN)) {?>
	          <input type="submit" name="unassign" value="<?php Translate("Unassign"); ?>" class="unbtn">
	          <?php } ?>
	        </td>
	        <td align="right">
	          <?php if (HasAccess(ACL_MOD_GROUP, ACL_ACTION_ASSIGN)) { ?>
	          <select name="selected_groups[]">
	            <option value="">--- <?php Translate("Group"); ?> ---</option>
	            <?php foreach (GetArrayValue("GroupListAll") as $g) { ?>
	            <option value="<?php print($g->name); ?>"><?php print($g->name); ?></option>
	            <?php } ?>
	          </select>
	          <input type="submit" name="assign_usergroup" value="<?php Translate("Assign"); ?>" class="anbtn">
	          <?php } ?>
	        </td>
	      </tr>
	    </table>
	    <?php } ?>
	  </td>
	</tr>
	</tfoot>
	
	<tbody>
	<?php foreach (GetArrayValue("GroupList") as $g) { ?>
	<tr>
	  <td><?php if (IsProviderActive(PROVIDER_GROUP_EDIT) && HasAccess(ACL_MOD_GROUP, ACL_ACTION_UNASSIGN)) { ?><input type="checkbox" name="selected_groups[]" value="<?php print($g->name); ?>"><?php } ?></td>
	  <td><a href="groupview.php?groupname=<?php print($g->getEncodedName()); ?>"><?php print($g->name); ?></a></td>
	</tr>
	<?php } ?>
	</tbody>
  
	</table><br>
</form>
<?php } ?>


<?php if (IsProviderActive(PROVIDER_ACCESSPATH_VIEW) && HasAccess(ACL_MOD_ACCESSPATH, ACL_ACTION_VIEW)) { ?>
<!--
  Permissions of user
-->
<h2><?php Translate("Permissions of users"); ?></h2>
<form action="userview.php?username=<?php PrintStringValue("UsernameEncoded"); ?>" method="POST">
<input type="hidden" name="selected_users[]" value="<?php PrintStringValue("Username"); ?>">

<?php HtmlFilterBox("userpermissionlist", 1); ?>

	<table id="userpermissionlist" class="datatable">
  
	<thead>
	<tr>
	  <th width="20">#</th>
	  <th><?php Translate("Access-Path"); ?></th>
	  <th><?php Translate("Permission"); ?></th>
	  <th><?php Translate("Inherit from Group"); ?></th>
	</tr>
	</thead>
  
	<tfoot>
	<tr>
	  <td colspan="4">
	    <table class="datatableinline">
	      <colgroup>
	        <col width="100%">
	      </colgroup>
	      <tr>
	        <td>
            <?php if (IsProviderActive(PROVIDER_ACCESSPATH_EDIT) && HasAccess(ACL_MOD_ACCESSPATH, ACL_ACTION_UNASSIGN)) { ?>
	          <input type="submit" name="unassign_permission" value="<?php Translate("Unassign"); ?>" class="unbtn">
	          <?php Translate("<b>Note:</b> You can not unassign permissions from user, which are inherited by a group."); ?>
	          <?php } ?>
	        </td>
	      </tr>
	    </table>
	  </td>
	</tr>
	</tfoot>
  
	<tbody>
  <?php foreach (GetArrayValue("PathList") as $ap) { ?>
	<tr>
	  <td><?php if (IsProviderActive(PROVIDER_ACCESSPATH_EDIT) && HasAccess(ACL_MOD_ACCESSPATH, ACL_ACTION_UNASSIGN) && empty($ap->inherited)) { ?><input type="checkbox" name="selected_accesspaths[]" value="<?php print($ap->path); ?>"><?php } ?></td>
	  <td><a href="accesspathview.php?accesspath=<?php print($ap->getEncodedPath()); ?>"><?php print($ap->path); ?></a></td>
	  <td><?php print($ap->perm); ?></td>
	  <td><?php print($ap->inherited); ?></td>
	</tr>
	<?php } ?>
	</tbody>
  
	</table>
</form>
<br>
<?php } ?>


<?php if (IsProviderActive(PROVIDER_AUTHENTICATION) && GetBoolValue("ProjectManager") && HasAccess(ACL_MOD_PROJECTMANAGER, ACL_ACTION_VIEW)) { ?>
<!-- 
  Project manager's access path list
-->
<h2><?php Translate("User is project manager of:"); ?></h2>
<form action="userview.php?username=<?php PrintStringValue("UsernameEncoded"); ?>" method="POST">
<input type="hidden" name="selected_users[]" value="<?php PrintStringValue("Username"); ?>">
<table class="datatable">
  <thead>
    <tr>
      <th width="20"></th>
      <th></th>
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
              <?php if (HasAccess(ACL_MOD_PROJECTMANAGER, ACL_ACTION_UNASSIGN)) { ?>
              <input type="submit" name="unassign_projectmanager" value="<?php Translate("Unassign"); ?>" class="unbtn">
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
    <?php foreach (GetArrayValue("RestrictedPathList") as $rp) { ?>
    <tr>
      <td><?php if (HasAccess(ACL_MOD_PROJECTMANAGER, ACL_ACTION_UNASSIGN)) { ?><input type="checkbox" name="selected_accesspaths[]" value="<?php print($rp->path); ?>"><?php } ?></td>
      <td><a href="accesspathview.php?accesspath=<?php print($rp->getEncodedPath()); ?>"><?php print($rp->path); ?></a> <small>(<?php Translate("+All sub Access-Path's"); ?>)</small></td>
    </tr>
    <?php } ?>
  </tbody>
</table>
</form>
<br>
<?php } ?>


<?php GlobalFooter(); ?>