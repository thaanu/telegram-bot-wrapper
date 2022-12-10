<?php

use Heliumframework\Telegram;

include __DIR__ . '/bootstrap.php';

try {

    $telegram = new Telegram($appConfig->bot_username, $appConfig->bot_token, CONVERSATION_DIR);

    // Set the commands
    $telegram->setCommand('/sample', 'Sample command');

    // get updates from webhook
    $response = $telegram->getUpdatesFromWebhook();

    // Save the last conversation for reference
    file_put_contents(__DIR__ . '/dump.txt', print_r($response, true));

    // Handle On-Going Converstation
    $telegram->initConversation();
    $userConv = $telegram->getConversation();
    if ( isset($userConv->command) ) {
        $command = $userConv->command;
        $request = $userConv->request;
        $telegram->setConversation([]); // Reset converstation
        include(__DIR__ . "/actions/command_$command.php");
        exit;
    }

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

    // Handle Call Back Queries
    if ( $telegram->isCallbackQuery() ) {

        $req = $telegram->getCallbackData();
        $ex = explode('?', $req);
        $action = $ex[0];
        $id = $ex[1];
        
        $secondAction = (isset($ex[2]) ? $ex[2] : '');
        $currentPage = (isset($ex[3]) ? $ex[3] : 1);
        $replyToMsgId = (isset($ex[4]) ? $ex[4] : '');
        $navigating = ( ! empty($secondAction) ? true : false );

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

}
catch ( Exception $ex ) {
    file_put_contents(__DIR__ . '/errors.log', $ex->getMessage());
}