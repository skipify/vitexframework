<?php declare(strict_types=1);
/**
 * 最小 Demo：.env 文件加载 + 普通 PHP 配置数组 + ENC1 加密配置值 端到端演示
 *
 * 运行：php demo/env_demo.php   （无第三方依赖）
 */
spl_autoload_register(function ($class) {
    $root = dirname(__DIR__);
    $classmap = [
        'vitex\\service\\CacheStore' => $root . '/vitex/service/cache/CacheStore.php',
    ];
    if (isset($classmap[$class])) {
        require $classmap[$class];
        return;
    }
    if (strpos($class, 'vitex\\') === 0) {
        require $root . '/' . str_replace('\\', '/', $class) . '.php';
    }
});

use vitex\service\ConfigProvider;
use vitex\service\crypto\ConfigCipher;
use vitex\service\EnvLoader;

$failures = 0;
function check(bool $cond, string $msg): void
{
    global $failures;
    echo ($cond ? '  [PASS] ' : '  [FAIL] ') . $msg . "\n";
    if (!$cond) {
        $failures++;
    }
}

// 1. 准备一个其他目录的 .env：配置加密主密钥 + 普通环境变量
$masterKey = 'MDEyMzQ1Njc4OWFiY2RlZg=='; // Base64("0123456789abcdef")
$dir = sys_get_temp_dir() . '/vitex-env-demo-' . getmypid();
mkdir($dir, 0700, true);
file_put_contents($dir . '/shared.env', ConfigCipher::ENV_KEY . '=' . $masterKey . "\nAPP_NAME=env-demo\n");

// 2. 生成一个 ENC1 加密配置值
$enc = ConfigCipher::encrypt($masterKey, 'db-pass-secret');

// 3. env_file 与普通配置数组同时加载，与原有 setConfig 用法完全兼容
$cp = new ConfigProvider();
$cp->setConfig([
    'env_file' => $dir . '/shared.env',                               // .env 从其他目录加载
    'debug'    => true,                                               // 普通配置项，用法不变
    'database' => ['master' => ['password' => $enc]],                 // ENC1 加密配置值
]);

check(getenv('APP_NAME') === 'env-demo', '.env 已注入环境变量');
check(ConfigCipher::findMasterKey(null) === $masterKey, '主密钥已从 .env 生效');
check($cp->getConfig('database')['master']['password'] === 'db-pass-secret', 'ENC1 配置值已自动解密');
check($cp->getConfig('debug') === true, '普通配置数组用法保持不变');
check(EnvLoader::load($dir . '/shared.env') === [], '重复加载幂等（真实环境变量优先）');

@unlink($dir . '/shared.env');
@rmdir($dir);
echo $failures === 0 ? "Demo OK\n" : "Demo FAILED\n";
exit($failures === 0 ? 0 : 1);
