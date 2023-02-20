<?php

namespace Commands;

use Discord\Builders\CommandBuilder;
use Discord\Builders\MessageBuilder;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Interactions\Interaction;

use function Common\newPartDiscord;

class CodeBlocks extends BaseCommand {
    protected static array|string $name = "codeblocks";
    protected const CODEBLOCKS_GIF = "https://cdn.discordapp.com/attachments/488733308449193984/1077166661058179082/cb-example-php.gif";
    
    public static function handler(Interaction $interaction): void
    {
        $message = MessageBuilder::new();

        /** @var Embed */
        $embed = newPartDiscord(Embed::class);

        $embed
            ->setTitle("Codeblocks")
            ->setDescription("Codeblocks should be used when sending code as it provides syntax highlighting making it easier for other users to read. How does one use codeblocks? Do three back ticks followed by the name of language, \`\`\`php, then below that write your code, and then do three more back ticks to close the code block, \`\`\`.")
            ->setImage(self::CODEBLOCKS_GIF)
        ;

        $message->addEmbed($embed);

        $interaction->respondWithMessage($message);
    }

    public static function getConfig(): CommandBuilder|array
    {
        return (new CommandBuilder)
            ->setName(self::$name)
            ->setDescription("Shows the user how to use codeblocks")
        ;
    }
}
