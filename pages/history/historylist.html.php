<?php GlobalHeader(); ?>

  <h1><?php Translate("History management"); ?></h1>
  <p class="hdesc"><?php Translate("Here you can see a list of all the Configuration Management Officer operation history"); ?></p>

<?php HtmlFilterBox("historylist", 1); ?>
<p><span><?php Translate("History items number:") . PrintStringValue("HistoryNumber");?></span></p>
  <form action="historylist.php" method="POST">
    <input type="submit" name="one_day" value="<?php Translate("Show one day items"); ?>">&nbsp;
    <input type="submit" name="yesterday" value="<?php Translate("Show yesterday items"); ?>">&nbsp;
    <input type="submit" name="one_week" value="<?php Translate("Show one week items"); ?>">&nbsp;
    <input type="submit" name="one_month" value="<?php Translate("Show one month items"); ?>">&nbsp;
    <input type="submit" name="show_all" value="<?php Translate("Show all items"); ?>">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    <span><?php Translate("Pages: " ). PrintStringValue("allPages") . Translate(" Current page: ") . PrintStringValue("currentPage"); ?></span>
    <input type="hidden" name="prePage" id="prePage" value="<?php PrintStringValue("prePage");?>">
    <input type="hidden" name="nextPage" id="nextPage" value="<?php PrintStringValue("nextPage");?>">
    <input type="submit" name="pre_page" value="<?php Translate("Previous"); ?>">&nbsp;
    <input type="submit" name="next_page" value="<?php Translate("Next"); ?>">&nbsp;
    <br>
    <br>
    <table id="historylist" class="datatable">
      <thead>
      <tr>
        <?php if (HasAccess(ACL_MOD_USER, ACL_ACTION_DELETE) || HasAccess(ACL_MOD_ROLE, ACL_ACTION_ASSIGN)) { ?>
        <?php } ?>
        <th width="50" align="center">
          <?php Translate("Index"); ?>
        </th>
        <th width="50" align="center">
          <?php Translate("ID"); ?>
        </th>
        <th width="150" align="center">
          <?php Translate("Users"); ?>
        </th>
        <th width="200">
          <?php Translate("Action"); ?>
        </th>
        <th width="300" align="center">
          <?php Translate("Date"); ?>
        </th>
        <th>
          <?php Translate("Description"); ?>
        </th>
      </tr>
      </thead>

      <tbody>
      <?php
      $index = 1;
      foreach (GetArrayValue("HistoryList") as $h) { ?>
        <tr>
          <td align="center"><?php print($index); ?></td>
          <td align="center"><?php print($h->getID()); ?></td>
          <td>
            <a href="userview.php?username=<?php print($h->getUsername()); ?>"><?php print($h->getUsername()); ?></a>
          </td>
          <td>
            <?php print($h->getAction()); ?>
          </td>
          <td align="center"><?php print($h->getDate()); ?></td>
          <td><?php print($h->getDescription()); ?></td>
        </tr>
      <?php $index++; } ?>
      </tbody>
    </table>
  </form>
<?php GlobalFooter(); ?>