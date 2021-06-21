<?php


class ParseOption
{
    private array|null $args;

    public function __construct(array $args)
    {
        $this->args = $args;
    }

    public function parse()
    {
        $arr = [];

        foreach ($this->args as $arg) {
            $arr = array_merge($arr, $this->parseEle($arg));
        }
        return $arr;
    }

    private function parseEle(string $val)
    {
        list($key, $val) = explode('=', $val);

        if ($key[0] != '-') {
            return null;
        }

        return [trim($key, '-') => $val];
    }


}