<?php GlobalHeader(); ?>

<h1>
	<?php Translate("Welcome"); ?>
  <?php if (IsUserLoggedIn()) { ?><?php SessionUsername(); ?><?php } ?>
</h1>

<table class="datatable">
  <colgroup>
    <col width="150">
    <col>
  </colgroup>
  <thead>
    <tr>
      <th colspan="2"><?php Translate("General information"); ?></th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><?php Translate("Application version"); ?></td>
      <td><?php AppVersion(); ?> <small>(<a href="http://svnadmin.insanefactory.com/"><?php Translate("Check for updates"); ?></a>)</small></td>
    </tr>
    <tr>
      <td><?php Translate("PHP version"); ?></td>
      <td><?php PrintStringValue("PHPVersion"); ?></td>
    </tr>
    <?php if (IsUserLoggedIn()) { ?>
    <tr>
      <td><?php Translate("Logged in as"); ?></td>
      <td><?php SessionUsername(); ?></td>
    </tr>
    <?php } else { ?>
    <tr>
      <td><?php Translate("Logged in as"); ?></td>
      <td><?php Translate("Guest"); ?></td>
    </tr>
    <?php } ?>
    <?php if (IsProviderActive(PROVIDER_AUTHENTICATION)) { ?>
    <tr>
      <td><?php Translate("Roles of user"); ?></td>
      <td>
        <ul>
          <?php foreach (GetArrayValue("Roles") as $r) { ?>
          <li><?php Translate($r->name); ?> - <i><?php Translate($r->description); ?></i></li>
          <?php } ?>
        </ul>
      </td>
    </tr>
    <?php } ?>
  </tbody>
</table>


<?php GlobalFooter(); ?>