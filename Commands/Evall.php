<?php

namespace Commands;

use CommandString\Env\Env;
use Discord\Builders\CommandBuilder;
use Discord\Builders\Components\Button;
use Discord\Builders\MessageBuilder;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Interactions\Command\Option;
use Discord\Parts\Interactions\Interaction;
use Discord\Parts\User\User;
use duzun\hQuery;
use Psr\Http\Message\ResponseInterface;
use React\Http\Browser;
use React\Http\Message\ResponseException;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use stdClass;

use function Common\buildActionRowWithButtons;
use function Common\getOptionFromInteraction;
use function Common\messageWithContent;
use function Common\newButton;
use function Common\newChoice;
use function Common\newOption;
use function Common\newPartDiscord;
use function React\Async\await;

class Evall extends BaseCommand {
    protected static array|string $name = "eval";
    private static array $versions;
    private static stdClass $tokens;
    private static stdClass $userVersions;
    public const DEFAULT_PHP_VERSION = "8.1.2";

    public static function handler(Interaction $interaction): void
    {
        $version = getOptionFromInteraction($interaction, "version")->value;

        self::setUserVersion($version, $interaction->member->user);

        $interaction->respondWithMessage(messageWithContent("All code you execute will be ran in PHP version **".self::getUserVersion($interaction->member->user)."**"), true);
    }

    public static function getUserVersion(User $user): string
    {
        if (!isset(self::$userVersions)) {
            self::$userVersions = new stdClass;
        }

        return self::$userVersions->{$user->id} ?? self::DEFAULT_PHP_VERSION;
    }

    public static function getVersions(): array
    {
        if (!isset(self::$versions)) {
            self::refreshTokens();
        }

        return self::$versions;
    }

    private static function setUserVersion(string $version, User $user): void
    {
        if (!isset(self::$userVersions)) {
            self::$userVersions = new stdClass;
        }

        if (!in_array($version, self::getVersions())) {
            $version = self::DEFAULT_PHP_VERSION;
        }

        self::$userVersions->{$user->id} = $version;
    }

    public static function autocomplete(Interaction $interaction): void
    {
        $list = [];
        $versions = [];

        $allVersions = self::getVersions();

        $versionGiven = getOptionFromInteraction($interaction, "version")->value;

        if (!empty($versionGiven)) {
            foreach ($allVersions as $key => $version) {
                $chars = str_split($versionGiven);

                $similar = 0;
                $string = "";
                foreach ($chars as $char) {
                    $string .= $char;

                    if (!str_starts_with($version, $string)) {
                        break;
                    }

                    $similar++;
                }

                if ($similar) {
                    $list[] = compact('similar', 'key');
                }

            }

            foreach ($list as $version) {
                $version = self::$versions[$version["key"]];
                $versions[] = newChoice($version, $version);

                if (count($versions) >= 25) {
                    break;
                }
            }
        } else {
            foreach ($allVersions as $version) {
                $versions[] = newChoice($version, $version);

                if (count($versions) >= 25) {
                    break;
                }
            }
        }

        $interaction->autoCompleteResult($versions);
    }

    public static function refreshTokens(): bool
    {
        /** @var Browser $browser */
        $browser = Env::get()->browser;

        self::$tokens = new stdClass;

        $res = await($browser->get("https://onlinephp.io/"));

        if (!$res instanceof ResponseInterface) {
            return false;
        }

        self::$tokens->cookies = implode(";", $res->getHeader("set-cookie"));

        $dom = hQuery::fromHTML($res->getBody());

        self::$tokens->csrf = $dom->find("meta[name='csrf-token']")->attr("content");

        if (!isset(self::$versions)) {
            $versions = [];

            foreach ($dom->find("[name='php-versions-checkboxes[]']") as $box) {
                $versions[] = $box->attr("value");
            }

            self::$versions = $versions;
        }

        return true;
    }

    public static function runCode(string $code, string $version, bool $returnMessage = true): PromiseInterface
    {
        $browser = Env::get()->browser;

        $deferred = new Deferred();

        if (!isset(self::$tokens)) {
            echo "Refreshing tokens";

            if (!self::refreshTokens()) {
                $deferred->reject();
                return $deferred->promise();
            }
        }

        $code = urlencode($code);

        if (!in_array($version, self::$versions)) {
            $version = "8.1.2";
        }

        $body = "editor={$code}&php-versions%5B%5D={$version}&error-reporting%5B%5D=E_ALL&error-reporting%5B%5D=E_ERROR&error-reporting%5B%5D=E_WARNING&error-reporting%5B%5D=E_PARSE&error-reporting%5B%5D=E_NOTICE&error-reporting%5B%5D=E_STRICT&error-reporting%5B%5D=E_DEPRECATED&output=html&ajaxResult=1&_token=".self::$tokens->csrf;

        $browser->post("https://onlinephp.io/executeCode", [
            "content-type" => "application/x-www-form-urlencoded; charset=UTF-8",
            "X-CSRF-TOKEN" => self::$tokens->csrf,
            "Cookie" => self::$tokens->cookies
        ], $body)->then(static function (ResponseInterface $res) use ($returnMessage, $deferred, $version)
		{
            $dom = hQuery::fromHTML($res->getBody());

            $results = new stdClass;

            $results->stats = trim(str_replace(["  ", "\n"], ["", " "], $dom->find(".result-stats")->text()));
            $results->output = trim($dom->find(".results-html")->text());

            if (!$returnMessage) {
                $deferred->resolve($results);
            } else {
                $message = MessageBuilder::new();

                /** @var Embed $embed */
                $embed = newPartDiscord(Embed::class);

                $embed->setTitle("PHP Version - {$version}");
                $embed->setDescription("{$results->stats}\n\n```\n$results->output\n```");
                $embed->setTimestamp(time());

                $message->addEmbed($embed);
                $message->addComponent(buildActionRowWithButtons(newButton(Button::STYLE_DANGER, "Delete")->setListener(function (Interaction $interaction) {
                    $interaction->message->delete();
                }, Env::get()->discord)));

                $deferred->resolve($message);
            }
        }, static function (ResponseException $e) use ($deferred) {
            $deferred->reject($e->getResponse()->getBody());
        });

        return $deferred->promise();
    }

    public static function getConfig(): CommandBuilder|array
    {
        return (new CommandBuilder)
            ->setName(self::$name)
            ->setDescription("Run your PHP code on any version")
            ->addOption(newOption("version", "The PHP version to run your code on", Option::STRING, true)->setAutoComplete(true))
        ;
    }
}
