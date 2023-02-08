<?php

namespace Common\Caches;

use CommandString\Env\Env;
use Psr\Http\Message\ResponseInterface;
use React\Http\Browser;
use stdClass;

use function React\Async\await;

final class Functions {
    private static array $cacheInfo = [
        "filename" => "functions.json",
        "endpoint" => ""
    ];

    private array $items = [];

    public function __construct()
    {
        $info = self::$cacheInfo;

        if (!file_exists(self::buildPath($info["filename"]))) {
            self::updateCache();
        }

        $items = json_decode(file_get_contents(self::buildPath($info["filename"])));
    }

    private static function updateCache(): void
    {
        /** @var Browser */
        $browser = Env::get("browser");

        /** @var ResponseInterface */
        $res = await($browser->get("https://www.php.net/manual/en/indexes.functions.php"));
        $html = (string) $res->getBody();
        $dom = (new \DOMDocument())->loadHTML($html);

        $listParent = $dom->getElementById("indexes.functions");

        var_dump($listParent);

        $cache = [];

        foreach ($listParent->getElementsByTagName("a") as $item) {
            $cacheItem = new stdClass;

            $cacheItem->name = $item->textContent;
            $cacheItem->href = $item->attributes->getNamedItem("href");
            
            $cache[] = $cacheItem;
        }

        $cache["cached_date"] = time();

        file_put_contents(self::buildPath("functions.json"), json_encode($cache));
    }

    private static function buildPath(string $filename): string
    {
        return __DIR__."/$filename";
    }
}