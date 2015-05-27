<!DOCTYPE html>
<html>
<head>
	<title>iF.SVNAdmin | <?php PrintApplicationVersion(); ?></title>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8">
	<meta name="Author" content="Manuel Freiholz, insaneFactory.com">
	<link rel="stylesheet" type="text/css" href="templates/ifappstyle.css">
	<link rel="stylesheet" type="text/css" href="templates/jquery-ui-1.8.2.custom.css">
	<script type="text/javascript" src="templates/jquery-1.4.2.min.js"></script>
	<script type="text/javascript" src="templates/jquery-ui-1.8.2.custom.min.js"></script>
	<script type="text/javascript" src="templates/script.js"></script>
</head>
<body>
	<div id="main">

		<div id="header">
			<img src="templates/images/logo.png" width="24" height="40" border="0">
			<h1><a href="index.php">iF.SVNAdmin</a></h1>

			<?php if(IsUserLoggedIn()){ ?>
			<div id="loggedin">
				<?php Translate("Welcome"); ?> <b><?php PrintSessionUsername(); ?>!</b>
			</div>
			<?php } ?>

			<div id="locale-selection">
				<select id="locale-selector" name="locale">
					<?php foreach (GetArrayValue("LocaleList") as $l) {?>
					<option value="<?php print($l->locale); ?>"
						style="background-image:url('templates/flags/<?php print($l->locale); ?>.png');"
						<?php if (CurrentLocale() == $l->locale) { ?> selected="selected"<?php } ?>>
						<?php print($l->name); ?> (<?php Translate("Translated by"); ?> <?php print($l->author); ?>)
					</option>
					<?php } ?>
				</select>
			</div>

			<div class="clear"></div>
		</div>

<?php include_once("pages/global-navigation.php"); ?>

	<div id="contentarea">
		<div id="textarea">

			<?php if (HasAppExceptions()) : ?>
			<div class="top-message top-message-error">
				<div class="top-message-header">
					<h3><?php Translate("Exception list"); ?></h3>
				</div>
				<div class="top-message-content">
					<ul>
						<?php foreach (AppEngine()->getExceptions() as $item) : ?>
						<li><?php print($item->getMessage()); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
			</div>
			<?php endif; ?>

			<?php if (HasAppMessages()) : ?>
			<div class="top-message top-message-info">
				<div class="top-message-header">
					<h3><?php Translate("Message list"); ?></h3>
				</div>
				<div class="top-message-content">
					<ul>
						<?php foreach (AppEngine()->getMessages() as $item) : ?>
						<li><?php print($item); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
			</div>
			<?php endif; ?>
