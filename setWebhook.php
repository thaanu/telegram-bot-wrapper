<?php

use Heliumframework\Telegram;

include __DIR__ . '/bootstrap.php';

$telegram = new Telegram($appConfig->bot_username, $appConfig->bot_token);
$url = $appConfig->base_url."/hook.php";
$response = $telegram->setWebhook($url);

echo "Setting webook to -> $url\n";
print_r($response);
exit;