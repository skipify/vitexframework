<?php


namespace vitex\core\attribute\sys;

use vitex\core\attribute\AttributeInterface;
use vitex\helper\attribute\parser\AttributeParserInterface;
use vitex\helper\attribute\parser\sys\HookListenerParser;

/**
 * 执行钩子的注解
 * 给方法（可以是私有的）或者静态方法 添加注解，添加钩子
 * 当在钩子的位置 emit 时的时候会调用此钩子的监听方法
 * 如果是 普通钩子 可以 #[HookListener("hookName")]
 * 如果是只执行一次的钩子可以是 #[HookListener("HookName",HookListener::TYPE_ONCE)]
 *
 * @package vitex\core\attribute\sys
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
class HookListener implements AttributeInterface
{
    /**
     * 普通的钩子监听器
     */
    const TYPE_COMMON = 1;

    /**
     * 只执行一次的钩子监听器
     */
    const TYPE_ONCE = 2;

    public function __construct(
        /**
         * 钩子名字
         * @var string
         */
        private string $hook,
        /**
         * 钩子类型
         * @var int
         */
        private int $type = self::TYPE_COMMON
    )
    {
    }

    public function getParse(): AttributeParserInterface
    {
        return new HookListenerParser();
    }

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