<?php if (/*IsAuthActive? && */ HasAccess(ACL_MOD_ROLE, ACL_ACTION_VIEW)) : ?>
<div id="rolelist">
  <h2><?php Translate('Available roles'); ?></h2>
  <table class="datatable">
  <thead>
    <tr>
      <th><?php Translate('Role'); ?></th>
      <th><?php Translate('Description'); ?></th>
    </tr>
  </thead>
  <tbody>
  <?php foreach(GetArrayValue('RoleList') as $r): ?>
  <tr>
    <td><?php print($r->getTranslatedName()); ?></td>
    <td><?php print($r->getTranslatedDescription()); ?></td>
  </tr>
  <?php endforeach; ?>
  </tbody>
  </table>
</div>
<?php endif; ?>