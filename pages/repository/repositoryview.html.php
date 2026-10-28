<?php GlobalHeader(); ?>

<h1><?php Translate("Repository browsing"); ?></h1>
<p class="hdesc">
  <a href="repositoryview.php?pi=<?php print(h(GetValue("Repository")->getEncodedParentIdentifier())); ?>&amp;r=<?php print(h(GetValue("Repository")->getEncodedName())); ?>"><?php print(h(GetValue("Repository")->getName())); ?></a>
  : <?php PrintStringValue("CurrentPath"); ?>
</p>

<table class="datatable">
<thead>
  <tr>
    <th width="22">
      <?php if (GetBoolValue("RepositoryRoot")) { ?>
      <a href="repositorylist.php"><img src="templates/icons/back.png" alt="TR{Back}}"></a>
      <?php } else { ?>
      <a href="repositoryview.php?pi=<?php print(h(GetValue("Repository")->getEncodedParentIdentifier())); ?>&amp;r=<?php print(h(GetValue("Repository")->getEncodedName())); ?>&amp;p=<?php PrintStringValue("BackLinkPathEncoded"); ?>"><img src="templates/icons/back.png" alt="<?php Translate("Back"); ?>"></a>
      <?php } ?>
    </th>
    <th width="22">#</th>
    <th><?php Translate("File name"); ?></th>
    <th width="150"><?php Translate("Author"); ?></th>
    <th width="80"><?php Translate("Revision"); ?></th>
    <?php if (GetBoolValue("ApacheWebLink") || GetBoolValue("CustomWebLink")) { ?>
    <th width="80"><?php Translate("Options"); ?></th>
    <?php } ?>
  </tr>
</thead>
      
<tfoot>
  <tr>
    <td>
    	<?php if (GetBoolValue("RepositoryRoot")) { ?>
      <a href="repositorylist.php"><img src="templates/icons/back.png" alt="TR{Back}}"></a>
    	<?php } else { ?>
      <a href="repositoryview.php?pi=<?php print(h(GetValue("Repository")->getEncodedParentIdentifier())); ?>&amp;r=<?php print(h(GetValue("Repository")->getEncodedName())); ?>&amp;p=<?php PrintStringValue("BackLinkPathEncoded"); ?>"><img src="templates/icons/back.png" alt="<?php Translate("Back"); ?>"></a>
      <?php } ?>
    </td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
    <?php if (GetBoolValue("ApacheWebLink") || GetBoolValue("CustomWebLink")) { ?>
    <td></td>
    <?php } ?>
  </tr>
</tfoot>

<tbody>
  <?php foreach (GetArrayValue("ItemList") as $item) { ?>
  <tr>
    <td>
      <?php if (IsProviderActive(PROVIDER_ACCESSPATH_EDIT) && HasAccess(ACL_MOD_ACCESSPATH, ACL_ACTION_ADD)) { ?>
	    <a href="accesspathcreate.php?pi=<?php print(h(GetValue("Repository")->getEncodedParentIdentifier())); ?>&amp;r=<?php print(h(GetValue("Repository")->getEncodedName())); ?>&amp;p=<?php print(h($item->getEncodedRelativePath())); ?>"><img src="templates/icons/addpath.png" alt="-" title="<?php Translate("Add access path"); ?>"></a>
      <?php } ?>
    </td>
    <td>
      <?php if ($item->type == 0) { ?>
      <img src="templates/icons/folder.png" border="0" alt="-">
      <?php } else { ?>
      <img src="templates/icons/file.png" border="0" alt="-">
      <?php } ?>
    </td>
    <td>
    	<?php if ($item->type == 0) { ?>
      <a href="repositoryview.php?pi=<?php print(h(GetValue("Repository")->getEncodedParentIdentifier())); ?>&amp;r=<?php print(h(GetValue("Repository")->getEncodedName())); ?>&amp;p=<?php print(h($item->getEncodedRelativePath())); ?>"><?php print(h($item->name)); ?></a>
      <?php } else { ?>
			<?php print(h($item->name)); ?>
      <?php } ?>
    </td>
    <td>
    	<?php print(h($item->author)); ?>
    </td>
    <td align="right">
    	<?php print(h($item->revision)); ?>
    </td>
    <?php if (GetBoolValue("ApacheWebLink") || GetBoolValue("CustomWebLink")) { ?>
    <td align="center">
      <?php if (GetBoolValue("ApacheWebLink")) { ?><a href="<?php print(h($item->apacheWebLink)); ?>" target="_blank"><img src="templates/images/apache-icon.png" alt="A" title="Apache WebDAV"></a><?php } ?>
      <?php if (GetBoolValue("CustomWebLink")) { ?><a href="<?php print(h($item->customWebLink)); ?>" target="_blank"><img src="templates/images/weblink-icon.gif" alt="W" title="Custom Subversion Browser"></a><?php } ?>
    </td>
    <?php } ?>
  </tr>
  <?php } ?>
</tbody>

</table>

<?php GlobalFooter(); ?>