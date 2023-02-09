<?php

namespace Common\Caches;

use CommandString\Env\Env;
use duzun\hQuery;
use Psr\Http\Message\ResponseInterface;
use React\Http\Browser;

use function React\Async\await;

final class Functions {
    private array $items = [];
    private static self $instance;

    public function __construct()
    {
        $this->items = self::fetchCache();

        self::$instance = $this;
    }

    public static function get(): self
    {
        return self::$instance ?? new self();
    }

    private static function fetchCache(): array
    {
        foreach (scandir(__DIR__) as $file) {
            if (preg_match("/(?P<string>functions.*.json)/", $file)) {
                $file_dir = __DIR__ . "/$file";
            }
        }

        if (!file_exists($file_dir)) {
            self::updateCache();
            return self::fetchCache();
        }
        
        $timestamp = (int)explode(".", $file)[1];

        if (time() - $timestamp > 604800) {
            self::updateCache();
            return self::fetchCache();
        }

        return json_decode(file_get_contents($file_dir), true);
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
        $func = $this->getByNameRaw($name);

        if ($func === null) {
            return null;
        }

        return new Func($func);
    }

    public function getByNameRaw(string $name): ?array
    {
        foreach ($this->items as $item) {
            if ($item["name"] === $name) {
                return $item;
            }
        }

        return null;
    }

    public function search(string $name, int $limit = 25): ?array
    {
        $list = [];

        foreach ($this->items as $key => $item) {
            $itemName = $item["name"];

            if (str_contains($itemName, "::")) {
                $itemName = explode("::", $itemName)[1];
            }

            $similar = levenshtein($item["name"], $name);

            $list[] = [
                "similar" => $similar,
                "key" => $key
            ];
        }

        usort($list, function ($a, $b) {
            return ($a["similar"] > $b["similar"]);
        });

        $items = [];

        foreach ($list as $item) {
            $items[] = $this->items[$item["key"]];

            if (count($items) >= $limit) {
                break;
            }
        }

        return $items;
    }
}