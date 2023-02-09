<?php

namespace Common\Caches\Functions;

use CommandString\Env\Env;
use duzun\hQuery;
use Psr\Http\Message\ResponseInterface;
use React\Http\Browser;

use function React\Async\await;

final class Functions {
    /**
     * @var Func[]
     */
    private array $functions = [];
    private array $raw = [];
    private static self $instance;

    public function __construct()
    {
        $this->raw = self::fetchCache();

        self::$instance = $this;
    }

    public static function get(): self
    {
        return self::$instance ?? new self();
    }

    private static function fetchCache(): array
    {
        foreach (scandir(__DIR__) as $file_name) {
            if (preg_match("/(?P<string>functions.*.json)/", $file_name)) {
                $file_path = __DIR__ . "/$file_name";
            }
        }

        if (!file_exists($file_path)) {
            self::updateCache();
            return self::fetchCache();
        }
        
        $timestamp = (int)explode(".", $file_name)[1];

        if (time() - $timestamp > 604800) {
            unlink($file_path);
            self::updateCache();
            return self::fetchCache();
        }

        return json_decode(file_get_contents($file_path));
    }

    private static function updateCache(): void
    {
        /** @var Browser */
        $browser = Env::get("browser");

        /** @var ResponseInterface */
        $res = await($browser->get("https://www.php.net/manual/en/indexes.functions.php"));
        $html = (string) $res->getBody();
        $dom = hQuery::fromHTML($html);

        $cache = [];

        foreach ($dom->find("a.index") as $item) {
            $cacheItem["name"] = $item->text();
            $cacheItem["href"] = $item->attr("href");
            
            $cache[] = $cacheItem;
        }

        file_put_contents(__DIR__."/functions.".time().".json", json_encode($cache));
    }

    public function getByName(string $name): ?Func
    {
        foreach ($this->functions as $function) {
            if ($function->name === $name) {
                return $function;
            }
        }

        foreach ($this->raw as $function) {
            if ($function->name === $name) {
                $func = new Func($function);
                $this->functions[] = $func;
                return $func;
            }
        }

        return null;
    }

    public function search(string $name, int $limit = 25): ?array
    {
        $list = [];

        foreach ($this->raw as $key => $func) {
            $similar = levenshtein($func->name, $name);

            $list[] = [
                "similar" => $similar,
                "key" => $key
            ];
        }

        usort($list, function ($a, $b) {
            return ($a["similar"] > $b["similar"]);
        });

        $items = [];

        foreach ($list as $func) {
            $items[] = $this->raw[$func["key"]];

            if (count($items) >= $limit) {
                break;
            }
        }

        return $items;
    }
}