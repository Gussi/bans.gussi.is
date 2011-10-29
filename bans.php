<?php
spl_autoload_register(function($class_name) {
	@include(__DIR__ . '/' . str_replace('_', '/', $class_name) . '.class.php');
});
$pdo = new database('mysql:host=localhost;dbname=bukkit', 'bukkit', 'bukkit');
$bans = $pdo->get_all('
	SELECT
		`name`,
		`reason`,
		`admin`,
		UNIX_TIMESTAMP(`time`) AS `time`,
		UNIX_TIMESTAMP(`temptime`) AS `temptime`,
		UNIX_TIMESTAMP(`temptime`) - UNIX_TIMESTAMP(`time`) AS `time_length`,
		UNIX_TIMESTAMP(`temptime`) - UNIX_TIMESTAMP() AS `time_left`
	FROM `banlist`
	WHERE (UNIX_TIMESTAMP(`temptime`) - UNIX_TIMESTAMP()) > 0
	ORDER BY `time_left` ASC'
);

foreach($bans as &$ban) {
	$ban['reason'] = utf8_encode($ban['reason']);
}

print(json_encode(array(
	'bans'		=> $bans,
)));
