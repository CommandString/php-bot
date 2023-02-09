<?php

namespace Common\Caches;

use CommandString\Env\Env;
use duzun\hQuery;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use Throwable;

use function React\Async\await;

class Func {
    public readonly ReflectionFunction|ReflectionMethod $reflection;
    public readonly string $name;
    public readonly string $href;
    public readonly ?string $header;

    public function __construct(
        public readonly array $raw
    ) {
        $this->name = $raw["name"];
        $this->href = $raw["href"];

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
                            $paramString .= "\"$default\"";
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
            $res = await(Env::get()->browser->get($this->href));
            $html = (string) $res->getBody();
            $dom = hQuery::fromHTML($html);

            $header = trim($dom->find(".description > .dc-description")->get(0)->text());
            $header = str_replace(" ", "", $header);
            $header = str_replace(" ", "", $header);
            $header = str_replace("(", "(\n   ", $header);
            $header = str_replace(",", ",\n   ", $header);
            $header = str_replace(")", "\n)", $header);
            $header = str_replace(":", ": ", $header);

            $this->header = $header;
        }
    }
}