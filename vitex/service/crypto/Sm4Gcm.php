<?php declare(strict_types=1);
/**
 * Vitex SM4-GCM 国密加密（GB/T 32907-2016 + NIST SP 800-38D）双驱动实现。
 *
 * 驱动优先级：openssl sm4-gcm（C 速度）> 纯 PHP 实现（零第三方依赖）。
 * 注意当前部署现实：SM4-GCM 是 OpenSSL 3.5 LTS（2025-04）才引入的算法，且需
 * PHP >= 8.5（openssl 封装改为 EVP_CIPHER_fetch 动态解析 + 通用 AEAD 分支）才能
 * 经 PHP openssl 调用；主流环境（PHP <= 8.4 + OpenSSL 3.0/3.2，Ubuntu 22.04/24.04、
 * Debian 12、RHEL 9/10 等）实测一律不可用——openssl_get_cipher_methods() 会列出
 * sm4-gcm/sm4-ccm 但调用失败（provider-only，不在 legacy 名字表）。
 * 因此【纯 PHP 回退是当前的实际主路径】，openssl 路径是为未来技术栈预留的加速器；
 * 探测以真实加解密自测为准（useNative()/selfTestNative()），不信任方法名列表。
 *
 * 纯 PHP 实现移植自 signhub-sdk-php（Apache-2.0，Copyright signhub）。
 *
 * 密文格式与 signhub 全家桶（Java/PHP/Go SDK 与服务端）逐字节互通：
 *  - 密钥 16 字节；nonce 12 字节；输出 = cipher‖tag（tag 16 字节追加尾部）。
 *  契约红线：不得以 sm4-cbc/ctr 等替代（无认证 tag 且破坏三语言逐字节对齐）。
 *
 * 性能定位：本类当前仅用于 ENC1 配置值解密（启动期少量短值），纯 PHP 路径
 * 毫秒级完成，性能无虞；若未来用于高频大流量场景需重新评估。
 *
 * 本类不做任何轮密钥静态缓存：轮密钥现场扩展、用完即释放，不驻留密钥材料。
 */

namespace vitex\service\crypto;

class Sm4Gcm
{
    const KEY_LEN = 16;
    const NONCE_LEN = 12;
    const TAG_LEN = 16;
    const BLOCK_LEN = 16;

    /**
     * 强制走纯 PHP 实现（仅测试用）：null=自动探测，true=纯 PHP，false=openssl。
     * @var bool|null
     */
    public static $forcePurePhp = null;

    /** openssl sm4-gcm 可用性缓存。
     * @var bool|null
     */
    private static $nativeAvailable = null;

    // S 盒（GB/T 32907-2016 附录 A）
    private const SBOX = [
        0xd6, 0x90, 0xe9, 0xfe, 0xcc, 0xe1, 0x3d, 0xb7, 0x16, 0xb6, 0x14, 0xc2, 0x28, 0xfb, 0x2c, 0x05,
        0x2b, 0x67, 0x9a, 0x76, 0x2a, 0xbe, 0x04, 0xc3, 0xaa, 0x44, 0x13, 0x26, 0x49, 0x86, 0x06, 0x99,
        0x9c, 0x42, 0x50, 0xf4, 0x91, 0xef, 0x98, 0x7a, 0x33, 0x54, 0x0b, 0x43, 0xed, 0xcf, 0xac, 0x62,
        0xe4, 0xb3, 0x1c, 0xa9, 0xc9, 0x08, 0xe8, 0x95, 0x80, 0xdf, 0x94, 0xfa, 0x75, 0x8f, 0x3f, 0xa6,
        0x47, 0x07, 0xa7, 0xfc, 0xf3, 0x73, 0x17, 0xba, 0x83, 0x59, 0x3c, 0x19, 0xe6, 0x85, 0x4f, 0xa8,
        0x68, 0x6b, 0x81, 0xb2, 0x71, 0x64, 0xda, 0x8b, 0xf8, 0xeb, 0x0f, 0x4b, 0x70, 0x56, 0x9d, 0x35,
        0x1e, 0x24, 0x0e, 0x5e, 0x63, 0x58, 0xd1, 0xa2, 0x25, 0x22, 0x7c, 0x3b, 0x01, 0x21, 0x78, 0x87,
        0xd4, 0x00, 0x46, 0x57, 0x9f, 0xd3, 0x27, 0x52, 0x4c, 0x36, 0x02, 0xe7, 0xa0, 0xc4, 0xc8, 0x9e,
        0xea, 0xbf, 0x8a, 0xd2, 0x40, 0xc7, 0x38, 0xb5, 0xa3, 0xf7, 0xf2, 0xce, 0xf9, 0x61, 0x15, 0xa1,
        0xe0, 0xae, 0x5d, 0xa4, 0x9b, 0x34, 0x1a, 0x55, 0xad, 0x93, 0x32, 0x30, 0xf5, 0x8c, 0xb1, 0xe3,
        0x1d, 0xf6, 0xe2, 0x2e, 0x82, 0x66, 0xca, 0x60, 0xc0, 0x29, 0x23, 0xab, 0x0d, 0x53, 0x4e, 0x6f,
        0xd5, 0xdb, 0x37, 0x45, 0xde, 0xfd, 0x8e, 0x2f, 0x03, 0xff, 0x6a, 0x72, 0x6d, 0x6c, 0x5b, 0x51,
        0x8d, 0x1b, 0xaf, 0x92, 0xbb, 0xdd, 0xbc, 0x7f, 0x11, 0xd9, 0x5c, 0x41, 0x1f, 0x10, 0x5a, 0xd8,
        0x0a, 0xc1, 0x31, 0x88, 0xa5, 0xcd, 0x7b, 0xbd, 0x2d, 0x74, 0xd0, 0x12, 0xb8, 0xe5, 0xb4, 0xb0,
        0x89, 0x69, 0x97, 0x4a, 0x0c, 0x96, 0x77, 0x7e, 0x65, 0xb9, 0xf1, 0x09, 0xc5, 0x6e, 0xc6, 0x84,
        0x18, 0xf0, 0x7d, 0xec, 0x3a, 0xdc, 0x4d, 0x20, 0x79, 0xee, 0x5f, 0x3e, 0xd7, 0xcb, 0x39, 0x48,
    ];

    private const FK = [0xa3b1bac6, 0x56aa3350, 0x677d9197, 0xb27022dc];

    /**
     * SM4-GCM 加密，返回 cipher‖tag。
     */
    public static function encrypt(string $key, string $nonce, string $plaintext): string
    {
        self::checkArgs($key, $nonce);
        if (self::useNative()) {
            $tag = '';
            $cipher = openssl_encrypt(
                $plaintext,
                'sm4-gcm',
                $key,
                OPENSSL_RAW_DATA,
                $nonce,
                $tag,
                '',
                self::TAG_LEN
            );
            if ($cipher === false) {
                throw new \RuntimeException('SM4-GCM openssl encrypt failed');
            }
            return $cipher . $tag; // PHP 的 GCM tag 单独返回，拼回尾部对齐 cipher‖tag 约定
        }
        return self::pureEncrypt($key, $nonce, $plaintext);
    }

    /**
     * SM4-GCM 解密 cipher‖tag，认证失败抛 RuntimeException。
     */
    public static function decrypt(string $key, string $nonce, string $cipherWithTag): string
    {
        self::checkArgs($key, $nonce);
        if (strlen($cipherWithTag) < self::TAG_LEN) {
            throw new \RuntimeException('SM4-GCM ciphertext too short');
        }
        if (self::useNative()) {
            $tag = substr($cipherWithTag, -self::TAG_LEN);
            $plain = openssl_decrypt(
                substr($cipherWithTag, 0, -self::TAG_LEN),
                'sm4-gcm',
                $key,
                OPENSSL_RAW_DATA,
                $nonce,
                $tag,
                ''
            );
            if ($plain === false) {
                throw new \RuntimeException('SM4-GCM auth failed (tag mismatch)');
            }
            return $plain;
        }
        return self::pureDecrypt($key, $nonce, $cipherWithTag);
    }

    /**
     * 驱动选择：测试开关优先，否则探测 openssl sm4-gcm（结果缓存）。
     *
     * 注意：openssl_get_cipher_methods() 可能列出 sm4-gcm（OpenSSL 3 自带），
     * 但 PHP 的 openssl 封装对 AEAD 走硬编码白名单，实际不可用的情况存在——
     * 因此探测以真实加解密自测为准，任何一步失败即回退纯 PHP。
     */
    public static function useNative(): bool
    {
        if (self::$forcePurePhp !== null) {
            return !self::$forcePurePhp;
        }
        if (self::$nativeAvailable === null) {
            self::$nativeAvailable = in_array('sm4-gcm', openssl_get_cipher_methods(true), true)
                && self::selfTestNative();
        }
        return self::$nativeAvailable;
    }

    /** openssl sm4-gcm 真实自测：空风险样本走一遍加解密往返。 */
    private static function selfTestNative(): bool
    {
        $key = str_repeat('v', self::KEY_LEN);   // 公开探测样本，非密钥材料
        $nonce = str_repeat('n', self::NONCE_LEN);
        $tag = '';
        $cipher = @openssl_encrypt(
            'vitex-sm4gcm-selftest',
            'sm4-gcm',
            $key,
            OPENSSL_RAW_DATA,
            $nonce,
            $tag,
            '',
            self::TAG_LEN
        );
        if ($cipher === false) {
            return false;
        }
        $plain = @openssl_decrypt($cipher, 'sm4-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag, '');
        return $plain === 'vitex-sm4gcm-selftest';
    }

    private static function checkArgs(string $key, string $nonce): void
    {
        if (strlen($key) !== self::KEY_LEN) {
            throw new \InvalidArgumentException('SM4-GCM key must be 16 bytes');
        }
        if (strlen($nonce) !== self::NONCE_LEN) {
            throw new \InvalidArgumentException('SM4-GCM nonce must be 12 bytes');
        }
    }

    // ---------------------------------------------------------------- 纯 PHP 回退实现（移植自 signhub-sdk-php，Apache-2.0）

    private static function pureEncrypt(string $key, string $nonce, string $plaintext): string
    {
        $rk = self::roundKeys($key); // 现场扩展，函数结束即释放，不驻留密钥材料
        try {
            // GHASH 子密钥 H = E(K, 0^128)（SP 800-38D 6.3）
            $h = self::encryptBlockRk($rk, str_repeat("\x00", 16));
            // 预计数器块 J0：96bit nonce ‖ 0^31 ‖ 1（SP 800-38D 8.1.1）
            $j0 = $nonce . "\x00\x00\x00\x01";
            $cipher = self::gctr($rk, self::inc32($j0), $plaintext);   // C = GCTR(K, inc32(J0), P)
            $s = self::ghash($h, '', $cipher);                         // S = GHASH_H(A, C)
            $tag = substr(self::gctr($rk, $j0, $s), 0, self::TAG_LEN); // T = MSB_t(GCTR(K, J0, S))
            return $cipher . $tag;
        } finally {
            unset($rk);
        }
    }

    private static function pureDecrypt(string $key, string $nonce, string $cipherWithTag): string
    {
        $cipher = substr($cipherWithTag, 0, -self::TAG_LEN);
        $tag = substr($cipherWithTag, -self::TAG_LEN);
        $rk = self::roundKeys($key);
        try {
            $h = self::encryptBlockRk($rk, str_repeat("\x00", 16));
            $j0 = $nonce . "\x00\x00\x00\x01";
            $s = self::ghash($h, '', $cipher);
            $expect = substr(self::gctr($rk, $j0, $s), 0, self::TAG_LEN);
            if (!hash_equals($expect, $tag)) { // 常时比较：先验 tag 再出明文
                throw new \RuntimeException('SM4-GCM auth failed (tag mismatch)');
            }
            return self::gctr($rk, self::inc32($j0), $cipher);
        } finally {
            unset($rk);
        }
    }

    /** 用已扩展轮密钥加密单个块（GCM 内部复用，避免逐块重复扩展）。 */
    private static function encryptBlockRk(array $rk, string $block): string
    {
        $x = array_values(unpack('N4', $block));
        return self::packWords(self::rounds($x, $rk));
    }

    private static function rounds(array $x, array $rk): array
    {
        for ($i = 0; $i < 32; $i++) {
            $x[$i + 4] = $x[$i] ^ self::t($x[$i + 1] ^ $x[$i + 2] ^ $x[$i + 3] ^ $rk[$i]);
        }
        // 反序变换：输出 (X35, X34, X33, X32)
        return [$x[35], $x[34], $x[33], $x[32]];
    }

    /** 轮函数 T = τ(S 盒逐字节代换) ∘ L(线性扩散)。 */
    private static function t(int $a): int
    {
        $b = self::tau($a);
        return $b ^ self::rotl32($b, 2) ^ self::rotl32($b, 10) ^ self::rotl32($b, 18) ^ self::rotl32($b, 24);
    }

    /** 密钥扩展用 T'：τ ∘ L'（L' = B ^ rotl(B,13) ^ rotl(B,23)）。 */
    private static function tPrime(int $a): int
    {
        $b = self::tau($a);
        return $b ^ self::rotl32($b, 13) ^ self::rotl32($b, 23);
    }

    /** 32 位循环左移。 */
    private static function rotl32(int $x, int $n): int
    {
        $x &= 0xffffffff;
        $n %= 32;
        if ($n === 0) {
            return $x;
        }
        return (($x << $n) | ($x >> (32 - $n))) & 0xffffffff;
    }

    private static function tau(int $a): int
    {
        return (self::SBOX[($a >> 24) & 0xff] << 24)
            | (self::SBOX[($a >> 16) & 0xff] << 16)
            | (self::SBOX[($a >> 8) & 0xff] << 8)
            | self::SBOX[$a & 0xff];
    }

    /**
     * @return int[] 32 个轮密钥（现场扩展，不做静态缓存：密钥材料只在本次调用作用域内存在）
     */
    private static function roundKeys(string $key): array
    {
        if (strlen($key) !== self::KEY_LEN) {
            throw new \InvalidArgumentException('SM4 key must be 16 bytes');
        }
        $mk = array_values(unpack('N4', $key));
        $k = [];
        for ($i = 0; $i < 4; $i++) {
            $k[$i] = $mk[$i] ^ self::FK[$i];
        }
        // CK[i][j] = (4i + j) × 7 mod 256（GB/T 32907 生成规则）
        $rk = [];
        for ($i = 0; $i < 32; $i++) {
            $ck = ((4 * $i + 0) * 7 % 256 << 24) | ((4 * $i + 1) * 7 % 256 << 16)
                | ((4 * $i + 2) * 7 % 256 << 8) | ((4 * $i + 3) * 7 % 256);
            $rk[$i] = $k[$i] ^ self::tPrime($k[$i + 1] ^ $k[$i + 2] ^ $k[$i + 3] ^ $ck);
            $k[$i + 4] = $rk[$i];
        }
        return $rk;
    }

    private static function packWords(array $w): string
    {
        return pack('N4', $w[0], $w[1], $w[2], $w[3]);
    }

    /** GCTR（NIST SP 800-38D 6.5）：CB 从 ICB 开始的计数器模式。 */
    private static function gctr(array $rk, string $icb, string $data): string
    {
        if ($data === '') {
            return '';
        }
        $cb = $icb;
        $out = '';
        $n = intdiv(strlen($data), 16);
        for ($i = 0; $i < $n; $i++) {
            $block = substr($data, $i * 16, 16);
            $out .= $block ^ self::encryptBlockRk($rk, $cb);
            $cb = self::inc32($cb);
        }
        $rest = strlen($data) % 16;
        if ($rest > 0) {
            $ks = self::encryptBlockRk($rk, $cb);
            $out .= substr($data, $n * 16) ^ substr($ks, 0, $rest);
        }
        return $out;
    }

    /** inc32：仅递增计数器最后 32 位（mod 2^32）。 */
    private static function inc32(string $block): string
    {
        $counter = unpack('N', substr($block, 12, 4))[1];
        $counter = ($counter + 1) & 0xffffffff;
        return substr($block, 0, 12) . pack('N', $counter);
    }

    /** GHASH（NIST SP 800-38D 6.4）：Y = (Y ⊕ X_i) • H，• 为 GF(2^128) 乘法。 */
    private static function ghash(string $h, string $aad, string $cipher): string
    {
        $y = str_repeat("\x00", 16);
        $y = self::ghashBlocks($y, $h, self::padToBlock($aad));
        $y = self::ghashBlocks($y, $h, self::padToBlock($cipher));
        // 末块：[len(A)]64 ‖ [len(C)]64（比特长度）
        $lenBlock = pack('J2', strlen($aad) * 8, strlen($cipher) * 8);
        return self::gfMult(self::xorBytes($y, $lenBlock), $h);
    }

    /** 0 填充至 16 字节整数倍（GCM 分块填充）。 */
    private static function padToBlock(string $data): string
    {
        $rest = strlen($data) % 16;
        return $rest === 0 ? $data : $data . str_repeat("\x00", 16 - $rest);
    }

    private static function ghashBlocks(string $y, string $h, string $data): string
    {
        $n = intdiv(strlen($data), 16);
        for ($i = 0; $i < $n; $i++) {
            $y = self::gfMult(self::xorBytes($y, substr($data, $i * 16, 16)), $h);
        }
        return $y;
    }

    /** GF(2^128) 乘法（多项式 x^128 + x^7 + x^2 + x + 1，R = 0xE1‖0^120）。 */
    private static function gfMult(string $x, string $y): string
    {
        $z = str_repeat("\x00", 16);
        $v = $y;
        for ($i = 0; $i < 128; $i++) {
            if ((ord($x[$i >> 3]) >> (7 - ($i & 7))) & 1) {
                $z = self::xorBytes($z, $v);
            }
            $lsb = ord($v[15]) & 1;
            $v = self::shiftRight1($v);
            if ($lsb) {
                $v[0] = chr(ord($v[0]) ^ 0xe1);
            }
        }
        return $z;
    }

    private static function shiftRight1(string $b): string
    {
        $out = str_repeat("\x00", 16);
        $carry = 0;
        for ($i = 0; $i < 16; $i++) {
            $byte = ord($b[$i]);
            $out[$i] = chr(($byte >> 1) | ($carry << 7));
            $carry = $byte & 1;
        }
        return $out;
    }

    private static function xorBytes(string $a, string $b): string
    {
        return $a ^ $b;
    }
}
