<!DOCTYPE html>
<html>
<head>
	<title>iF.SVNAdmin | Login</title>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8">
	<meta name="Author" content="Manuel Freiholz, insaneFactory.com">
	<link rel="stylesheet" type="text/css" href="templates/ifappstyle.css">
</head>
<body style="background-color:#f9f9f9;">

<div style="text-align:center; padding:50px;">
	<div id="login">

		<div id="header">
			<img src="templates/images/logo.png" width="24" height="40" border="0" alt="iF-Logo" title="insaneFactory - Subversion administration">
			<h1><a href="http://www.insanefactory.com/">iF.SVNAdmin</a></h1>
		</div>

		<div style="padding:20px;">
			<h1><?php Translate("Login"); ?></h1>
			<p class="hdesc"><?php Translate("You must login to get access to the application."); ?></p>

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

			<div class="formdiv">
				<form action="login.php" method="POST">
					<label for="loginname"><?php Translate("Username"); ?>:</label><br>
					<input type="text" name="loginname" id="loginname" class="lineedit"><br>

					<label for="loginpass"><?php Translate("Password"); ?>:</label><br>
					<input type="password" name="loginpass" id="loginpass" class="lineedit"><br>

					<div class="formsubmit">
						<input type="submit" name="login" value="<?php Translate("Login"); ?>" class="addbtn">
					</div>
				</form>
			</div>

		<?php foreach (GetArrayValue("LocaleList") as $l): ?>
			<a href="<?php PrintCurrentScriptName(); ?>?locale=<?php print($l->getLocale()); ?>" title="<?php Translate("Translated by"); ?> <?php print($l->getAuthor()); ?>"><img src="templates/flags/<?php print($l->getLocale()); ?>.png" alt="<?php print($l->getName()); ?>"><?php print($l->getName()); ?></a>
		<?php endforeach; ?>
		</div>
	</div>
</div>

</body>
</html>