<?php
namespace vitex\ext;

class Captcha
{
    //配置
    /**
     * 验证码类型，1为普通验证码，2为计算型验证码
     * @var int
     */
    private $type = 1;
    /**
     * 普通验证码字符长度，默认为4
     * @var integer
     */
    private $length = 4;
    /**
     * 图片的宽高像素
     * @var integer
     */
    private $width  = 80;
    private $height = 40;
    /**
     * 干扰线数量
     * @var integer
     */
    private $linenum = 4;
    private $font;
    /**
     * 渲染引擎，默认是自动判断 优先 imagick > gd,此值如果设定只能是 gd/imagick
     * @var string
     */
    private $engine = '';
    public function __construct($setting = [])
    {
        $this->font = dirname(__FILE__) . '/fangzheng.ttf';
        if ($this->type == 2) {
            $this->width = 100;
        }
        foreach ($setting as $key => $val) {
            $this->{$key} = $val;
        }
    }
    /**
     * 生成验证码
     * @param  string $file 图片保存文件名，不指定则图片会被直接输出
     * @return null
     */
    public function get($file = "")
    {
        $support             = $this->support();
        list($code, $answer) = $this->generateCode();
        $image               = null;
        @session_start();
        $_SESSION['vitex.captcha.answer'] = strtolower($answer);
        if ($support == 'imagick') {
            $image = new \Imagick();
            $image->newImage($this->width, $this->height, "none");
            $image->setImageFormat('png');
            $image = $this->imagickLine($image, $this->linenum);
            $image = $this->imagickDrawText($image, $code);
            $image->swirlImage(30);
            $image->oilPaintImage(1);
            if ($file) {
                $image->writeImage($file);
            } else {
                header("Content-type:image/png");
                echo $image->getImageBlob();
            }
        } else {
            $image = imagecreate($this->width, $this->height);
            $color = imagecolorallocate($image, 255, 255, 255);
            imagecolortransparent($image, $color);
            $this->gdLine($image, $this->linenum);
            $this->gdDrawText($image, $code);
            if ($file) {
                imagepng($image, $file);
            } else {
                header("Content-type: image/jpeg; Cache-Control: no-store, no-cache, must-revalidate");
                imagepng($image);
            }
        }
    }
    /**
     * 验证码检查
     * @param  string $code               输入的验证码结果
     * @return bool   true为验证通过 false为验证失败
     */
    public static function test($code)
    {
        @session_start();
        $code   = strtolower($code);
        $answer = $_SESSION['vitex.captcha.answer'];

        $_SESSION['vitex.captcha.answer'] = null;
        if ($answer == $code) {
            return true;
        }
        return false;
    }
    /**
     * 生成验证码内容以及验证答案
     * @return array code 返回生成的验证码
     */
    protected function generateCode()
    {
        $answer = '';
        if ($this->type == "1") {
            $code   = md5(time());
            $code   = substr($code, rand(3, 5), $this->length);
            $code   = str_replace(['2', '0', 'o', '1', 'i'], ['Z', '9', '8', 'L', 'A'], $code);
            $code   = strtoupper($code);
            $answer = $code;
        }elseif ($this->type == "3"){
            $str ="冰消雪融风和日丽山光水色草长莺飞山明水秀明月清风奇花异草鸟语花香千山万水跋山涉水山光水色山明水秀山高水长山高水低穷山恶水水秀山明一碧千里翠色欲流千山一碧碧波荡漾翠绿欲滴绿草如茵万紫千红花红柳绿姹紫嫣红一碧千里翠色欲流翠色欲滴五彩缤纷五光十色春光明媚春意盎然春色满园春暖花开春和景明春山如笑风和日丽烈日炎炎和风细雨彤云密布晴空万里万里无云狂风暴雨倾盆大雨天昏地暗百花齐放百花盛开百花争艳花团锦簇含苞欲放花红柳绿争奇斗艳姹紫嫣红五彩缤纷一碧千里翠色欲流绿草如茵郁郁葱葱枝繁叶茂红叶似火枯木逢春危峰兀立崇山峻岭怪石嶙峋奇峰罗列水平如镜汹涌澎湃波澜壮阔微波粼粼浩浩荡荡碧空如洗晴空万里万里无云愁眉不展眉飞色舞神采奕奕满面春风嬉皮笑脸愁眉苦脸神采飞扬喜形于色得意忘形垂头丧气兴高采烈炯炯有神喜笑颜开喜上眉梢心急如焚心慌意乱心甘情愿惴惴不安忐忑不安心惊肉跳胆战心惊心乱如麻心乱如麻拾金不昧助人为乐默默奉献大公无私坚贞不屈勇往直前尊老爱幼见义勇为乐善好施舍己为人";
            $str    = str_split($str,12);
            $index  = mt_rand(0,count($str)-1);
            $code   = $str[$index];
            $answer = $code;
        } else {
            $operate               = ['+', '-'];
            $opindex               = rand(0, 1);
            $opnum1                = rand(0, 9);
            $opnum2                = rand(0, 9);
            list($opnum1, $opnum2) = ($opindex == 1 ? [max($opnum1, $opnum2), min($opnum1, $opnum2)] : [$opnum1, $opnum2]);
            $code                  = $opnum1 . $operate[$opindex] . $opnum2 . '=?';
            $answer                = $opindex == 1 ? ($opnum1 - $opnum2) : ($opnum1 + $opnum2);
        }
        return [$code, $answer];
    }
    /**
     * 在图片上添加验证文字
     * @param  resource $image         图片对象
     * @param  string   $text          要添加的字符
     * @return resource 图片对象
     */
    protected function imagickDrawText($image, $text)
    {
        $draw = new \ImagickDraw();
        $draw->setFont($this->font);

        $draw->setFontSize($this->height * 0.8);
        $draw->setFillColor(new \ImagickPixel('#333333'));
        $draw->setStrokeAntialias(true);
        $draw->setTextAntialias(true);
        $metrics = $image->queryFontMetrics($draw, $text);
        $draw->annotation(0, $metrics['ascender'], $text);
        $image->drawImage($draw);
        $draw->destroy();
        return $image;
    }
    protected function gdDrawText($image, $text)
    {
        //干扰字符
        $code  = md5(time());
        $color = imagecolorallocate($image, rand(180, 200), rand(180, 200), rand(180, 200));
        imagettftext($image, $this->height * 0.8, 3, $this->width * 0.05 + 1, $this->height * 0.8 + 2, $color, $this->font, $code);
        //实际验证码
        $color = imagecolorallocate($image, rand(30, 120), rand(30, 120), rand(30, 120));
        imagettftext($image, $this->height * 0.6, 3, $this->width * 0.05, $this->height * 0.8, $color, $this->font, $text);
    }

    /**
     * 在图片上画线
     * @param $image
     * @param  integer $num 画线数量
     * @return object 图片对象
     * @internal param object $img 图片对象
     */
    protected function gdLine($image, $num = 4)
    {
        for ($i = 0; $i <= $num; $i++) {
            $randcolor = $this->randColor();
            $color     = imagecolorallocate($image, hexdec(substr($randcolor, 1, 2)), hexdec(substr($randcolor, 3, 2)), hexdec(substr($randcolor, 5, 2)));
            //设置颜色
            $x1 = rand(0, $this->width);
            $y1 = rand(0, $this->height);
            $x2 = rand(0, $this->width);
            $y2 = rand(0, $this->height);
            imageline($image, $x1, $y1, $x2, $y2, $color);
        }
    }
    /**
     * 在图片上划线
     * @param  object  $image             Imagick的实例
     * @param  integer $num               画线的数量
     * @return object  Imagick的实例
     */
    protected function imagickLine($image, $num = 4)
    {
        $draw = new \ImagickDraw();
        for ($i = 0; $i < $num; $i++) {
            $color  = $this->randColor();
            $startx = rand(0, $this->width);
            $endx   = rand(0, $this->width);
            $starty = rand(0, $this->height);
            $endy   = rand(0, $this->height);
            $draw->setStrokeColor($color);
            $draw->setFillColor($color);
            $draw->setStrokeWidth(2);
            $draw->line($startx, $starty, $endx, $endy);
        }
        $image->drawImage($draw);
        $draw->destroy();
        return $image;
    }

    /**
     * 随机生成一个十六进制的色值
     * @return string 色值
     */
    protected function randColor()
    {
        $color = '';
        for ($i = 0; $i < 6; $i++) {
            $color .= dechex(rand(0, 15));
        }
        return '#' . $color;
    }

    /**
     * 获取支持的图片处理扩展，imagick> gd2
     * @return string
     */
    protected function support()
    {
        if ($this->engine) {
            return $this->engine;
        }
        if (class_exists('Imagick')) {
            return 'imagick';
        }
        return 'gd';
    }
}
