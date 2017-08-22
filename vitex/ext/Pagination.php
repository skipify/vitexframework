<?php
namespace vitex\ext;


use vitex\core\Exception;

class Pagination
{
    /**
     * 显示的页码数量
     * @var integer
     */
    protected $linknum   = 10;
    protected $totalpage = null;
    /**
     * 信息总记录行数
     * @var int
     */
    protected $totalrows = null;
    /**
     * 信息每页行数
     * @var integer
     */
    protected $perpage = 10;
    /**
     * 标签说明可以指定不同的表示方法
     * @var array
     */
    protected $label = [
        'first' => '首页',
        'last'  => '末页',
        'prev'  => '上一页',
        'next'  => '下一页',
    ];
    /**
     * 链接地址
     * @var string
     */
    protected $url;
    /**
     * 获取分页的标示，默认是直接获取get的page参数
     * @var string
     */
    protected $param = 'page';

    public function __construct($setting = [])
    {
        foreach ($setting as $key => $val) {
            $this->{$key} = $val;
        }
        if (!$this->url) {
            throw new Exception('请指定分页的URL',Exception::CODE_PARAM_NUM_ERROR);
        }
        if ($this->totalpage === null && $this->totalrows === null) {
            throw new Exception('总页数totalpage或者信息总条数totalrows至少要设置一个',Exception::CODE_PARAM_NUM_ERROR);
        }
        $this->totalpage = $this->totalpage ?: ceil($this->totalrows / $this->perpage);
    }

    /**
     * 获取分页代码
     * @param string $wrap
     * @param bool $retArr
     * @return string 返回的分页html
     */
    public function get($wrap = "",$retArr = false)
    {
        $curpage = intval(isset($_GET[$this->param]) ? $_GET[$this->param] : 1);
        $pages   = $this->getLink($this->url, $curpage);
        if ($wrap) {
            foreach ($pages as &$page) {
                $page = '<' . $wrap . '>' . $page . '</' . $wrap . '>';
            }
        }
        return $retArr ? $pages : implode('', $pages);
    }
    /**
     * 获取分页数组
     * @return array 返回分页的数组，每个元素为一个页码
     */
    public function getArray()
    {
        $curpage = intval(isset($_GET[$this->param]) ? $_GET[$this->param] : 1);
        return $this->getLink($this->url, $curpage);
    }
    /**
     * 构造分页内容
     * @param  string $url           链接url
     * @param  int    $curpage       当前页码
     * @return array  分页信息
     */
    protected function getLink($url, $curpage)
    {
        $url               = strpos($url, '?') === false ? $url . '?' . $this->param . '=' : $url . '&' . $this->param . '=';
        list($start, $end) = $this->getPageBound($curpage);

        $links   = [];
        $links[] = '<a href="' . $url . '1" class="firstpage">' . $this->label['first'] . '</a>';
        $links[] = '<a href="' . $this->getPrevUrl($curpage, $url) . '" class="prevpage">' . $this->label['prev'] . '</a>';
        for ($i = $start; $i <= $end; $i++) {
            if ($i == $curpage) {
                $links[] = '<a href="javascript:;" class="curpage">' . $i . '</a>';
            } else {
                $links[] = '<a href="' . $url . $i . '" class="pagenum">' . $i . '</a>';
            }
        }
        $links[] = '<a href="' . $this->getNextUrl($curpage, $url) . '" class="nextpage">' . $this->label['next'] . '</a>';
        $links[] = '<a href="' . $url . $this->totalpage . '" class="lastpage">' . $this->label['last'] . '</a>';
        return $links;
    }
    /**
     * 获取上一页的链接地址
     * @param  int    $curpage       当前页码
     * @param  string $url           当前链接地址
     * @return string 链接地址
     */
    public function getPrevUrl($curpage, $url)
    {
        if ($curpage == 1) {
            return 'javascript:;';
        }
        $pagenum = max(1, $curpage - 1);
        return $url . $pagenum;
    }
    /**
     * 获取上一页的链接地址
     * @param  int    $curpage       当前页码
     * @param  string $url           当前链接地址
     * @return string 链接地址
     */
    public function getNextUrl($curpage, $url)
    {
        if ($curpage == $this->totalpage) {
            return 'javascript:;';
        }
        $pagenum = min($this->totalpage, $curpage + 1);
        return $url . $pagenum;
    }
    /**
     * 获取当前要展示的页面数
     * @param  int   $curpage                         当前页码
     * @return array 要显示的页码开始结束
     */
    protected function getPageBound($curpage)
    {
        if ($this->totalpage <= $this->linknum) {
            $start = 1;
            $end   = $this->totalpage;
        } else {
            $preNum = ceil($this->linknum / 2);
            $start  = max(1, $curpage - $preNum);
            $end    = min($this->totalpage, ($curpage + $preNum));
        }
        return [$start, $end];
    }
}
