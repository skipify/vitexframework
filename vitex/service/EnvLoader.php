<?php declare(strict_types=1);
/**
 * Vitex 一个基于php8.0开发的 快速开发restful API的微型框架
 * @version  2.0.0
 *
 * @package vitex
 *
 * @author  skipify <skipify@qq.com>
 * @copyright skipify
 * @license MIT
 */

namespace vitex\service;

/**
 * .env 文件加载器：把 env 文件注入真实环境变量（putenv + $_ENV/$_SERVER），
 * 供 getenv()、$vitex->env 及框架配置（如 ENC1: 主密钥 VITEX_CONFIG_KEY）使用。
 *
 * 规则：
 * - 文件不存在或不可读时静默跳过
 * - 已存在的真实环境变量优先，文件值不会覆盖它们（部署系统注入的变量不受影响）
 * - 忽略空行与 #/; 注释行，支持可选的 export 前缀与成对的单双引号
 * - 行内 # 视为值的一部分（密码等含 # 的值建议用引号包裹）
 * - 不支持多行值与变量插值
 */
class EnvLoader
{
    /**
     * 加载 env 文件到环境变量
     * @param string|array $files env文件路径，字符串或路径数组（按顺序加载）
     * @return array 实际注入的 key=>value（不含已被真实环境变量占用的键）
     */
    public static function load($files): array
    {
        $loaded = [];
        foreach ((array)$files as $file) {
            if (!is_string($file) || $file === '' || !is_file($file) || !is_readable($file)) {
                continue;
            }
            foreach (self::parse($file) as $key => $val) {
                if ($key === '' || getenv($key) !== false) {
                    continue;
                }
                if (!putenv($key . '=' . $val)) {
                    continue;
                }
                $_ENV[$key] = $val;
                $_SERVER[$key] = $val;
                $loaded[$key] = $val;
            }
        }
        return $loaded;
    }

    /**
     * 解析 env 文件为 key=>value 数组（不写入环境变量）
     * @param string $file env文件路径
     * @return array
     */
    public static function parse(string $file): array
    {
        $env = [];
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return $env;
        }
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || $line[0] === ';') {
                continue;
            }
            if (strpos($line, 'export ') === 0) {
                $line = trim(substr($line, 7));
            }
            $pos = strpos($line, '=');
            if ($pos === false) {
                continue;
            }
            $key = trim(substr($line, 0, $pos));
            $val = self::unquote(trim(substr($line, $pos + 1)));
            if ($key !== '') {
                $env[$key] = $val;
            }
        }
        return $env;
    }

    /**
     * 去掉值两侧成对的单引号或双引号（双引号内的 \n 转义为换行）
     */
    private static function unquote(string $val): string
    {
        $len = strlen($val);
        if ($len < 2) {
            return $val;
        }
        if ($val[0] === '"' && $val[$len - 1] === '"') {
            return str_replace('\\n', "\n", substr($val, 1, -1));
        }
        if ($val[0] === "'" && $val[$len - 1] === "'") {
            return substr($val, 1, -1);
        }
        return $val;
    }
}
