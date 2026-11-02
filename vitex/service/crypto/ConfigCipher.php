<?php declare(strict_types=1);
/**
 * Vitex 配置值静态加密（SCF1 格式）：对配置数组 / .env / 环境变量中的敏感值做透明加解密，
 * 语义对齐 Jasypt 的 ENC(...)，算法为 SM4-GCM（国密），底层走 vitex\service\crypto\Sm4Gcm。
 *
 * 值格式：ENC1:<Base64( magic("SCF1") || nonce(12B) || SM4-GCM(cipher‖tag) )>
 * 与 signhub 全家桶（Java/PHP/Go SDK 与服务端）格式逐字节互通。
 *
 * 主密钥为 16 字节 SM4 密钥的 Base64 文本（openssl rand -base64 16 生成），
 * 经环境变量 SIGNHUB_CONFIG_KEY 注入（框架内也可经配置项 config.enc1_key 设置），
 * 不写配置文件、不入仓库。主密钥为机器生成的高熵密钥，无需 KDF 派生；
 * 主密钥丢失 = 密文永久无法解密，务必托管备份。
 *
 * 典型用法：在 Vitex 配置数组（或 .env / 入口设置）里把敏感值写成 ENC1: 密文，
 * 框架启动时由 ConfigProvider 自动解密（config.enc1_key / SIGNHUB_CONFIG_KEY）；
 * 非 Vitex 环境可手动调用 resolve() 解析整个配置数组。
 */

namespace vitex\service\crypto;

class ConfigCipher
{
    /** 加密值前缀（含冒号），v1。 */
    const PREFIX = 'ENC1:';

    /** 主密钥默认环境变量名。 */
    const ENV_KEY = 'SIGNHUB_CONFIG_KEY';

    /** 二进制魔数（ASCII "SCF1"，signhub/vitex config value v1）。 */
    const MAGIC = 'SCF1';

    /**
     * 加密为 ENC1: 值。同一明文每次加密结果不同（随机 nonce），属正常现象。
     *
     * @throws \InvalidArgumentException 主密钥非法
     */
    public static function encrypt(string $base64Key, string $plaintext): string
    {
        $key = self::parseKey($base64Key);
        $nonce = random_bytes(Sm4Gcm::NONCE_LEN);
        return self::PREFIX . base64_encode(
            self::MAGIC . $nonce . Sm4Gcm::encrypt($key, $nonce, $plaintext)
        );
    }

    /**
     * 解密 ENC1: 值，认证失败/格式非法/主密钥缺失抛异常（fail-closed，不放行密文）。
     *
     * @throws \InvalidArgumentException 前缀不符 / Base64 非法 / 魔数不符 / 主密钥缺失
     * @throws \RuntimeException 主密钥不匹配或密文被篡改
     */
    public static function decrypt(string $base64Key, string $value): string
    {
        $key = self::parseKey($base64Key);
        if (!self::isEncrypted($value)) {
            throw new \InvalidArgumentException('不是 ' . self::PREFIX . ' 加密值');
        }
        $raw = base64_decode(substr($value, strlen(self::PREFIX)), true);
        $minLen = strlen(self::MAGIC) + Sm4Gcm::NONCE_LEN + Sm4Gcm::TAG_LEN;
        if ($raw === false || strlen($raw) < $minLen) {
            throw new \InvalidArgumentException('配置值密文非法或长度不足');
        }
        if (substr($raw, 0, strlen(self::MAGIC)) !== self::MAGIC) {
            throw new \InvalidArgumentException('配置值魔数不符（SCF1）');
        }
        $nonce = substr($raw, strlen(self::MAGIC), Sm4Gcm::NONCE_LEN);
        $cipher = substr($raw, strlen(self::MAGIC) + Sm4Gcm::NONCE_LEN);
        try {
            return Sm4Gcm::decrypt($key, $nonce, $cipher);
        } catch (\RuntimeException $e) {
            throw new \RuntimeException(
                '配置值解密失败：主密钥不匹配或密文已损坏',
                0,
                $e
            );
        }
    }

    /** 是否为加密值（仅前缀判断，不校验内容合法性）。 */
    public static function isEncrypted(string $value): bool
    {
        return strpos($value, self::PREFIX) === 0;
    }

    /**
     * 递归解析配置数组（含嵌套数组）：ENC1: 值就地解密，其余原样保留（明文/密文可混用）。
     *
     * @throws \RuntimeException 数组中存在加密值而主密钥缺失
     * @throws \InvalidArgumentException|\RuntimeException 任一值解密失败
     */
    public static function resolve(array $config, string $base64Key): array
    {
        array_walk_recursive($config, function (&$v) use ($base64Key): void {
            if (is_string($v) && self::isEncrypted($v)) {
                $v = self::decrypt($base64Key, $v);
            }
        });
        return $config;
    }

    /**
     * 主密钥解析：显式传入值优先，其次环境变量 SIGNHUB_CONFIG_KEY。
     *
     * @return string|null 主密钥原文；缺失返回 null（是否报错由调用方决定）
     */
    public static function findMasterKey(?string $argOverride): ?string
    {
        if ($argOverride !== null && trim($argOverride) !== '') {
            return $argOverride;
        }
        $env = getenv(self::ENV_KEY);
        return $env === false || trim($env) === '' ? null : $env;
    }

    /**
     * 主密钥解析：Base64 文本 -> 16 字节。
     *
     * @throws \InvalidArgumentException 密钥缺失或非法
     */
    public static function parseKey(string $base64Key): string
    {
        if (trim($base64Key) === '') {
            throw new \InvalidArgumentException(
                '缺少配置值主密钥：请设置环境变量 ' . self::ENV_KEY . '（openssl rand -base64 16 生成）'
            );
        }
        $key = base64_decode(trim($base64Key), true);
        if ($key === false || strlen($key) !== Sm4Gcm::KEY_LEN) {
            throw new \InvalidArgumentException('主密钥必须是 16 字节 SM4 密钥的 Base64 编码');
        }
        return $key;
    }
}
