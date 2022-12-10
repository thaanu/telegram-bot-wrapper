<?php

use Heliumframework\Telegram;

include __DIR__ . '/bootstrap.php';

$telegram = new Telegram($appConfig->bot_username, $appConfig->bot_token);

$response = $telegram->getUpdates();

// Handle Commands
if ( $telegram->isCommand() ) {

    // Split
    $command = $telegram->extractCommand()->command;
    $phrase = $telegram->extractCommand()->phrase;

    // Find the command
    $commandFile = __DIR__ . "/commands/command_$command.php";
    if ( ! file_exists($commandFile) ) {
        $telegram->sendMessage($botResponses->misunderstood);
    }
    else {
        include $commandFile;
    }


    exit; // uncomment after use

}

if ( $telegram->isCallbackQuery() ) {

    $req = $telegram->getCallbackData();
    $ex = explode('?', $req);
    $action = $ex[0];
    $id = $ex[1];

    $commandFile = __DIR__ . "/callbackqueries/callbackquery_$action.php";

    if ( file_exists($commandFile) ) {
        include $commandFile;
    }
    else {
        $telegram->sendMessage($botResponses->misunderstood);
    }

    exit; // uncomment after use

}


exit;