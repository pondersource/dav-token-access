<?php
use OCP\Util;

$appId = OCA\TokenBasedDav\AppInfo\Application::APP_ID;
// Util::addScript($appId, $appId . '-main');

// fontawesome/fortawesome
Util::addStyle($appId, 'fontawesome-free/css/all.min');
Util::addStyle($appId, 'style');
script('tokenbaseddav', 'main');
?>

<div id="app">
	<div id="app-content">
		please pick a folder to share (coming soon)
		<form method="POST" id="form">
			<ul>
				<?php foreach($_['names'] as $name) { echo "<li><input type=\"checkbox\" name=\"$name\">$name</li>"; } ?>
			</ul>
			<input type="submit"/>
		</form>
	</div>
</div>