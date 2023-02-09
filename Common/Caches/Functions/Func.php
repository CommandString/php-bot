<?php

namespace Common\Caches\Functions;

use CommandString\Env\Env;
use duzun\hQuery;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use stdClass;
use Throwable;

use function React\Async\await;

class Func {
    public readonly ReflectionFunction|ReflectionMethod $reflection;
    public readonly string $name;
    public readonly string $href;
    public readonly ?string $header;
    public readonly array $examples;

    public function __construct(
        public readonly stdClass $raw
    ) {
        $this->name = $raw->name;
        $this->href = $raw->href;

        $res = await(Env::get()->browser->get($this->href));
        $html = (string) $res->getBody();
        $dom = hQuery::fromHTML($html);

        # GENERATE METHOD HEADER
        try {
            if (str_contains($this->name, "::")) {
                $parts = explode("::", $this->name);
                $reflectionClass = new ReflectionClass($parts[0]);
                $this->reflection = $reflectionClass->getMethod($parts[1]);
            } else {
                $this->reflection = new ReflectionFunction($this->name);
            }

            $header = "{$this->name}(";

            foreach ($this->reflection->getParameters() as $param) {
                $paramString = "\n   ";

                if ($param->hasType()) {
                    $paramString .= "{$param->getType()} ";
                }

                if ($param->isVariadic()) {
                    $paramString .= "...";
                }

                if ($param->isPassedByReference()) {
                    $paramString .= "&";
                }

                $paramString .= "$" . $param->getName();

                if ($param->isDefaultValueAvailable()) {
                    $paramString .= " = ";

                    if ($default = $param->getDefaultValueConstantName()) {
                        $paramString .= $default;
                    } else {
                        $default = $param->getDefaultValue();

                        if (is_string($default)) {
                            $paramString .= "\"{$default}\"";
                        } else if (is_int($default) || is_float($default)) {
                            $paramString .= $default;
                        } else if (is_array($default)) {
                            $paramString .= "[" . implode(", ", $default) . "]";
                        } else if (is_bool($default)) {
                            $paramString .= $default ? "true" : "false";
                        }
                    }
                }

                $paramString .= ",";

                $header .= $paramString;
            }

            if (empty($this->reflection->getParameters())) {
                $header .= ")";
            } else {
                $header = substr($header, 0, -1) . "\n)";
            }


            if ($this->reflection->hasReturnType()) {
                $header .= ": {$this->reflection->getReturnType()}";
            }

            $this->header = $header;
        } catch (Throwable) {
            $headerElement = $dom->find(".description > .dc-description");

            if (is_null($headerElement)) {
                $this->header = "/* Cannot Generate Header */";
            } else {
                $header = trim($headerElement->get(0)->text());
				$header = str_replace([
					" ",
					" "
				], "", $header);

                if (!str_contains($header, "()")) {
					$header = str_replace([
						"(",
						",",
						")",
						"=",
						"$"
					], [
						"(\n   ",
						",\n   ",
						"\n)",
						" = ",
						" $"
					], $header);
                }

				$header = str_replace([
					":",
					": : "
				], [
					": ",
					"::"
				], $header);

                $this->header = $header;
            }
        }

        # GET EXAMPLES
        $examples = [];

        foreach ($dom->find("div.example") ?? [] as $example) {

            $title = trim($example->find("p > strong")->text());

            $code = hQuery::fromHTML(str_replace(["<br>", "<br />"], "\n", $example->find("code")->html()))->text();

            $examples[] = compact('title', 'code');
        }

        $this->examples = $examples;
    }
}
