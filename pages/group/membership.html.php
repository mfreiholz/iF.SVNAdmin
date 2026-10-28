<?php GlobalHeader(); ?>

<script type="text/javascript">
$(document).ready(function(){
  $("#selectallusers").click(function(){
    selectAll(this, "selusers[]");
  });
  $("#selectallgroups").click(function(){
    selectAll(this, "selgroups[]");
  });
  $("#selectallsubgroups").click(function(){
    selectAll(this, "selsubgroups[]");
  });
});
</script>

<h1><?php Translate("User and Group &lt;&gt; Group assignment"); ?></h1>
<p class="hdesc"><?php Translate("Here you can assign users to groups."); ?></p>

<form action="groupassign.php" method="POST"><?php PrintCsrfField(); ?>
  <table width="100%">

    <tbody>
      <tr>
        <td valign="top" width="48%">

          <?php HtmlFilterBox("userlist", 1); ?>

          <table id="userlist" class="datatable">
            <thead>
            <tr>
              <th width="20"><input type="checkbox" id="selectallusers"></th>
              <th><?php Translate("User"); ?></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach (GetArrayValue("UserList") as $u) : ?>
            <tr>
              <td><input type="checkbox" name="selusers[]" value="<?php print(h($u->name)); ?>"></td>
              <td><a href="userview.php?username=<?php print(h($u->getEncodedName())); ?>"><?php print(h($u->getDisplayName())); ?></a></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
          </table>

          <?php HtmlFilterBox("subgrouplist", 1); ?>

          <table id="subgrouplist" class="datatable">
            <thead>
            <tr>
              <th width="20"><input type="checkbox" id="selectallsubgroups"></th>
              <th><?php Translate("Group"); ?></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach(GetArrayValue("GroupList") as $g) : ?>
            <tr>
              <td><input type="checkbox" name="selsubgroups[]" value="<?php print(h($g->name)); ?>"></td>
              <td><a href="groupview.php?groupname=<?php print(h($g->getEncodedName())); ?>"><?php print(h($g->name)); ?></a></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
          </table>

        </td>
        <td width="30" class="asslefttoright">
          <!-- Empty column -->
        </td>
        <td valign="top" width="48%">

          <?php HtmlFilterBox("grouplist", 1); ?>

          <table id="grouplist" class="datatable">
            <thead>
            <tr>
              <th width="20"><input type="checkbox" id="selectallgroups"></th>
              <th><?php Translate("Group"); ?></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach(GetArrayValue("GroupList") as $g) : ?>
            <tr>
              <td><input type="checkbox" name="selgroups[]" value="<?php print(h($g->name)); ?>"></td>
              <td><a href="groupview.php?groupname=<?php print(h($g->getEncodedName())); ?>"><?php print(h($g->name)); ?></a></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
          </table>

        </td>
      </tr>
    </tbody>
  </table>

  <?php if (IsProviderActive(PROVIDER_GROUP_EDIT)) : ?>
  <div class="formsubmit">
    <input type="submit" name="assign" value="<?php Translate('Assign'); ?>" class="anbtn">
  </div>
  <?php endif; ?>

</form>

<p>
  <a href="grouplist.php">&#xAB; <?php Translate("Back to overview"); ?></a>
</p>

<?php GlobalFooter(); ?>
