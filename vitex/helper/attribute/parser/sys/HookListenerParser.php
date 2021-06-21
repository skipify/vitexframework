<?php


namespace vitex\helper\attribute\parser\sys;


use vitex\core\attribute\sys\HookListener;
use vitex\helper\attribute\parsedata\HookData;
use vitex\helper\attribute\parser\AttributeParserBase;
use vitex\helper\attribute\parser\AttributeParserInterface;
use vitex\Vitex;

/**
 * 解析钩子的监听注解
 * @package vitex\helper\attribute\parser\sys
 */
class HookListenerParser extends AttributeParserBase implements AttributeParserInterface
{
    private HookData $data;

    public function parse(\ReflectionAttribute $attribute, $instance = null, $reflectInstance = null)
    {
        $data = new HookData();

        /**
         * @var $instance HookListener
         */
        $instance = $instance ? $instance : $attribute->newInstance();

        $data->setParse($this);

        $data->setHook($instance->getHook());
        $data->setType($instance->getType());

        $this->data = $data;
        return $this->data;
    }

    public function doFinal(array $attributes)
    {
        $vitex = Vitex::getInstance();
        /**
         * @var $target \ReflectionMethod
         */
        $target = $this->data->getTarget();
        if ($target->isStatic()) {
            $callable = [$target->getDeclaringClass()->getName(), $target->getName()];
        } else {
            $classInstance = $vitex->container->get($target->getDeclaringClass()->getName());
            $callable = $target->getClosure($classInstance);
        }

        if ($this->data->getType() == HookListener::TYPE_COMMON) {
            $vitex->on($this->data->getHook(), $callable);
        } else {
            $vitex->once($this->data->getHook(), $callable);
        }
    }
}