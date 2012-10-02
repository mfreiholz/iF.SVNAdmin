<?php GlobalHeader(); ?>

<h1><?php Translate("Repository management"); ?></h1>
<p class="hdesc"><?php Translate("On this page you can view your existing repositories and create or delete an repository."); ?></p>

<?php foreach (GetArrayValue('RepositoryParentList') as $rp) : ?>

	<h2><?php Translate('Location'); ?>: <?php print($rp->description); ?><br><small>(<?php print($rp->path); ?>)</small></h2>

	<?php HtmlFilterBox('repolist_' . $rp->identifier); ?>

	<form action="repositorylist.php" method="POST">
		<input type="hidden" name="pi" value="<?php print($rp->getEncodedIdentifier()); ?>">
		
		<table id="repolist_<?php print($rp->identifier); ?>" class="datatable">

		<thead>
			<tr>
				<th width="22"></th>
				<th width="20"></th>
				<th>
					<?php Translate("Repository"); ?>
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
				<td colspan="4">

				<table class="datatableinline">
				<colgroup>
					<col width="50%">
					<col width="50%">
				</colgroup>
				<tr>
					<td>
						<input type="submit" name="delete" value="<?php Translate("Delete"); ?>" class="delbtn" onclick="return deletionPrompt('<?php Translate("Are you sure?"); ?>');">

						<?php if (IsProviderActive(PROVIDER_ACCESSPATH_EDIT) && HasAccess(ACL_MOD_ACCESSPATH, ACL_ACTION_DELETE)): ?>
						<small>(<input type="checkbox" id="delete_ap" name="delete_ap" value="1" checked><label for="delete_ap"> <?php Translate('+Remove configured Access-Paths'); ?></label>)</small>
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
				$list = GetArrayValue('RepositoryList');
				$list = $list[$rp->identifier];
				foreach ($list as $r) :
			?>
			<tr>
				<td>
					<?php if (IsProviderActive(PROVIDER_ACCESSPATH_EDIT) && HasAccess(ACL_MOD_ACCESSPATH, ACL_ACTION_ADD)) : ?>
						<a href="accesspathcreate.php?pi=<?php print($r->getEncodedParentIdentifier()); ?>&amp;r=<?php print($r->getEncodedName()); ?>">
							<img src="templates/icons/addpath.png" alt="<?php Translate("Add access path"); ?>" title="<?php Translate("Add access path"); ?>">
						</a>
					<?php endif; ?>
				</td>
				<td>
					<?php if (GetBoolValue('ShowDeleteButton') && IsProviderActive(PROVIDER_REPOSITORY_EDIT) && HasAccess(ACL_MOD_REPO, ACL_ACTION_DELETE)) : ?>
						<input type="checkbox" name="selected_repos[]" value="<?php print($r->name); ?>">
					<?php endif; ?>
				</td>
				<td>
					<a href="repositoryview.php?pi=<?php print($r->getEncodedParentIdentifier()); ?>&amp;r=<?php print($r->getEncodedName()); ?>"><?php print($r->name); ?></a>
				</td>
				<?php if (GetBoolValue("ShowOptions")) : ?>
				<td>
					<?php if (GetBoolValue("ShowDumpOption")) : ?>
					<a href="repositorylist.php?pi=<?php print($r->getEncodedParentIdentifier()); ?>&amp;r=<?php print($r->getEncodedName()); ?>&amp;dump=true">
						<img src="templates/icons/exportdump.png" border="0" alt="<?php Translate("Dump"); ?>" title="<?php Translate("Export dump"); ?>">
					</a>
					<?php endif; ?>
				</td>
				<?php endif; ?>
			</tr>
			<?php endforeach; ?>
		</tbody>

		</table>
	</form>

<?php endforeach; ?>

<?php GlobalFooter(); ?>