<?php
header('Content-Type: application/xml; charset=utf-8');
$login = isset($_GET['login']) ? $_GET['login'] : '';
$login = htmlspecialchars($login, ENT_QUOTES, 'UTF-8');
echo "<?xml version='1.0' encoding='utf-8' ?>\n";
?>
<maniacode>
	<add_favourite>
		<login><?php echo $login; ?></login>
	</add_favourite>
</maniacode>
