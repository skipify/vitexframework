<?php


namespace vitex\helper\attribute\parsedata;


use vitex\helper\attribute\parser\ParseDataBase;
use vitex\helper\attribute\parser\ParseDataInterface;

/**
 * 钩子适配器的一些数据存储实体
 * @package vitex\helper\attribute\parser\route
 */

class HookData extends ParseDataBase implements ParseDataInterface
{
    private string $hook;

    private int $type;

    /**
     * @return string
     */
    public function getHook(): string
    {
        return $this->hook;
    }

    /**
     * @param string $hook
     */
    public function setHook(string $hook): void
    {
        $this->hook = $hook;
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * @param int $type
     */
    public function setType(int $type): void
    {
        $this->type = $type;
    }

}