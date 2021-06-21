<?php


namespace vitex\helper\attribute\parser\validate;

/**
 * 验证类
 * @package vitex\helper\attribute\parser\validate
 */
class Validate
{

    /**
     * 是否为26字母
     * @param $val
     * @return bool
     */
    public static function isAlpha($val): bool
    {
        return preg_match('/^[a-zA-Z]+$/', $val) ? true : false;
    }

    /**
     * 验证是否是有效的邮箱
     * @param $val
     * @return bool
     */
    public static function isEmail($val): bool
    {
        return filter_var($val, FILTER_VALIDATE_EMAIL) ? true : false;
    }

    /**
     * 是否有效的URL
     * @param $val
     * @return bool
     */
    public static function isUrl($val): bool
    {
        return filter_var($val, FILTER_VALIDATE_URL) ? true : false;
    }

    /**
     * 是否有效的IP
     * @param $val
     * @return bool
     */
    public static function isIp($val): bool
    {
        return filter_var($val, FILTER_VALIDATE_IP) ? true : false;
    }

    /**
     * 是否有效的MAC地址
     * @param $val
     * @return bool
     */
    public static function isMac($val): bool
    {
        return filter_var($val, FILTER_VALIDATE_MAC) ? true : false;
    }

    /**
     * 是否是有效的域名
     * @param $val
     * @return bool
     */
    public static function isDomain($val): bool
    {
        return filter_var($val, FILTER_VALIDATE_DOMAIN) ? true : false;
    }

    /**
     * 是否是整形
     * @param $val
     * @return bool
     */
    public static function isInt($val): bool
    {
        return filter_var($val, FILTER_VALIDATE_INT) ? true : false;
    }

    /**
     * 是否是Float数字
     * @param $val
     * @return bool
     */
    public static function isFloat($val): bool
    {
        return filter_var($val, FILTER_VALIDATE_FLOAT) ? true : false;
    }

    /**
     * 身份证号验证
     * @param $idcard
     * @return bool
     */
    public static function isIdCard($idcard): bool
    {
        $idcard = (string)$idcard;
        // 只能是18位
        if (strlen($idcard) != 18) {
            return false;
        }

        $vCity = array(
            '11', '12', '13', '14', '15', '21', '22',
            '23', '31', '32', '33', '34', '35', '36',
            '37', '41', '42', '43', '44', '45', '46',
            '50', '51', '52', '53', '54', '61', '62',
            '63', '64', '65', '71', '81', '82', '91'
        );

        if (!preg_match('/^([\d]{17}[xX\d]|[\d]{15})$/', $idcard)) {
            return false;
        }

        if (!in_array(substr($idcard, 0, 2), $vCity)) {
            return false;
        }

        // 取出本体码
        $idcardBase = substr($idcard, 0, 17);

        // 取出校验码
        $verify_code = substr($idcard, 17, 1);
        // 加权因子
        $factor = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);

        // 校验码对应值
        $verifyCodeList = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');

        // 根据前17位计算校验码
        $total = 0;
        for ($i = 0; $i < 17; $i++) {
            $total += substr($idcardBase, $i, 1) * $factor[$i];
        }
        // 取模
        $mod = $total % 11;
        // 比较校验码
        if ($verify_code == $verifyCodeList[$mod]) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 验证手机号
     * @param $val
     * @return bool
     */
    public static function isMobile($val): bool
    {
        $val = (string)$val;
        $reg = '/^13[\d]{9}$|^14[5,6,7,8,9]{1}\d{8}$|^15[^4]{1}\d{8}$|^16[6]{1}\d{8}$|^17[0,1,2,3,4,5,6,7,8]{1}\d{8}$|^18[\d]{9}$|^19[8,9]{1}\d{8}$/';
        return preg_match($reg, $val) ? true : false;
    }
}