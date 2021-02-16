<?php declare(strict_types=1);
/**
 * Vitex 一个基于php7.0开发的 快速开发restful API的微型框架
 * @version  0.2.0
 *
 * @package vitex
 *
 * @author  skipify <skipify@qq.com>
 * @copyright skipify
 * @license MIT
 */
namespace vitex\core;

/**
 * 美化异常的更新
 */
class Exception extends \Exception
{
    /**
     * 预制的错误代码
     */
    const CODE_UNCONNECT_DATABASE = 301;
    const CODE_UNCONNECT_DATABASE_MSG = '无法链接数据库';

    const CODE_DATABASE_ERROR  = 302;
    const CODE_DATABASE_ERROR_MSG  = '数据库执行错误';


    const CODE_NOTFOUND_FILE = 401;
    const CODE_NOTFOUND_FILE_MSG ='无法找到文件';

    CONST CODE_NOTFOUND_CLASS = 402;
    CONST CODE_NOTFOUND_CLASS_MSG = '无法找到执行的方法';

    CONST CODE_NOTFOUND_METHOD = 403;
    CONST CODE_NOTFOUND_METHOD_MSG = '无法找到执行的方法';

    const CODE_PARAM_ERROR = 501;
    const CODE_PARAM_ERROR_MSG = '参数错误';

    const CODE_PARAM_VALUE_ERROR = 502;
    const CODE_PARAM_VALUE_ERROR_MSG = '参数值错误';

    const CODE_PARAM_NUM_ERROR = 503;
    const CODE_PARAM_NUM_ERROR_MSG = '参数数量错误';

    const CODE_PARAM_PARSE_ERROR = 504;
    const CODE_PARAM_PARSE_ERROR_MSG = '参数解析错误，无法识别';

    const CODE_PARAM_INVALID_FORMAT = 505;
    const CODE_PARAM_INVALID_FORMAT_MSG = '参数格式错误';


}
