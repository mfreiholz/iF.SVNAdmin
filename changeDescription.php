<html>
<header>
<title>Update Description</title>
</header>
<body>
<?php
function updater($desc,$reponame){
	try {
		// connect to your database
		$db = new SQLite3('descriptions.db');
		$repodesc = $db->escapeString(&desc);
		$sqliteResult = $db->query('UPDATE repo_desc SET description="'.$repodesc.'" WHERE name="'.$reponame.'";');
		if (!$sqliteResult and $sqliteDebug) {
				// the query failed and debugging is enabled
				echo "<p>There was an error in the query.</p>";
				echo $db->lastErrorMsg();
			}
	}
	catch (Exception $exception) {
		// sqlite3 throws an exception when it is unable to connect
		echo '<p>There was an error connecting to the database!</p>';
		if ($sqliteDebug) {
			echo $exception->getMessage();
		}
	}
	
	if (!$sqliteResult and $sqliteDebug) {
		// the query failed and debugging is enabled
		echo "<p>There was an error in the query.</p>";
		echo $db->lastErrorMsg();
	}
	if(!($db->close())){
		echo "<p>Not even closed.</p>";
	}
}   

if(isset($_POST['description'])){
    updater($_POST['description'],$_POST['name']);
	echo "<script>window.opener.location.href = window.opener.location.href;</script>";
	echo "Updated.</br>";
	echo "<a href=\"javascript:self.close()\">Close Window</a>";
	echo "<script>window.close();</script>";
	echo "<script>self.close();</script>";
}
else {

	try {
		// connect to your database
		$db = new SQLite3('descriptions.db');
		
		$sqliteResult = $db->query("CREATE TABLE IF NOT EXISTS repo_desc (
			name varchar(255) NOT NULL,
			description varchar(255) NOT NULL);
			");
		if (!$sqliteResult and $sqliteDebug) {
			// the query failed and debugging is enabled
			echo "<p>There was an error in the query.</p>";
			echo $db->lastErrorMsg();
		}
	}
	catch (Exception $exception) {
		// sqlite3 throws an exception when it is unable to connect
		echo '<p>There was an error connecting to the database!</p>';
		if ($sqliteDebug) {
			echo $exception->getMessage();
		}
	}
	$name = $_GET['name'];
	$sqliteResult = $db->query("SELECT description FROM repo_desc WHERE name='".$name."';");
	if (!$sqliteResult and $sqliteDebug) {
		// the query failed and debugging is enabled
		echo "<p>There was an error in the query.</p>";
		echo $db->lastErrorMsg();
	}

	echo "<form action=\"\" method=\"post\">";
	while ($row = $sqliteResult->fetchArray()) {
		echo "<input type=text size=\"250\" name=\"description\" maxlength=\"200\" value=\"".$row['description']."\">";
		echo "<input type=hidden name=\"name\" value=\"".$name."\" />";
	}
	if(!($db->close())){
		echo "<p>Not even closed.</p>";
	}
	echo "<br><br>";
	echo "<input type=\"submit\" /><br/>";
	echo "</form>";
	echo "<br/><br/>";
	echo "<a href=\"javascript:self.close()\">Close Window</a>";
	echo "</body></html>";
}
?>
