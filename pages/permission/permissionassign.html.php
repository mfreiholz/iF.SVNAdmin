<?php GlobalHeader(); ?>

<script type="text/javascript">
$(document).ready(function(){
  $("#selectallusers").click(function(){
    selectAll(this, "selected_users[]");
  });
  $("#selectallgroups").click(function(){
    selectAll(this, "selected_groups[]");
  });
  $("#selectallpaths").click(function(){
    selectAll(this, "selected_accesspaths[]");
  });
});
</script>

<h1><?php Translate("Permissions"); ?></h1>
<p class="hdesc"><?php Translate("Manage the user and group permissions for the different access-paths on this site."); ?></p>

      <form action="permissionassign.php" method="POST">
      <table width="100%">
        <tr>
          <td valign="top" width="50%">

            <?php HtmlFilterBox("userlist", 1); ?>

            <table id="userlist" class="datatable">
              <thead>
              <tr>
                <th width="20"><input type="checkbox" id="selectallusers"></th>
                <th><?php Translate("User"); ?></th>
              </tr>
              </thead>
              <tbody>
              <?php foreach(GetArrayValue("UserList") as $u): ?>
              <tr>
                <td><input type="checkbox" name="selected_users[]" value="<?php print($u->getName()); ?>"></td>
                <td><a href="userview.php?username=<?php print($u->getEncodedName()); ?>"><?php print($u->getName()); ?></a></td>
              </tr>
              <?php endforeach; ?>
              </tbody>
            </table>

          </td>
          <td valign="top" width="50%">

            <?php HtmlFilterBox("grouplist", 1); ?>

            <table id="grouplist" class="datatable">
              <thead>
              <tr>
                <th width="20"><input type="checkbox" id="selectallgroups"></th>
                <th><?php Translate("Group"); ?></th>
              </tr>
              </thead>
              <tbody>
              <?php foreach(GetArrayValue("GroupList") as $g): ?>
              <tr>
                <td><input type="checkbox" name="selected_groups[]" value="<?php print($g->getName()); ?>"></td>
                <td><a href="groupview.php?groupname=<?php print($g->getEncodedName()); ?>"><?php print($g->getName()); ?></a></td>
              </tr>
              <?php endforeach; ?>
              </tbody>
            </table>

          </td>
        </tr>
        <tr>
          <td colspan="2" class="asstoptobottom" height="30"></td>
        </tr>
        <tr>
          <td colspan="2">

    <table class="datatable">
      <thead>
        <tr>
          <th><?php Translate("Select the permission for the selected user(s)/group(s)"); ?></th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td><input type="radio" name="permission" value="<?php PrintStringValue("PermNone"); ?>" checked> <b><?php Translate("No permission"); ?>:</b> <?php Translate("The user(s)/group(s) has no rights."); ?></td>
        </tr>
        <tr>
          <td><input type="radio" name="permission" value="<?php PrintStringValue("PermRead"); ?>"> <b><?php Translate("Read only"); ?>:</b> <?php Translate("The user(s)/group(s) can see all files."); ?></td>
        </tr>
        <tr>
          <td><input type="radio" name="permission" value="<?php PrintStringValue("PermReadWrite"); ?>"> <b><?php Translate("Read &amp; Write"); ?>:</b> <?php Translate("The user(s)/group(s) can see, create, modify and delete all files."); ?></td>
        </tr>
      </tbody>
    </table>

          </td>
        </tr>
        <tr>
          <td colspan="2" class="asstoptobottom" height="30"></td>
        </tr>
        <tr>
          <td colspan="2">

	<?php HtmlFilterBox("accesspathlist", 1); ?>

    <table id="accesspathlist" class="datatable">
      <thead>
        <tr>
          <th width="20"><input type="checkbox" id="selectallpaths"></th>
          <th colspan="2"><?php Translate("Access-Path"); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach(GetArrayValue("AccessPathList") as $p): ?>
        <tr>
          <td><input type="checkbox" name="selected_accesspaths[]" value="<?php print($p->getPath()); ?>"></td>
          <td><a href="accesspathview.php?accesspath=<?php print($p->getEncodedPath()); ?>"><?php print($p->getPath()); ?></a></td>
        </tr>
		<?php endforeach; ?>
      </tbody>
    </table>

          </td>
        </tr>
      </table>
      <div class="formsubmit">
        <input type="submit" name="assign" value="<?php Translate("Assign"); ?>" class="anbtn">
      </div>
      </form>

<?php GlobalFooter(); ?>