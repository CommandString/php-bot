<?php

namespace Commands\Manual;

use Commands\BaseCommand;
use CommandString\Env\Env;
use Common\Caches\Functions\Functions as FunctionsCache;
use Discord\Builders\CommandBuilder;
use Discord\Builders\MessageBuilder;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Interactions\Interaction;
use duzun\hQuery;

use function Common\getOptionFromInteraction;
use function Common\messageWithContent;
use function Common\newChoice;
use function Common\newPartDiscord;
use function React\Async\await;

class Functions extends BaseCommand {
    protected static array|string $name = ["manual", ["functions"]];
    
    public static function handler(Interaction $interaction): void
    {
        $functionName = getOptionFromInteraction($interaction, "functions", "query")->value;

        $message = MessageBuilder::new();

        $interaction->respondWithMessage($message->addEmbed(self::generateFunctionEmbed($functionName) ?? messageWithContent("Unable to find function.")));
    }

    public static function generateFunctionEmbed(string $functionName): ?Embed
    {
        $func = FunctionsCache::get()->getByName($functionName);

        if ($func === null) {
            return null;
        }

        $res = await(Env::get()->browser->get($func->href));
        $html = (string) $res->getBody();
        $dom = hQuery::fromHTML($html);
        $header = $func->header;

        /** @var Embed */
        $embed = newPartDiscord(Embed::class);

        $embed
            ->setTitle($func->name)
            ->setURL($func->href)
            ->setDescription("```php\n".$header."\n```".trim($dom->find(".refpurpose > .dc-title")->first()->text()))
        ;

        return $embed;
    }

    public static function autocomplete(Interaction $interaction): void
    {
        $query = getOptionFromInteraction($interaction, "functions", "query")->value;

        $results = FunctionsCache::get()->search($query);

        $choices = [];
        foreach ($results as $result) {
            $choices[] = newChoice($result->name, $result->name);
        }

        $interaction->autoCompleteResult($choices);
    }

    public static function getConfig(): CommandBuilder|array
    {
        return [];
    }
}
