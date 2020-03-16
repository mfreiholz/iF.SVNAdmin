<?php GlobalHeader(); ?>
  <h1><?php Translate("History management"); ?></h1>
  <p class="hdesc"><?php Translate("Here you can see a list of all the Configuration Management Officer operation history"); ?></p>

<?php HtmlFilterBox("historylist", 1); ?>

  <form action="historylist.php" method="POST">
    <table id="historylist" class="datatable">
      <thead>
      <tr>
        <?php if (HasAccess(ACL_MOD_USER, ACL_ACTION_DELETE) || HasAccess(ACL_MOD_ROLE, ACL_ACTION_ASSIGN)) { ?>
        <?php } ?>
        <th width="50" align="center"><?php Translate("Index"); ?></th>
        <th width="150" align="center">
          <?php Translate("Users"); ?>
        </th>
        <th>
          <?php Translate("Action"); ?>
        </th>
        <th>
          <?php Translate("Date"); ?>
        </th>
        <th>
          <?php Translate("Description"); ?>
        </th>
      </tr>
      </thead>


      <tbody>
      <?php $Index = 1;
      foreach (GetArrayValue("HistoryList") as $h) { ?>
        <tr>
          <td align="center"><?php print($Index); ?></td>
          <td>
            <a href="userview.php?username=<?php print($h->getUsername()); ?>"><?php print($h->getUsername()); ?></a>
          </td>
          <td>
            <?php print($h->getAction()); ?>
          </td>
          <td><?php print($h->getDate()); ?></td>
          <td><?php print($h->getDescription()); ?></td>
        </tr>
        <?php $Index++;
      } ?>
      </tbody>
    </table>
  </form>

<?php GlobalFooter(); ?>