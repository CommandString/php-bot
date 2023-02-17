<?php

namespace Commands\Manual;

use Commands\BaseCommand;
use Discord\Builders\CommandBuilder;
use Discord\Parts\Interactions\Command\Option;
use Discord\Parts\Interactions\Interaction;

use function Common\newOption;

class Manual extends BaseCommand {
    protected static array|string $name = "manual";

    public static function handler(Interaction $interaction): void
    {
        // check other files
    }

    public static function getConfig(): CommandBuilder|array
    {
        $newOption = static function (string $name): Option
        {
            return newOption($name, "Search for {$name} in the PHP manual.", Option::SUB_COMMAND)
                ->addOption(newOption("query", "Search Query", Option::STRING, true)->setAutoComplete(true))
            ;
        };

        return (new CommandBuilder)
            ->setName(self::$name)
            ->setDescription("Search the PHP manual")
            ->addOption($newOption("functions"))
        ;
    }
}
