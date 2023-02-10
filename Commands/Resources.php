<?php

namespace Commands;

use Discord\Builders\CommandBuilder;
use Discord\Builders\MessageBuilder;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Interactions\Interaction;

use function Common\emptyEmbedField;
use function Common\newEmbedField;
use function Common\newPartDiscord;

class Resources extends BaseCommand {
    protected static array|string $name = "resources";
    private static $resources = [
        "PHPTheRightWay" => "https://phptherightway.com/",
        "Official PHP Manual" => "https://www.php.net/manual/en/",
        "W3Schools" => "https://www.w3schools.com/php/",
        "PHP Standard Recommendations" => "https://www.php-fig.org/psr/",
        "PHP Delusions" => "https://phpdelusions.net/"
    ];
    
    public static function handler(Interaction $interaction): void
    {
        /** @var Embed $embed */
        $embed = newPartDiscord(Embed::class);

        $embed->setTitle("PHP Resources");
        $embed->setDescription("Below are some resources that can guide you in your journey to becoming a PHP master.");

        foreach (self::$resources as $name => $url) {
            $embed->addField(newEmbedField($name, $url, true));

            if (count($embed->fields) % 3 === 0) {
                emptyEmbedField($embed);
            }
        }

        $interaction->respondWithMessage(MessageBuilder::new()->addEmbed($embed), true);
    }

    public static function getConfig(): CommandBuilder|array
    {
        return (new CommandBuilder)
            ->setName(self::$name)
            ->setDescription("Get a list of resources for PHP")
        ;
    }
}
