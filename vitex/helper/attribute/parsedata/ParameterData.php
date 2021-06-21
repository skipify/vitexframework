<?php


namespace vitex\helper\attribute\parsedata;


use vitex\helper\attribute\parser\ParseDataBase;
use vitex\helper\attribute\parser\ParseDataInterface;

class ParameterData extends ParseDataBase implements ParseDataInterface
{
    /**
     * 参数名
     * @var string
     */
    private string $parameterName;

    /**
     * 参数类型
     * @var string
     */
    private string $parameterType;

    /**
     * @return string
     */
    public function getParameterName(): string
    {
        return $this->parameterName;
    }

    /**
     * @param string $parameterName
     */
    public function setParameterName(string $parameterName): void
    {
        $this->parameterName = $parameterName;
    }

    /**
     * @return string
     */
    public function getParameterType(): string
    {
        return $this->parameterType;
    }

    /**
     * @param string $parameterType
     */
    public function setParameterType(string $parameterType): void
    {
        $this->parameterType = $parameterType;
    }


}