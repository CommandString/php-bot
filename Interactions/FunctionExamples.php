<?php

namespace Interactions;

use Commands\Evall;
use Commands\Manual\Functions as ManualFunctions;
use Common\Caches\Functions\Functions;
use Discord\Builders\Components\Button;
use Discord\Builders\MessageBuilder;
use Discord\Parts\Interactions\Interaction;
use Discord\Discord;
use Discord\Parts\Embed\Embed;

use function Common\buildActionRowWithButtons;
use function Common\newButton;
use function Common\newPartDiscord;

class FunctionExamples extends BaseInteraction {
    protected static string $id = "FunctionExamples";

    public static function handler(Interaction $interaction, Discord $discord, string $funcName = null, int $exampleId = -1, int $runCode = 0)
    {
        $func = Functions::get()->getByName($funcName);
        
        $exampleId = $interaction->data->values[0] ?? $exampleId;
        $example = $func->examples[$exampleId] ?? null;

        if (is_null($example)) {
            $interaction->updateMessage(ManualFunctions::generateFunctionMessage($func->name));
            return;
        }
        
        $code = $example["code"];

        if (!$runCode) {
            $message = MessageBuilder::new();

            /** @var Embed $embed */
            $embed = newPartDiscord(Embed::class);

            $embed->setTitle($example["title"]);
            $embed->setDescription("```php\n{$code}\n```");

            $message->addEmbed($embed);

            $message->addComponent(buildActionRowWithButtons(newButton(Button::STYLE_PRIMARY, "Function Header", "FunctionExamples|{$func->name}"), newButton(Button::STYLE_SUCCESS, "Run Example", "FunctionExamples|{$func->name}|$exampleId|1")));

            $interaction->updateMessage($message);
            return;
        }

        Evall::runCode($code, Evall::DEFAULT_PHP_VERSION, true)->then(function ($reply) use ($interaction) {
            $interaction->updateMessage($reply);
        });
    }
}