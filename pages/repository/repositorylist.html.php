<?php GlobalHeader(); ?>

  <h1><?php Translate("Repository management"); ?></h1>
  <p class="hdesc"><?php Translate("On this page you can view your existing repositories and create or delete an repository."); ?></p>

<?php foreach (GetArrayValue('RepositoryParentList') as $rp) : ?>

  <h2><?php Translate('Location'); ?>: <?php print($rp->description); ?><br><small>(<?php print($rp->path); ?>)</small>
  </h2>

  <?php HtmlFilterBox('repolist_' . $rp->identifier); ?>

  <form action="repositorylist.php" method="POST">
    <input type="hidden" name="pi" value="<?php print($rp->getEncodedIdentifier()); ?>">
    <!-- define the table, if just single svn repo root ,then the id is repolist_0	-->
    <table id="repolist_<?php print($rp->identifier); ?>" class="datatable">

      <thead>
      <tr>
        <?php if (IsProviderActive(PROVIDER_ACCESSPATH_EDIT) && HasAccess(ACL_MOD_ACCESSPATH, ACL_ACTION_ADD)) { ?>
          <th width="22">#</th>
        <?php } ?>
        <?php if (GetBoolValue('ShowDeleteButton') && IsProviderActive(PROVIDER_REPOSITORY_EDIT) && HasAccess(ACL_MOD_REPO, ACL_ACTION_DELETE)) { ?>
          <th width="20">#</th>
        <?php } ?>

        <th width="50" align="center"><?php Translate("Index"); ?></th>
        <th>
          <?php Translate("Repositories"); ?>
        </th>
        <th>
          <?php Translate("Repository URL"); ?>
        </th>
        <th>
          <?php Translate("Repository Description"); ?>
        </th>
        <?php if (GetBoolValue("ShowOptions")) : ?>
          <th width="150">
            <?php Translate("Options"); ?>
          </th>
        <?php endif; ?>
      </tr>
      </thead>

      <?php if (GetBoolValue("ShowDeleteButton") && IsProviderActive(PROVIDER_REPOSITORY_EDIT) && HasAccess(ACL_MOD_REPO, ACL_ACTION_DELETE)): ?>
        <tfoot>
        <tr>
          <td colspan="7">

            <table class="datatableinline">
              <colgroup>
                <col width="50%">
                <col width="50%">
              </colgroup>
              <tr>
                <td>
                  <input type="submit" name="delete" value="<?php Translate("Delete"); ?>" class="delbtn"
                         onclick="return deletionPrompt('<?php Translate("Are you sure?"); ?>');">

                  <?php if (IsProviderActive(PROVIDER_ACCESSPATH_EDIT) && HasAccess(ACL_MOD_ACCESSPATH, ACL_ACTION_DELETE)): ?>
                    <small>(<input type="checkbox" id="delete_ap" name="delete_ap" value="1" checked><label
                          for="delete_ap"> <?php Translate('+Remove configured Access-Paths'); ?></label>)</small>
                  <?php endif; ?>
                </td>
                <td align="right"></td>
              </tr>
            </table>

          </td>
        </tr>
        </tfoot>
      <?php endif; ?>

      <tbody>
      <?php
      // get all repositories list
      $list = GetArrayValue('RepositoryList');
      $list = $list[$rp->identifier];
      // $r in the $list like this：svnadmin\core\entities\Repository Object ( [name] => A03 [repoDescription] => testdesc [parentIdentifier] => 0 )
      // $index as the index
      $index = 1;
      foreach ($list as $r) :?>
        <tr>
          <?php if (IsProviderActive(PROVIDER_ACCESSPATH_EDIT) && HasAccess(ACL_MOD_ACCESSPATH, ACL_ACTION_ADD)) : ?>
            <td>
              <a href="accesspathcreate.php?pi=<?php print($r->getEncodedParentIdentifier()); ?>&amp;r=<?php print($r->getEncodedName()); ?>">
                <img src="templates/icons/addpath.png" alt="<?php Translate("Add access path"); ?>"
                     title="<?php Translate("Add access path"); ?>">
              </a>
            </td>
          <?php endif; ?>

          <?php if (GetBoolValue('ShowDeleteButton') && IsProviderActive(PROVIDER_REPOSITORY_EDIT) && HasAccess(ACL_MOD_REPO, ACL_ACTION_DELETE)) : ?>
            <td>
              <input type="checkbox" name="selected_repos[]" value="<?php print($r->name); ?>">
            </td>
          <?php endif; ?>

          <td align="center">
            <?php print($index); ?>
          </td>
          <td>
            <a href="repositoryview.php?pi=<?php print($r->getEncodedParentIdentifier()); ?>&amp;r=<?php print($r->getEncodedName()); ?>"><?php print($r->name); ?></a>
          </td>
          <td>
            <a href="<?php print($r->getURLPath()); ?>"><?php print($r->getURLPath()); ?></a>
          </td>
          <td>
            <?php if (empty($r->getDescription())) { ?>
              <span class="redfont"><?php Translate("No data!"); ?></span>
            <?php } else { ?>
              <?php print($r->getDescription()); ?>
            <?php } ?>
          </td>

          <?php if (GetBoolValue("ShowOptions")) : ?>
            <td>
              <?php if (GetBoolValue("ShowDumpOption")) : ?>
                <a href="repositorylist.php?pi=<?php print($r->getEncodedParentIdentifier()); ?>&amp;r=<?php print($r->getEncodedName()); ?>&amp;dump=true">
                  <img src="templates/icons/exportdump.png" border="0" alt="<?php Translate("Dump"); ?>"
                       title="<?php Translate("Export dump"); ?>">
                </a>
              <?php endif; ?>

              <?php if (GetBoolValue("ShowDownloadTreeOption")) : ?>
                <a href="repositorylist.php?pi=<?php print($r->getEncodedParentIdentifier()); ?>&amp;r=<?php print($r->getEncodedName()); ?>&amp;tree=true">
                  <img src="templates/icons/exportexcel.ico" border="0" alt="<?php Translate("Excel"); ?>"
                       title="<?php Translate("Export Repository Path file"); ?>">
                </a>
              <?php endif; ?>

              <?php if (GetBoolValue("ShowDownloadAccessPathOption")) : ?>
                <a href="repositorylist.php?pi=<?php print($r->getEncodedParentIdentifier()); ?>&amp;r=<?php print($r->getEncodedName()); ?>&amp;accesspath=true">
                  <img src="templates/icons/permission.ico" border="0" alt="<?php Translate("AccessPath"); ?>"
                       title="<?php Translate("Export Repository Access Path file"); ?>">
                </a>
              <?php endif; ?>
            </td>

          <?php endif; ?>
        </tr>
        <?php $index++; endforeach; ?>
      </tbody>

    </table>
  </form>

<?php endforeach; ?>

<?php GlobalFooter(); ?>