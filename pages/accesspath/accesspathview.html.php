<?php GlobalHeader(); ?>

<h1><?php Translate("Access-Path"); ?>: <?php PrintStringValue("AccessPath"); ?></h1>
<p class="hdesc"><?php Translate("Assigned users and groups to this access-path."); ?></p>

<h2><?php Translate("Assigned users"); ?></h2>
<form action="accesspathview.php?accesspath=<?php PrintStringValue("AccessPathEncoded"); ?>" method="POST">
	<input type="hidden" name="selected_accesspaths[]" value="<?php PrintStringValue("AccessPath"); ?>">

	<?php HtmlFilterBox("accesspathviewlist", 1); ?>

	<table id="accesspathviewlist" class="datatable">
	<thead>
		<tr>
			<th width="20">#</th>
			<th width="300"><?php Translate("User"); ?></th>
			<th><?php Translate("Permission"); ?></th>
		</tr>
	</thead>

	<?php if (IsProviderActive(PROVIDER_ACCESSPATH_EDIT)) : ?>
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
							<?php if (HasAccess(ACL_MOD_ACCESSPATH, ACL_ACTION_UNASSIGN)) : ?>
							<input type="submit" name="unassign" value="<?php Translate("Unassign"); ?>" class="unbtn">
							<?php endif; ?>
						</td>
						<td align="right">
							<?php if (HasAccess(ACL_MOD_ACCESSPATH, ACL_ACTION_ASSIGN)) : ?>
							<select name="selected_users[]">
								<option value="">--- <?php Translate("User"); ?> ---</option>
								<?php foreach (GetArrayValue("UserListAll") as $u) : ?>
								<option value="<?php print($u->getName()); ?>"><?php print($u->getName()); ?></option>
								<?php endforeach; ?>
							</select>

							<select name="permission">
								<option value="<?php PrintStringValue("PermNone"); ?>"><?php Translate("No permission"); ?></option>
								<option value="<?php PrintStringValue("PermRead"); ?>"><?php Translate("Read only"); ?></option>
								<option value="<?php PrintStringValue("PermReadWrite"); ?>"><?php Translate("Read &amp; Write"); ?></option>
							</select>
							<input type="submit" name="assign_permission" value="<?php Translate("Assign"); ?>" class="anbtn">
							<?php endif; ?>
						</td>
					</tr>
				</table>

			</td>
		</tr>
	</tfoot>
	<?php endif; ?>

      <tbody>
      <?php foreach(GetArrayValue("UserList") as $u) : ?>
      <tr>
        <td><?php if(IsProviderActive(PROVIDER_ACCESSPATH_EDIT) && HasAccess(ACL_MOD_ACCESSPATH, ACL_ACTION_UNASSIGN)): ?><input type="checkbox" name="selected_users[]" value="<?php print($u->getName()); ?>"><?php endif; ?></td>
        <td><a href="userview.php?username=<?php print($u->getEncodedName()); ?>"><?php print($u->getName()); ?></a></td>
        <td><?php print($u->getPermission()); ?></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
      </table><br>
      </form>

      <h2><?php Translate("Assigned groups"); ?></h2>
      <form action="accesspathview.php?accesspath=<?php PrintStringValue("AccessPathEncoded"); ?>" method="POST">
      <input type="hidden" name="selected_accesspaths[]" value="<?php PrintStringValue("AccessPath"); ?>">

      <?php HtmlFilterBox("assignedgrouplist", 1); ?>

      <table id="assignedgrouplist" class="datatable">
      <thead>
      <tr>
        <th width="20">#</th>
        <th width="300"><?php Translate("Group"); ?></th>
        <th><?php Translate("Permission"); ?></th>
      </tr>
      </thead>
      <?php if (IsProviderActive(PROVIDER_ACCESSPATH_EDIT)): ?>
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
                <?php if (HasAccess(ACL_MOD_ACCESSPATH, ACL_ACTION_UNASSIGN)): ?>
                <input type="submit" name="unassign" value="<?php Translate("Unassign"); ?>" class="unbtn">
                <?php endif; ?>
              </td>
              <td align="right">
                <?php if (HasAccess(ACL_MOD_ACCESSPATH, ACL_ACTION_ASSIGN)): ?>
                <select name="selected_groups[]">
                  <option value="">--- <?php Translate("Group"); ?> ---</option>
                  <?php foreach(GetArrayValue("GroupListAll") as $g): ?>
                  <option value="<?php print($g->getName()); ?>"><?php print($g->getName()); ?></option>
                  <?php endforeach; ?>
                </select>
                <select name="permission">
                  <option value="<?php PrintStringValue("PermNone"); ?>"><?php Translate("No permission"); ?></option>
                  <option value="<?php PrintStringValue("PermRead"); ?>"><?php Translate("Read only"); ?></option>
                  <option value="<?php PrintStringValue("PermReadWrite"); ?>"><?php Translate("Read &amp; Write"); ?></option>
                </select>
                <input type="submit" name="assign_permission" value="<?php Translate("Assign"); ?>" class="anbtn">
                <?php endif; ?>
              </td>
            </tr>
          </table>

        </td>
      </tr>
      </tfoot>
      <?php endif; ?>
      <tbody>
      <?php foreach(GetArrayValue("GroupList") as $g): ?>
      <tr>
        <td><?php if (IsProviderActive(PROVIDER_ACCESSPATH_EDIT) && HasAccess(ACL_MOD_ACCESSPATH, ACL_ACTION_UNASSIGN)): ?><input type="checkbox" name="selected_groups[]" value="<?php print($g->getName()); ?>"><?php endif; ?></td>
        <td><a href="groupview.php?groupname=<?php print($g->getEncodedName()); ?>"><?php print($g->getName()); ?></a></td>
        <td><?php print($g->getPermission()); ?></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
      </table><br>
      </form>

<?php GlobalFooter(); ?>