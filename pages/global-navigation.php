  <div id="topnav">
    <ul class="ddm">

    <li><a href="index.php"><?php Translate("Home"); ?></a></li>

    <?php if (IsProviderActive(PROVIDER_REPOSITORY_VIEW) && (HasAccess(ACL_MOD_REPO, ACL_ACTION_VIEW) || HasAccess(ACL_MOD_REPO, ACL_ACTION_ADD))) { ?>
    <li><a href="repositorylist.php"><?php Translate("Repositories"); ?></a>
      <ul class="ddm-sub">
			<?php if (HasAccess(ACL_MOD_REPO, ACL_ACTION_VIEW)){?><li><a href="repositorylist.php"><?php Translate("List"); ?></a></li><?php } ?>
			<?php if (IsProviderActive(PROVIDER_REPOSITORY_EDIT) && HasAccess(ACL_MOD_REPO, ACL_ACTION_ADD)){?><li><a href="repositorycreate.php"><?php Translate("Add"); ?></a></li><?php } ?>
      </ul>
    </li>
    <?php } ?>

    <?php if (IsProviderActive(PROVIDER_USER_VIEW) && (HasAccess(ACL_MOD_USER, ACL_ACTION_VIEW) || HasAccess(ACL_MOD_USER, ACL_ACTION_ADD))) { ?>
    <li><a href="userlist.php"><?php Translate("Users"); ?></a>
      <ul class="ddm-sub">
      <?php if (HasAccess(ACL_MOD_USER, ACL_ACTION_VIEW)){?><li><a href="userlist.php"><?php Translate("List"); ?></a></li><?php } ?>
      <?php if (IsProviderActive(PROVIDER_USER_EDIT) && HasAccess(ACL_MOD_USER, ACL_ACTION_ADD)){?><li><a href="usercreate.php"><?php Translate("Add"); ?></a></li><?php } ?>
      </ul>
    </li>
    <?php } ?>

		<?php if (IsProviderActive(PROVIDER_GROUP_VIEW) && (HasAccess(ACL_MOD_GROUP, ACL_ACTION_VIEW) || HasAccess(ACL_MOD_GROUP, ACL_ACTION_ADD) || HasAccess(ACL_MOD_GROUP, ACL_ACTION_ASSIGN))) { ?>
    <li><a href="grouplist.php"><?php Translate("Groups"); ?></a>
      <ul class="ddm-sub">
      <?php if (HasAccess(ACL_MOD_GROUP, ACL_ACTION_VIEW)){?><li><a href="grouplist.php"><?php Translate("List"); ?></a></li><?php } ?>
      <?php if (IsProviderActive(PROVIDER_GROUP_EDIT) && HasAccess(ACL_MOD_GROUP, ACL_ACTION_ADD)){?><li><a href="groupcreate.php"><?php Translate("Add"); ?></a></li><?php } ?>
      <?php if (IsProviderActive(PROVIDER_GROUP_EDIT) && HasAccess(ACL_MOD_GROUP, ACL_ACTION_ASSIGN)){?><li><a href="usergroupassign.php"><?php Translate("Memberships"); ?></a></li><?php } ?>
      </ul>
    </li>
    <?php } ?>

    <?php if (IsProviderActive(PROVIDER_ACCESSPATH_VIEW) && (HasAccess(ACL_MOD_ACCESSPATH, ACL_ACTION_VIEW) || HasAccess(ACL_MOD_ACCESSPATH, ACL_ACTION_ADD) || HasAccess(ACL_MOD_ACCESSPATH, ACL_ACTION_ASSIGN))) { ?>
    <li><a href="accesspathslist.php"><?php Translate("Access-Paths"); ?></a>
      <ul class="ddm-sub">
      <?php if (HasAccess(ACL_MOD_ACCESSPATH, ACL_ACTION_VIEW)){?><li><a href="accesspathslist.php"><?php Translate("List"); ?></a></li><?php } ?>
      <?php if (IsProviderActive(PROVIDER_ACCESSPATH_EDIT) && HasAccess(ACL_MOD_ACCESSPATH, ACL_ACTION_ADD)){?><li><a href="accesspathcreate.php"><?php Translate("Add"); ?></a></li><?php } ?>
      <?php if (IsProviderActive(PROVIDER_ACCESSPATH_EDIT) && HasAccess(ACL_MOD_ACCESSPATH, ACL_ACTION_ASSIGN)){?><li><a href="permissionassign.php"><?php Translate("Permissions"); ?></a></li><?php } ?>
      </ul>
    </li>
    <?php } ?>

    <?php if (IsViewUpdateable() && HasAccess(ACL_MOD_UPDATE, ACL_ACTION_SYNCHRONIZE) && AppEngine()->getConfig()->getValueAsBoolean('GUI', 'AllowUpdateByGui', true)) {?>
    <li><a href="update.php"><?php Translate("Update"); ?></a>
      <ul class="ddm-sub">
      <?php if (HasAccess(ACL_MOD_UPDATE, ACL_ACTION_SYNCHRONIZE)){?><li><a href="update.php"><?php Translate("Synchronize"); ?></a></li><?php } ?>
      </ul>
    </li>
    <?php } ?>

    <?php if (HasAccess(ACL_MOD_SETTINGS, ACL_ACTION_CHANGE)) { ?>
    <li><a href="settings.php"><?php Translate("Settings"); ?></a>
      <ul class="ddm-sub">
        <li><a href="settings.php"><?php Translate("Backend"); ?></a></li>
      </ul>
    </li>
    <?php } ?>

    <?php if (IsUserLoggedIn()) { ?>
    <li><a href="#"><?php Translate("Session"); ?></a>
      <ul class="ddm-sub">
        <?php if (IsProviderActive(PROVIDER_USER_EDIT) && HasAccess(ACL_MOD_USER, ACL_ACTION_CHANGEPASS)) { ?><li><a href="userchangepass.php?username=<?php SessionUsername(); ?>"><?php Translate("Change password"); ?></a></li><?php } ?>
        <li><a href="logout.php"><?php Translate("Logout"); ?></a></li>
      </ul>
    </li>
    <?php } ?>

    </ul>
    <div class="clear"></div>
  </div>