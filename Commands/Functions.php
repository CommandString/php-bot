<?php

namespace Commands;

use Discord\Builders\CommandBuilder;
use Discord\Parts\Interactions\Command\Option;
use Discord\Parts\Interactions\Interaction;

use function Common\newOption;

class Functions extends BaseCommand {
    protected static array|string $name = "functions";
    
    public static function handler(Interaction $interaction): void
    {
        
    }

    public static function autocomplete(Interaction $interaction): void
    {
        
    }

    public static function getConfig(): CommandBuilder|array
    {
        return (new CommandBuilder)
            ->setName(self::$name)
            ->setDescription("Search for functions on PHP website")
            ->addOption(newOption("query", "Search Query", Option::STRING, true)->setAutoComplete(true))
        ;
    }
}
