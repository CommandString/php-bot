<?php

namespace Interactions;

use Common\Caches\Functions\Functions;
use Discord\Builders\Components\Option;
use Discord\Builders\Components\StringSelect;
use Discord\Builders\MessageBuilder;
use Discord\Parts\Interactions\Interaction;
use Discord\Discord;

use function Common\messageWithContent;

class FunctionExamples extends BaseInteraction {
    protected static string $id = "FunctionExamples";

    public static function handler(Interaction $interaction, Discord $discord, string $funcName = null, int $menu = 0)
    {
        $func = Functions::get()->getByName($funcName);

        if (!$menu) {            
            $menu = (new StringSelect("FunctionExamples|$funcName|1"))->setPlaceholder("Examples for $funcName");   

            $menu->addOption(new Option("Example #1 - Something something", 0));

            $interaction->respondWithMessage(MessageBuilder::new ()->addComponent($menu));
        } else {
            $interaction->updateMessage(messageWithContent("hehehe")->setComponents([]));
        }
    }
}