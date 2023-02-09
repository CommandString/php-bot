<?php

namespace Interactions;

use Commands\Manual\Functions as ManualFunctions;
use Common\Caches\Functions\Functions;
use Discord\Builders\MessageBuilder;
use Discord\Parts\Interactions\Interaction;
use Discord\Discord;
use Discord\Parts\Embed\Embed;

use function Common\messageWithContent;
use function Common\newPartDiscord;

class FunctionExamples extends BaseInteraction {
    protected static string $id = "FunctionExamples";

    public static function handler(Interaction $interaction, Discord $discord, string $funcName = null)
    {
        $func = Functions::get()->getByName($funcName);

        $example = $func->examples[$interaction->data->values[0]] ?? null;

        if (is_null($example)) {
            $interaction->updateMessage(ManualFunctions::generateFunctionMessage($func->name));
            return;
        }

        $code = "```php\n{$example["code"]}\n```";

        $message = MessageBuilder::new();

        /** @var Embed $embed */
        $embed = newPartDiscord(Embed::class);

        $embed->setTitle($example["title"]);
        $embed->setDescription($code);

        $message->addEmbed($embed);

        $interaction->updateMessage($message);
    }
}