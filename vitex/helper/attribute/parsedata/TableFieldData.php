<?php


namespace vitex\helper\attribute\parsedata;


use vitex\helper\attribute\parser\ParseDataBase;
use vitex\helper\attribute\parser\ParseDataInterface;

class TableFieldData extends ParseDataBase implements ParseDataInterface
{
    private string $alias;

    /**
     * @return string
     */
    public function getAlias(): string
    {
        return $this->alias;
    }

    /**
     * @param string $alias
     */
    public function setAlias(string $alias): void
    {
        $this->alias = $alias;
    }

}