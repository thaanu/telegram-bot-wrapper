<?php 
/**
 * Telegram Wrapper Class
 * @author Ahmed Shan
 */

 namespace Heliumframework;

use Exception;

 class Telegram {

    private $downloadUrlPrefix = 'https://api.telegram.org/file/bot';
    private $downloadUrl;
    private $conversationDir, $conversationFile, $conversation;
    private $prefix = 'https://api.telegram.org/bot';
    private $token, $botUsername, $response, $userInfo = [], $updateId, $chatId, $text, $photo, $messageId, $callbackData, $availableCommands = [], $document, $audio;
    private $isCommand, $isCallbackQuery;

    public function __construct($botUsername, $token, $conversationDir = '')
    {
        $this->token = $token;
        $this->botUsername = $botUsername;
        $this->conversationDir = $conversationDir;
        $this->downloadUrl = $this->downloadUrlPrefix . $this->token . DIRECTORY_SEPARATOR;

        if ( ! empty($conversationDir) && ! is_dir($this->conversationDir) ) {
            mkdir($this->conversationDir);
        }

    }

    public function getUpdates( $limit = 100, $timeout = 0, $webhook = false )
    {
        
        if ( $webhook == true ) {
            $response = json_decode(file_get_contents('php://input'));
        } else {
            $response = $this->_sendPost('/getUpdates', [
                'offset' => -1
            ]);
        }

        if ( !empty($response->result) ) {

            // Handling poll results
            if ( isset($response->result[0]->poll) ) {
                // todo : handle polls
            }
            else {
                $this->updateId     = $response->result[0]->update_id;
                $this->userInfo     = $response->result[0]->message->from;
                $this->chatId       = $response->result[0]->message->chat->id;

                // Handle Text
                if ( isset($response->result[0]->message->text) ) {
                    $this->text         = $response->result[0]->message->text;
                }

                // Handle Photo
                if ( isset($response->result[0]->message->photo) ) {
                    $this->photo = $response->result[0]->message->photo;
                    // Download photo to storage
                    $photoData = $this->getPhoto();
                }
    
                if ( empty($response->result[0]) ) {
                    $this->messageId    = $response->result->message_id;
                } else {
                    $this->messageId    = $response->result[0]->message->message_id;
                }
                
                $this->isCommand    = ( isset($response->result[0]->message->entities) && $response->result[0]->message->entities[0]->type == 'bot_command' ? true : false);
                $this->isCallbackQuery = ( isset($response->result[0]->callback_query) ? true : false);

                // Handling Query
                if ( $this->isCallbackQuery() ) {
                    $this->callbackData = $response->result[0]->callback_query->data;
                    $this->chatId       = $response->result[0]->callback_query->message->chat->id;
                }

            }

        }

        return $this->response = $response;
    }

    public function getUpdatesFromWebhook( $limit = 100, $timeout = 0)
    {
        
        $response = json_decode(file_get_contents('php://input'));

        file_put_contents(__DIR__ . '/dump.txt', print_r($response, true));

        if ( !empty($response) ) {

            // Handling poll results
            if ( isset($response->poll) ) {
                // todo : handle polls
            }
            else {
                $this->updateId     = $response->update_id;
                $this->userInfo     = $response->message->from;
                $this->chatId       = $response->message->chat->id;

                // Handle Text
                if ( isset($response->message->text) ) {
                    $this->text         = $response->message->text;
                }

                // Handle Photo
                if ( isset($response->message->photo) ) {
                    $this->photo = $response->message->photo;
                    // Download photo to storage
                    $photoData = $this->getPhoto();
                }

                // Handle Document
                if ( isset($response->message->document) ) {
                    $this->document = $response->message->document;
                }

                // Handle Audio
                if ( isset($response->message->audio) ) {
                    $this->audio = $response->message->audio;
                }
    
                if ( empty($response) ) {
                    $this->messageId    = $response->result->message_id;
                } else {
                    $this->messageId    = $response->message->message_id;
                }
                
                $this->isCommand    = ( isset($response->message->entities) && $response->message->entities[0]->type == 'bot_command' ? true : false);
                $this->isCallbackQuery = ( isset($response->callback_query) ? true : false);

                // Handling Query
                if ( $this->isCallbackQuery() ) {
                    $this->callbackData = $response->callback_query->data;
                    $this->chatId       = $response->callback_query->message->chat->id;
                    $this->messageId = $response->callback_query->message->message_id;
                }

            }

        }

        return $this->response = $response;
    }

    public function initConversation()
    {
        $payload = [];
        $this->conversationFile = $this->conversationDir . '/' . $this->chatId . '.json';
        if ( ! file_exists($this->conversationFile) ) {
            file_put_contents($this->conversationFile, json_encode($payload));
        }
    }

    public function setConversation( $payload )
    {
        file_put_contents($this->conversationFile, json_encode($payload));
        $this->getConversation();
    }

    public function getConversation()
    {
        if ( empty($this->conversationFile) ) {
            throw new Exception('Unable to find conversation file');
        }
        return $this->conversation = (object) json_decode(file_get_contents( $this->conversationFile ), true);
    }

    public function setChatId( $chatId )
    {
        $this->chatId = $chatId;
    }

    public function getChatId()
    {
        return $this->chatId;
    }

    public function getMessageId()
    {
        return $this->messageId;
    }

    public function getText()
    {
        return $this->text;
    }

    public function getCallbackData()
    {
        return $this->callbackData;
    }

    public function getPhoto()
    {
        $photo = [];
        if ( ! empty($this->photo) ) {
            foreach ( $this->photo as $p ) {
                $photo[] = $this->getFile($p->file_id);
            }
        }
        return $photo;
    }

    public function setCommand( $command, $description )
    {
        array_push($this->availableCommands, [
            'command' => $command,
            'description' => $description
        ]);
    }

    public function getCommands()
    {
        return (object) $this->availableCommands; 
    }

    public function downloadFile( $filePath, $destinationDir = '', $customFilename = '' )
    {
        if ( ! is_dir($destinationDir) ) { mkdir($destinationDir); }
        $downloadUrl = $this->downloadUrlPrefix . $this->token .'/'. $filePath;
        $filename = basename($downloadUrl);
        if ( isset($customFilename) && ! empty($customFilename) ) {
            $filename = str_replace($filename, $customFilename, $filename);
        }
        if ( file_put_contents($destinationDir.'/'.$filename, file_get_contents($downloadUrl)) ) {
            return true;
        }
        return false;
    }

    public function getFile( $fileId )
    {
        $response = $this->_sendPost('/getFile', [
            'file_id' => $fileId
        ]);
        if ( isset($response->result) ) {
            return $response->result;
        }
        return [];
    }

    public function isCommand()
    {
        return $this->isCommand;
    }

    public function isCallbackQuery()
    {
        return $this->isCallbackQuery;
    }

    public function getUser()
    {
        return $this->userInfo;
    }

    public function extractCommand()
    {
        $ex = explode(' ', $this->getText());
        $command = substr($ex[0], 1, strlen($ex[0]));
        array_shift($ex);
        $phrase = implode(' ', $ex);
        return (object) [
            'command' => $command,
            'phrase' => $phrase
        ];
    }

    public function setWebhook($url)
    {
        $response = $this->_sendPost('/setWebhook', [
            'url' => $url
        ]);
        return $this->response = $response;
    }

    public function deleteWebhook()
    {
        $response = $this->_sendPost('/deleteWebhook');
        return $this->response = $response;
    }

    public function getWebhookInfo()
    {
        $response = $this->_sendPost('/getWebhookInfo');
        return $this->response = $response;
    }

    public function sendMessage($text)
    {
        $response = $this->_sendPost('/sendMessage', [
            'chat_id' => $this->chatId,
            'text' => $text,
            'parse_mode' => 'html'
        ]);
        return $this->response = $response;
    }

    public function replyKeyboard($text, $keyboard = [])
    {
        $response = $this->_sendPost('/sendMessage', [
            'chat_id' => $this->chatId,
            'text' => $text,
            'parse_mode' => 'html',
            'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
        ]);
        return $this->response = $response;
    }

    public function editMessageReplyMarkup($text, $keyboard = [])
    {
        $response = $this->_sendPost('/editMessageReplyMarkup', [
            'chat_id' => $this->chatId,
            'message_id' => $this->messageId,
            'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
        ]);
        return $this->response = $response;
    }

    public function sendAudio($audioUrl)
    {
        $response = $this->_sendPost('/sendAudio', [
            'chat_id' => $this->chatId,
            'audio' => $audioUrl,
            'parse_mode' => 'html'
        ]);
        return $this->response = $response;
    }

    // todo: need to figure this method out
    public function forwardMessage( $fromChatid )
    {
        $response = $this->_sendPost('/forwardMessage', [
            'chat_id' => $this->chatId,
            'from_chat_id' => $fromChatid,
            'message_id' => $this->messageId
        ]);
        return $this->response = $response;
    }

    public function sendPhoto($photoUrl, $caption = '')
    {
        $response = $this->_sendPost('/sendPhoto', [
            'chat_id' => $this->chatId,
            'photo' => $photoUrl,
            'caption' => $caption
        ]);
        return $this->response = $response;
    }

    public function sendPoll(string $question, array $options, bool $multipleChoice = false, bool $anonymous = true) 
    {
        $response = $this->_sendPost('/sendPoll', [
            'chat_id' => $this->chatId,
            'question' => $question,
            'options' => json_encode($options),
            'allows_multiple_answers' => $multipleChoice,
            'is_anonymous' => $anonymous
        ]);
        return $this->response = $response;
    }

    public function sendQuiz(string $question, array $options, int $correctAnswer, string $explanation, bool $anonymous = true )
    {
        $response = $this->_sendPost('/sendPoll', [
            'type' => 'quiz',
            'chat_id' => $this->chatId,
            'question' => $question,
            'options' => json_encode($options),
            'correct_option_id' => $correctAnswer,
            'is_anonymous' => $anonymous,
            'explanation' => $explanation
        ]);
        return $this->response = $response;
    }

    public function getDocument()
    {
        return (object) $this->document;
    }

    public function getAudio()
    {
        return (object) $this->audio;
    }

    private function _sendPost( $method, $payload = [] )
    {

        $url = $this->prefix . $this->token . $method;
          
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        if ( ! empty($payload) ) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $server_output = curl_exec($ch);
        curl_close ($ch);

        return json_decode($server_output);
    }

 }