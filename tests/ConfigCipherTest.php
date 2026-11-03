<?php declare(strict_types=1);
/**
 * 配置值加密（ENC1/SCF1 + SM4-GCM）独立测试。
 * 运行：php tests/ConfigCipherTest.php   （PHP >= 7.3，无第三方依赖）
 *
 * 含 SCF1 格式固定回归向量：主密钥 MDEyMzQ1Njc4OWFiY2RlZg==（Base64("0123456789abcdef")）
 * 加密 'interop-plaintext' 的标准结果，用于保证格式不被无意改动。
 * 本机 openssl sm4-gcm 不可用时自动跳过 openssl 用例，纯 PHP 路径始终全量执行。
 */

require __DIR__ . '/../vitex/service/crypto/Sm4Gcm.php';
require __DIR__ . '/../vitex/service/crypto/ConfigCipher.php';

use vitex\service\crypto\ConfigCipher;
use vitex\service\crypto\Sm4Gcm;

$failures = 0;
$cases = 0;
$skipped = 0;

function check(bool $cond, string $name): void
{
    global $failures, $cases;
    $cases++;
    if (!$cond) {
        $failures++;
        echo "  [FAIL] {$name}\n";
    } else {
        echo "  [PASS] {$name}\n";
    }
}

function checkThrows(callable $fn, string $name): void
{
    global $failures, $cases;
    $cases++;
    try {
        $fn();
        $failures++;
        echo "  [FAIL] {$name}（未抛出异常）\n";
    } catch (\Throwable $e) {
        echo "  [PASS] {$name}（" . get_class($e) . "）\n";
    }
}

$masterKey = 'MDEyMzQ1Njc4OWFiY2RlZg=='; // Base64("0123456789abcdef")
$interopVector = 'ENC1:U0NGMRf1cJMg27mrF66ydKZKB0hXWrB483S7fvchODvPvCOIVad6r+N5t1K8SXGp5g==';
$native = Sm4Gcm::useNative();

echo "PHP " . PHP_VERSION . "，openssl sm4-gcm 实测: " . ($native ? '可用' : '不可用（走纯 PHP）') . "\n";

$encNative = null;

// ---- openssl 驱动（仅本机实测可用时执行） ----
if ($native) {
    Sm4Gcm::$forcePurePhp = false;
    $encNative = ConfigCipher::encrypt($masterKey, 'cross-driver');
    check(strpos($encNative, 'ENC1:') === 0, "[openssl] 密文带 ENC1: 前缀");
    check(ConfigCipher::decrypt($masterKey, $encNative) === 'cross-driver', "[openssl] 往返解密");
    check(ConfigCipher::decrypt($masterKey, $interopVector) === 'interop-plaintext', "[openssl] 固定向量解密");
} else {
    $skipped++;
    echo "  [SKIP] openssl 驱动用例（当前 PHP/OpenSSL 组合不支持 sm4-gcm：需 PHP >= 8.5 + OpenSSL >= 3.5，主流部署暂不存在）\n";
}

// ---- 纯 PHP 驱动（始终全量执行） ----
Sm4Gcm::$forcePurePhp = true;
$encPure = ConfigCipher::encrypt($masterKey, 'cross-driver');
check(strpos($encPure, 'ENC1:') === 0, "[纯PHP] 密文带 ENC1: 前缀");
check(ConfigCipher::decrypt($masterKey, $encPure) === 'cross-driver', "[纯PHP] 往返解密");
check(ConfigCipher::decrypt($masterKey, $interopVector) === 'interop-plaintext', "[纯PHP] 固定向量解密");
check(ConfigCipher::encrypt($masterKey, 'same') !== ConfigCipher::encrypt($masterKey, 'same'), "[纯PHP] 随机 nonce 密文不同");
Sm4Gcm::$forcePurePhp = null;

// ---- 跨驱动互操作（仅 openssl 可用时） ----
if ($native && $encNative !== null) {
    Sm4Gcm::$forcePurePhp = true;
    check(ConfigCipher::decrypt($masterKey, $encNative) === 'cross-driver', "[纯PHP] 解 openssl 密文（跨驱动互操作）");
    Sm4Gcm::$forcePurePhp = false;
    check(ConfigCipher::decrypt($masterKey, $encPure) === 'cross-driver', "[openssl] 解纯PHP密文（跨驱动互操作）");
    Sm4Gcm::$forcePurePhp = null;
}

// ---- 异常路径（纯 PHP 驱动，行为与 openssl 一致） ----
Sm4Gcm::$forcePurePhp = true;

checkThrows(function () {
    ConfigCipher::decrypt('MDEyMzQ1Njc4OWFiY2RlZ', 'x'); // 主密钥长度非法
}, '非法主密钥拒绝');

checkThrows(function () use ($masterKey) {
    ConfigCipher::decrypt($masterKey, 'plain-value');
}, '非 ENC1: 值拒绝');

checkThrows(function () use ($masterKey) {
    ConfigCipher::decrypt($masterKey, 'ENC1:!!!not-base64!!!');
}, '非法 Base64 拒绝');

checkThrows(function () use ($masterKey) {
    ConfigCipher::decrypt($masterKey, ConfigCipher::encrypt('cGFzc3dvcmQxMjM0NQ==', 'x'));
}, '错误主密钥拒绝');

checkThrows(function () use ($masterKey) {
    $enc = ConfigCipher::encrypt($masterKey, 'secret');
    $raw = base64_decode(substr($enc, strlen(ConfigCipher::PREFIX)), true);
    $raw[strlen($raw) - 1] = $raw[strlen($raw) - 1] ^ "\x01";
    ConfigCipher::decrypt($masterKey, ConfigCipher::PREFIX . base64_encode($raw));
}, '篡改密文拒绝');

// ---- resolve：混合数组 + fail-closed ----
$raw = [
    'host' => '127.0.0.1',
    'db' => ['password' => ConfigCipher::encrypt($masterKey, 'db-pass'), 'port' => 3306],
];
$resolved = ConfigCipher::resolve($raw, $masterKey);
check($resolved['host'] === '127.0.0.1' && $resolved['db']['port'] === 3306, "[resolve] 明文项原样保留");
check($resolved['db']['password'] === 'db-pass', "[resolve] 嵌套加密项解密");
checkThrows(function () {
    ConfigCipher::resolve(['p' => 'ENC1:abcd'], '');
}, '[resolve] 缺主密钥 fail-closed');

check(ConfigCipher::isEncrypted('ENC1:x') && !ConfigCipher::isEncrypted('plain') && !ConfigCipher::isEncrypted('ENC1X'), 'isEncrypted 前缀判断');

Sm4Gcm::$forcePurePhp = null;

echo "\n{$cases} 个用例（跳过 {$skipped} 组），{$failures} 个失败\n";
exit($failures === 0 ? 0 : 1);
