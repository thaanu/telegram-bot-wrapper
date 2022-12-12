<?php

use Heliumframework\Telegram;

include __DIR__ . '/bootstrap.php';

$telegram = new Telegram($appConfig->bot_username, $appConfig->bot_token);
$response = $telegram->setWebhook($config->base_url."/hook.php");
echo '<pre>'; print_r($response); echo '</pre>';
exit;