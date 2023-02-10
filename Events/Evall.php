<?php

namespace Events;

use Commands\Evall as EvalCommand;
use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\Parts\Channel\Message;
use Discord\Parts\Embed\Embed;
use Discord\WebSockets\Event;

use function Common\newPartDiscord;
use function Discord\mentioned;

class Evall extends BaseEvent {
    protected static string $event = Event::MESSAGE_CREATE;
    private static int $errorDeleteTime = 10000;

    public static function handler(Message $message = null, Discord $discord = null): void
    {
        if ($message->author->id === $discord->user->id || !mentioned($discord->user, $message)) {
            return;
        }

        $code = explode('```', explode('```php', $message->content)[1] ?? "")[0] ?? "";

        if (!strlen($code)) {
            $message->reply("Unable to parse code from command")->done(function (Message $message) {
                $message->delayedDelete(self::$errorDeleteTime);
            });
            return;
        }

        $message->channel->broadcastTyping();

        EvalCommand::runCode($code, EvalCommand::getUserVersion($message->author), true)->then(function ($reply) use ($message) {
            $message->reply($reply);
        });
    }
}