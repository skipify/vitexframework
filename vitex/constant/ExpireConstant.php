<?php


namespace vitex\constant;

/**
 * 缓存时间一些预制的值
 * 单位均为秒
 * @package vitex\constant
 */
class ExpireConstant
{
    /**
     * 一些基数  可以和下面内容进行计算
     */
    const BASE_2 = 2;
    const BASE_3 = 3;
    const BASE_4 = 4;
    const BASE_5 = 5;
    const BASE_6 = 6;
    const BASE_7 = 7;
    const BASE_8 = 8;
    const BASE_9 = 9;

    const BASE_60 = 60;

    /**
     * 秒
     */
    const SECOND = 1;
    const SECOND_15 = 15;
    const SECOND_30 = 30;
    const SECOND_45 = 45;

    /**
     * 分钟转为秒
     */
    const MINUTE = 60;
    const MINUTE_2 = 120;
    const MINUTE_3 = 180;
    const MINUTE_4 = 240;
    const MINUTE_5 = 300;
    const MINUTE_6 = 360;
    const MINUTE_7 = 420;
    const MINUTE_8 = 480;
    const MINUTE_9 = 540;
    const MINUTE_10 = 600;

    const FIVE_MINUTE = 300;
    const TEN_MINUTE = 600;

    /**
     * 按小时
     */
    const HALF_HOUR = 1800;
    const HOUR = 3600;
    const HOUR_2 = 7200;
    const HOUR_3 = 10800;
    const HOUR_4 = 14400;
    const HOUR_5 = 18000;
    const HOUR_6 = 21600;
    const HOUR_7 = 25200;
    const HOUR_8 = 28800;
    const HOUR_9 = 32400;

    /**
     * 按天
     */
    const DAY = 86400;
    const DAY_2 = 172800;
    const DAY_3 = 259200;
    const DAY_4 = 345600;
    const DAY_5 = 432000;
    const DAY_6 = 518400;
    const DAY_7 = 604800;
    const DAY_8 = 691200;
    const DAY_9 = 777600;


}