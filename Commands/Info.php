<?php

namespace Commands;

use Carbon\Carbon;
use CommandString\Env\Env;
use Discord\Builders\CommandBuilder;
use Discord\Builders\MessageBuilder;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Interactions\Interaction;

use function Common\newEmbedField;
use function Common\newPartDiscord;

class Info extends BaseCommand {
    protected static string|array $name = "info";

    public static function handler(Interaction $interaction): void
    {
        /** @var Embed $embed */
        $embed = newPartDiscord(Embed::class);

        $embed->addField(newEmbedField("Version", PHP_VERSION));
        $embed->addField(newEmbedField("Guilds", count(Env::get()->discord->guilds)));
        $embed->addField(newEmbedField("Source Code", "https://github.com/commandstring/php-bot"));
        $embed->setFooter("Started ".(new Carbon(Env::get()->started))->since());

        $interaction->respondWithMessage(MessageBuilder::new()->addEmbed($embed), true);
    }

    public static function getConfig(): CommandBuilder|array
    {
        return (new CommandBuilder)
            ->setName(self::$name)
            ->setDescription("Get info about the bot")
        ;
    }
}
