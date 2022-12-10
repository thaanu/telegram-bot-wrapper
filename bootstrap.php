<?php 

define('DHIVEHI_SONGS_URL', 'https://www.dhivehisongs.com');

define('MUSIC_DIR', __DIR__ . '/music');
define('CONVERSATION_DIR', __DIR__ . '/conversations');

$appConfig = (object) include __DIR__ . '/config.php';

date_default_timezone_set($appConfig->timezone);

$botResponses = (object) include __DIR__ . '/botResponses.php';

include __DIR__ . '/Telegram.php';