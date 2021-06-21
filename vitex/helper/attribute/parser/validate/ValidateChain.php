<?php


namespace vitex\helper\attribute\parser\validate;


use vitex\core\attribute\validate\Enum;
use vitex\core\attribute\validate\IsEmail;
use vitex\core\attribute\validate\IsIdCard;
use vitex\core\attribute\validate\IsUrl;
use vitex\core\attribute\validate\Regexp;
use vitex\core\attribute\validate\Required;
use vitex\core\attribute\validate\Tfloat;
use vitex\core\attribute\validate\Tint;
use vitex\core\attribute\validate\Tstring;
use vitex\Vitex;

/**
 * 链式过滤
 * @package vitex\helper\attribute\parser\validate
 */
class ValidateChain
{
    public static array $allParsers = [
        Enum::class,IsEmail::class,IsIdCard::class,IsUrl::class,Regexp::class,Required::class,Tfloat::class,
        Tint::class,Tstring::class
    ];
    private array $parsers = [];

    /**
     * 错误结果
     * @var array
     */
    private array $errorResult = [];


    /**
     * 验证所有的格式 列表
     * @return $this
     */
    public function allValidate()
    {
        $this->parsers = ValidateChain::$allParsers;
        return $this;
    }

    /**
     * @param RequiredParser $parser
     * @return $this
     */
    public function addValidate(RequiredParser $parser)
    {
        $this->parsers[] = $parser;
        return $this;
    }

    public function addValidates(array $parsers)
    {
        $this->parsers = $this->parsers + $parsers;
        return $this;
    }

    /**
     * 监测所有的限制条件 如果 返回的数组第一个为false 则表示出现了错误
     * @param $val
     * @return array
     */
    public function validate($val): array
    {
        $pass = true;
        /**
         * @var $parser RequiredParser
         */
        foreach ($this->parsers as $parser) {
            list($bool, $retVal) = $parser->check($val);
            if (!$bool) {
                $pass = false;
                $this->errorResult[] = $retVal;
            }
        }
        return [$pass, $val];
    }

    /**
     * 获取错误信息
     * @return array
     */
    public function getError(): array
    {
        return $this->errorResult;
    }
}