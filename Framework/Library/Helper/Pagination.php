<?php
/**
 * Created by PhpStorm.
 * User: JH
 * Date: 2017/12/23
 * Time: 21:02
 */
declare(strict_types=1);

namespace EP\Library\Helper;


class Pagination
{
    private $css = <<<css
.paginate-container {
    margin-top: 20px;
    margin-bottom: 15px;
    text-align: center;
    clear: left;
    clear: both;
}
.paginate-container .pagination {
    display: inline-block;
}
.pagination a:first-child, .pagination span:first-child, .pagination em:first-child {
    margin-left: 0;
    border-top-left-radius: 3px;
    border-bottom-left-radius: 3px;
}
.pagination a, .pagination span, .pagination em {
    position: relative;
    float: left;
    padding: 7px 12px;
    margin-left: -1px;
    font-size: 13px;
    font-style: normal;
    font-weight: 600;
    color: ___CSS_COLOR___;
    white-space: nowrap;
    vertical-align: middle;
    cursor: pointer;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
    background: #fff;
    border: 1px solid #e1e4e8;
}
.pagination .current, .pagination .current:hover {
    z-index: 3;
    color: #fff;
    background-color: ___CSS_COLOR___;
    border-color: ___CSS_COLOR___;
}
.pagination .gap, .pagination .disabled, .pagination .gap:hover, .pagination .disabled:hover {
    color: #d1d5da;
    cursor: default;
    background-color: #fafbfc;
}
.pagination::after {
    display: table;
    clear: both;
    content: "";
}
.pagination::before {
    display: table;
    content: "";
}
css;

    private $current = 1;
    private $total = 1;
    private $prev = '上一页';
    private $next = '下一页';
    private $offset = 3;
    private $data = array('p' => 1, 'result_count' => 1, 'total_page' => 1);
    private $params = array();

    private $css_color = '#0366d6';


    function __construct(array $data = array(), array $config = array())
    {
        if (!empty($data)) {
            $this->initData($data);
        }
        foreach ($config as $key => $value) {
            $this->{$key} = $value;
        }
        $this->css = str_replace('___CSS_COLOR___', $this->css_color, $this->css);
    }


    private function initData(array $data)
    {
        foreach ($data as $key => $val) {
            $this->data[$key] = $val;
        }
        $this->total = $this->data['total_page'];
        $this->current = ($this->data['p'] > $this->total) ? $this->total : $this->data['p'];
        parse_str($_SERVER['QUERY_STRING'], $this->params);
    }


    private function url($page)
    {
        $p = array(
            'p' => $page
        );
        return '?' . http_build_query(array_merge($this->params, $p));
    }

    private function prevPage()
    {
        if ($this->data['p'] <= 1) {
            return "<span class='previous_page disabled'>{$this->prev}</span>";
        }
        return "<a class=previous_page href={$this->url($this->current - 1)}>{$this->prev}</a>";
    }

    private function nextPage()
    {
        if ($this->current >= $this->total) {
            return "<span class='next_page disabled'>{$this->next}</span>";
        }
        return "<a class=next_page href={$this->url($this->current + 1)}>{$this->next}</a>";
    }


    private function listPage($num_page)
    {
        if ($num_page < 1) {
            return '';
        }
        if ($this->current == $num_page) {
            return "<em class='current'>{$num_page}</em>";
        }
        return "<a href={$this->url($num_page)}>{$num_page}</a>";
    }

    function build()
    {
        $_tpl = <<<tpl
<div class="paginate-container">
    <div class="pagination">

tpl;
        $tpl_ = <<<tpl
        
    </div>
</div>
tpl;
        $css = "<style>{$this->css}</style>\n";
        if (($this->current + $this->offset) > $this->total) {
            $begin = $this->total - $this->offset * 2;
        } else {
            $begin = $this->current - $this->offset;
        }

        $list = [];
        for ($i = $begin ; $begin + $this->offset * 2 >= $i ; $i++) {
            $list[] = $this->listPage($i);
        }
        return $css . $_tpl . $this->prevPage() . implode("\n", $list) . $this->nextPage() . $tpl_;
    }

    /**
     * @param array $data $_GET|$_POST
     * @param string $page_key
     * @param int $limit
     * @param string $count_key
     *
     * @return array
     */
    static function getPage(array $data, $page_key = 'p', $limit = 25, $count_key = 'count')
    {
        $page = [
            'p' => empty($data[$page_key]) ? 1 : intval($data[$page_key]),
            'limit' => $limit
        ];
        if (!empty($data['limit'])) {
            $page['limit'] = ((int)$data['limit'] > 0) ? (int)$data['limit'] : $limit;
        }
        if (!empty($data[$count_key])) {
            $page['result_count'] = (int)$data[$count_key];
        }
        return $page;
    }

    static function init(array $data, array &$page)
    {
        $page = self::getPage($data);
    }

}