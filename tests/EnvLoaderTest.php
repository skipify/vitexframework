<?php declare(strict_types=1);
/**
 * .env 加载器（EnvLoader）与配置集成独立测试。
 * 运行：php tests/EnvLoaderTest.php   （无第三方依赖）
 */

require __DIR__ . '/../vitex/service/EnvLoader.php';

$failures = 0;
$cases = 0;

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

$dir = sys_get_temp_dir() . '/vitex-envloader-test-' . getmypid();
mkdir($dir, 0700, true);
register_shutdown_function(function () use ($dir) {
    @unlink($dir . '/a.env');
    @unlink($dir . '/b.env');
    @unlink($dir . '/missing.env');
    @rmdir($dir);
});

// ---- 准备 env 文件 ----
file_put_contents($dir . '/a.env', <<<'ENV'
# 注释行
; 分号注释

PLAIN=hello
QUOTED="hello world"
SINGLE='it''s ok'
WITH_HASH=pass#word
BASE64=MDEyMzQ1Njc4OWFiY2RlZg==
export EXPORTED=via-export
ENV);
file_put_contents($dir . '/b.env', "B_ONLY=second\n");

use vitex\service\EnvLoader;

echo "PHP " . PHP_VERSION . "，EnvLoader 测试:\n";

// ---- 基本解析与加载 ----
$loaded = EnvLoader::load($dir . '/a.env');
check(getenv('PLAIN') === 'hello', "基本 KEY=VALUE 加载");
check(getenv('QUOTED') === 'hello world', "双引号剥离");
check(getenv('SINGLE') === "it''s ok", "单引号成对剥离(内部引号保留)");
check(getenv('WITH_HASH') === 'pass#word', "行内 # 保留为值的一部分");
check(getenv('BASE64') === 'MDEyMzQ1Njc4OWFiY2RlZg==', "值含等号(Base64 padding)");
check(getenv('EXPORTED') === 'via-export', "可选 export 前缀");
check(isset($loaded['PLAIN']) && !isset($loaded['UNSET_KEY']), "返回实际注入的键值");

// ---- 真实环境变量优先 ----
putenv('FROM_FILE=real-env');
EnvLoader::load($dir . '/b.env');
check(getenv('B_ONLY') === 'second', "多文件按顺序加载");
file_put_contents($dir . '/b.env', "B_ONLY=changed\nFROM_FILE=from-file\n");
EnvLoader::load($dir . '/b.env');
check(getenv('FROM_FILE') === 'real-env', "已有真实环境变量不被覆盖");

// ---- 文件不存在静默跳过 ----
$before = EnvLoader::load($dir . '/missing.env');
check($before === [], "文件不存在静默跳过");

// ---- parse 只解析不注入 ----
$parsed = EnvLoader::parse($dir . '/b.env');
check($parsed === ['B_ONLY' => 'changed', 'FROM_FILE' => 'from-file'], "parse 返回键值数组");

// ---- 与配置集成：ConfigProvider 经 env_file 加载主密钥并解密 ENC1 值 ----
spl_autoload_register(function ($class) {
    // 非PSR-4位置的类
    $classmap = [
        'vitex\\service\\CacheStore' => __DIR__ . '/../vitex/service/cache/CacheStore.php',
    ];
    if (isset($classmap[$class])) {
        require $classmap[$class];
        return;
    }
    if (strpos($class, 'vitex\\') === 0) {
        require __DIR__ . '/../' . str_replace('\\', '/', $class) . '.php';
    }
});

putenv(\vitex\service\crypto\ConfigCipher::ENV_KEY); // 清掉本轮测试外的同名变量干扰
$enc = \vitex\service\crypto\ConfigCipher::encrypt('MDEyMzQ1Njc4OWFiY2RlZg==', 'db-pass');
file_put_contents($dir . '/b.env', \vitex\service\crypto\ConfigCipher::ENV_KEY . "=MDEyMzQ1Njc4OWFiY2RlZg==\n");

$cp = new \vitex\service\ConfigProvider();
$cp->setConfig([
    'env_file' => $dir . '/b.env',
    'database' => ['password' => $enc],
]);
check(\vitex\service\crypto\ConfigCipher::findMasterKey(null) === 'MDEyMzQ1Njc4OWFiY2RlZg==', "env_file 注入的主密钥可被框架找到");
check($cp->getConfig('database')['password'] === 'db-pass', "setConfig 时 .env 主密钥解密 ENC1 配置值");

// 兼容性：env_file 留空时行为不变
$cp2 = new \vitex\service\ConfigProvider();
$cp2->setConfig(['debug' => true]);
check($cp2->getConfig('debug') === true && $cp2->getConfig('env_file') === '', "env_file 留空不加载，原有配置用法不变");

echo "\n{$cases} 个用例，{$failures} 个失败\n";
exit($failures === 0 ? 0 : 1);
