<?php 

    $msg = 'Hi, ' . $telegram->getUser()->first_name . "\n";
    $msg .= "I am a bot who can find you Dhivehi songs.\n";
    $msg .= "Try the following commands.\n\n";

    $commands = $telegram->getCommands();

    foreach ( $commands as $command ) {
        $msg .= $command['command'] . ' - ' . $command['description'] . "\n";
    }

    $msg .= "\n\n<b>Example:</b>\n";
    $msg .= "/find <i>finifen</i>\n\n";
    $msg .= "<i>finifen</i> is the word to search. You can replace with any other word.\n";
    $telegram->sendMessage($msg);