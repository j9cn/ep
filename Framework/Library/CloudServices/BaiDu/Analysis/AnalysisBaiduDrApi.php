<?php
/**
 * Created by PhpStorm.
 * User: jh
 * Date: 2020/2/4
 * Time: 下午8:20
 */

namespace EP\Library\CloudServices\BaiDu\Analysis;


use EP\Library\Curl\HttpRequest;

/**
 * Class AnalysisBaiduDrApi
 * @see https://tongji.baidu.com/api/manual/Chapter2/drapi.html
 * @see https://tongji.baidu.com/api/manual/Chapter1/getData.html
 * @package EP\Library\CloudServices\BaiDu\Analysis
 */
class AnalysisBaiduDrApi
{

    protected $user = '';
    protected $pwd = '';
    protected $token = '';
    protected $account_type = 1;
    protected $site_id = '';

    private $api = 'https://api.baidu.com/json/tongji/v1/';
    private $action = 'ReportService/getData';


    private $curl = null;
    private $result = '';
    private $data = [];
    private $query_data = '';
    private $body_data = [];
    private $filter_data = [];

    private $failures = [
        '8401' => '缺少Token',
        '8402' => '无效Token',
        '8414' => '无效Token',
        '8101' => '无效用户名',
        '9101' => '无效站点ID或无权限'
    ];

    /**
     * AnalysisBaiduDrApi constructor.
     *
     * @param array $config
     * <pre>
     * array(
     *  username => ***,
     *  password => ***,
     *  token => ***,
     *  account_type => 1
     * )
     * </pre>
     */
    public function __construct(array $config = [])
    {
        if ($config) {
            $this->setConfig($config);
        }
    }

    /**
     * 设置账号信息
     *
     * @param array $config
     *
     * @return $this
     */
    function setConfig(array $config)
    {
        if (isset($config['username'])) {
            $this->user = $config['username'];
        }
        if (isset($config['password'])) {
            $this->pwd = $config['password'];
        }
        if (isset($config['token'])) {
            $this->token = $config['token'];
        }
        if (isset($config['site_id'])) {
            $this->setSiteId($config['site_id']);
        }
        return $this;
    }

    /**
     * @param $site_id
     *
     * @return $this
     */
    function setSiteId($site_id)
    {
        $this->site_id = $site_id;
        return $this;
    }

    /**
     * @param $token
     *
     * @return $this
     */
    function setToken($token)
    {
        $this->token = $token;
        return $this;
    }

    /**
     * 设置数据条件
     *
     * @param array $filter
     *
     * @return $this
     */
    function setFilter($filter = [])
    {
        if ($filter && is_array($filter)) {
            $this->filter_data = $filter;
        }
        return $this;
    }

    /**
     * 返回错误信息
     * @return array
     */
    function getFailures()
    {
        $fail = [
            'code' => -1,
            'fail_status' => 0,
            'fail_msg' => '未知错误',
            'query_data' => $this->query_data,
            'result_ori' => $this->result
        ];

        if (isset($this->data['header']['status'])) {
            $header = $this->data['header'];
            $fail['fail_status'] = $header['status'];
            if (0 != $header['status']) {
                if (!empty($header['failures'])) {
                    $failures = $header['failures'][0];
                    $fail['code'] = $failures['code'];
                    $fail['fail_msg'] = $this->failures[$failures['code']];
                }
            }
        }
        return $fail;
    }


    /**
     * 网站列表
     * @see https://tongji.baidu.com/api/manual/Chapter1/getSiteList.html
     * @return array
     */
    function getSiteList()
    {
        $this->action = 'ReportService/getSiteList';
        $this->query();
        return $this->parseResult();
    }


    /**
     * 网站概况(趋势数据)
     * @see https://tongji.baidu.com/api/manual/Chapter1/overview_getTimeTrendRpt.html
     *
     * @param $start_date
     * @param $end_date
     * @param string $metrics
     * <p>
     * pv_count (浏览量PV)
     * visitor_count (访客数UV)
     * ip_count (IP 数)
     * bounce_ratio (跳出率，%)
     * avg_visit_time (平均访问时长，秒)
     * trans_count (转化次数)
     * </p>
     *
     * @return array
     */
    function getTimeTrendRpt($start_date = 0, $end_date = 0, $metrics = 'pv_count,visitor_count,ip_count')
    {
        $this->formatData($start_date, $end_date, $metrics, 'overview/getTimeTrendRpt');
        $this->query();
        return $this->parseResult();
    }

    /**
     * 网站概况(地域分布)
     * @see https://tongji.baidu.com/api/manual/Chapter1/overview_getDistrictRpt.html
     *
     * @param int $start_date
     * @param int $end_date
     *
     * @return array
     */
    function getDistrictRpt($start_date = 0, $end_date = 0)
    {
        $this->formatData($start_date, $end_date, 'pv_count', 'overview/getDistrictRpt');
        $this->query();
        return $this->parseResult();
    }

    /**
     * 网站概况(来源网站、搜索词、入口页面、受访页面)
     * @see https://tongji.baidu.com/api/manual/Chapter1/overview_getCommonTrackRpt.html
     *
     * @param int $start_date
     * @param int $end_date
     *
     * @return array
     */
    function getCommonTrackRpt($start_date = 0, $end_date = 0)
    {
        $this->formatData($start_date, $end_date, 'pv_count', 'overview/getCommonTrackRpt');
        $this->query();
        return $this->parseResult();
    }

    /**
     * 趋势分析
     * @see https://tongji.baidu.com/api/manual/Chapter1/trend_time_a.html
     *
     * @param int $start_date
     * @param int $end_date
     * @param string $metrics
     * <p>
     * pv_count (浏览量(PV))
     * pv_ratio (浏览量占比，%)
     * visit_count (访问次数)
     * visitor_count (访客数(UV))
     * new_visitor_count (新访客数)
     * new_visitor_ratio (新访客比率，%)
     * ip_count (IP 数)
     * bounce_ratio (跳出率，%)
     * avg_visit_time (平均访问时长，秒)
     * avg_visit_pages (平均访问页数)
     * trans_count (转化次数)
     * trans_ratio (转化率，%)
     * avg_trans_cost (平均转化成本，元)
     * income (收益，元)
     * profit (利润，元)
     * roi (投资回报率，%)
     * </p>
     * @param array $filter
     * <p>
     * gran(时间粒度)
     * source(来源类型)
     * clientDevice(设备类型)
     * area(地域)
     * visitor(访客类型)
     * </p>
     *
     * @return array
     */
    function trendAnalysis(
        $start_date = 0,
        $end_date = 0,
        $filter = [],
        $metrics = 'pv_count,visitor_count,ip_count,new_visitor_count'
    ) {
        $this->setFilter($filter);
        $this->formatData($start_date, $end_date, $metrics, 'trend/time/a');
        $this->query();
        return $this->parseResult();
    }


    /**
     * 实时访客
     * @see https://tongji.baidu.com/api/manual/Chapter1/trend_latest_a.html
     *
     * @param array $filter
     * @param string $metrics
     *
     * @return array
     */
    function trendLatest($filter = [], $metrics = 'area,source,visitorId,visit_pages')
    {
        $this->setFilter($filter);
        $this->body_data = [
            'metrics' => $metrics,
            'method' => 'trend/latest/a',
            'order' => 'visit_pages,desc',
            'max_results' => 100
        ];
        $this->query();
        return $this->parseResult();
    }

    /**
     * 全部来源
     * @see https://tongji.baidu.com/api/manual/Chapter1/source_all_a.html
     *
     * @param int $start_date
     * @param int $end_date
     * @param array $filter
     * <p>
     * viewType:type(按来源分类) / site (按来源网站)
     * clientDevice(设备类型)
     * visitor(访客类型)
     * </p>
     * @param string $metrics
     * <p>
     * pv_count (浏览量(PV))
     * pv_ratio (浏览量占比，%)
     * visit_count (访问次数)
     * visitor_count (访客数(UV))
     * new_visitor_count (新访客数)
     * new_visitor_ratio (新访客比率，%)
     * ip_count (IP 数)
     * bounce_ratio (跳出率，%)
     * avg_visit_time (平均访问时长，秒)
     * avg_visit_pages (平均访问页数)
     * trans_count (转化次数)
     * trans_ratio (转化率，%)
     * </p>
     *
     * @return array
     */
    function sourceAll(
        $start_date = 0,
        $end_date = 0,
        $filter = [],
        $metrics = 'pv_count,visit_count,visitor_count'
    ) {
        $this->setFilter($filter);
        $this->formatData($start_date, $end_date, $metrics, 'source/all/a');
        $this->query();
        return $this->parseResult();
    }

    /**
     * 搜索引擎
     * @see https://tongji.baidu.com/api/manual/Chapter1/source_engine_a.html
     *
     * @param int $start_date
     * @param int $end_date
     * @param array $filter
     * @param string $metrics
     *
     * @return array
     */
    function sourceEngine(
        $start_date = 0,
        $end_date = 0,
        $filter = [],
        $metrics = 'pv_count,visit_count,visitor_count'
    ) {
        $this->setFilter($filter);
        $this->formatData($start_date, $end_date, $metrics, 'source/engine/a');
        $this->query();
        return $this->parseResult();
    }

    /**
     * 搜索词
     * @see https://tongji.baidu.com/api/manual/Chapter1/source_searchword_a.html
     *
     * @param int $start_date
     * @param int $end_date
     * @param array $filter
     * @param string $metrics
     *
     * @return array
     */
    function sourceSearchWord(
        $start_date = 0,
        $end_date = 0,
        $filter = [],
        $metrics = 'pv_count,visit_count,visitor_count'
    ) {
        $this->setFilter($filter);
        $this->formatData($start_date, $end_date, $metrics, 'source/searchword/a');
        $this->query();
        return $this->parseResult();
    }

    /**
     * 外部链接
     * @see https://tongji.baidu.com/api/manual/Chapter1/source_link_a.html
     *
     * @param int $start_date
     * @param int $end_date
     * @param array $filter
     * @param string $metrics
     *
     * @return array
     */
    function sourceLink(
        $start_date = 0,
        $end_date = 0,
        $filter = [],
        $metrics = 'pv_count,visit_count,visitor_count'
    ) {
        $this->setFilter($filter);
        $this->formatData($start_date, $end_date, $metrics, 'source/link/a');
        $this->query();
        return $this->parseResult();
    }


    /**
     * 受访页面
     * @see https://tongji.baidu.com/api/manual/Chapter1/visit_toppage_a.html
     *
     * @param int $start_date
     * @param int $end_date
     * @param array $filter
     * @param string $metrics
     *
     * @return array
     */
    function visitTopPage($start_date = 0, $end_date = 0, $filter = [], $metrics = 'pv_count,visitor_count')
    {
        $this->setFilter($filter);
        $this->formatData($start_date, $end_date, $metrics, 'visit/toppage/a');
        $this->query();
        return $this->parseResult();
    }

    /**
     * 入口页面
     * @see https://tongji.baidu.com/api/manual/Chapter1/visit_landingpage_a.html
     *
     * @param int $start_date
     * @param int $end_date
     * @param string $metrics
     *
     * @return array
     */
    function visitLandingPage($start_date = 0, $end_date = 0, $metrics = 'visit_count,visitor_count')
    {
        $this->formatData($start_date, $end_date, $metrics, 'visit/landingpage/a');
        $this->query();
        return $this->parseResult();
    }

    /**
     * 受访域名
     * @see https://tongji.baidu.com/api/manual/Chapter1/visit_topdomain_a.html
     *
     * @param int $start_date
     * @param int $end_date
     * @param array $filter
     * @param string $metrics
     *
     * @return array
     */
    function visitTopDomain(
        $start_date = 0,
        $end_date = 0,
        $filter = [],
        $metrics = 'pv_count,visit_count,visitor_count'
    ) {
        $this->setFilter($filter);
        $this->formatData($start_date, $end_date, $metrics, 'visit/topdomain/a');
        $this->query();
        return $this->parseResult();
    }

    /**
     * 地域分布(按省)
     * @see https://tongji.baidu.com/api/manual/Chapter1/visit_district_a.html
     *
     * @param int $start_date
     * @param int $end_date
     * @param array $filter
     * @param string $metrics
     *
     * @return array
     */
    function visitDistrict(
        $start_date = 0,
        $end_date = 0,
        $filter = [],
        $metrics = 'pv_count,visit_count,visitor_count'
    ) {
        $this->setFilter($filter);
        $this->formatData($start_date, $end_date, $metrics, 'visit/district/a');
        $this->query();
        return $this->parseResult();
    }

    /**
     * 地域分布(按国家)
     * @see https://tongji.baidu.com/api/manual/Chapter1/visit_world_a.html
     *
     * @param int $start_date
     * @param int $end_date
     * @param array $filter
     * @param string $metrics
     *
     * @return array
     */
    function visitWorld(
        $start_date = 0,
        $end_date = 0,
        $filter = [],
        $metrics = 'pv_count,visit_count,visitor_count'
    ) {
        $this->setFilter($filter);
        $this->formatData($start_date, $end_date, $metrics, 'visit/world/a');
        $this->query();
        return $this->parseResult();
    }

    private function query()
    {
        $this->curl = new HttpRequest();
        $data = $this->buildQueryData($this->body_data);
        $this->query_data = $data;
        $this->result = $this->curl->request($this->api . $this->action, $data, $this->curl::METHOD_POST);
    }

    private function formatData($start_date = 0, $end_date = 0, $metrics = '', $method)
    {

        if ($end_date < 10) {
            if ($end_date <= $start_date) {
                $end_date = date('Ymd', strtotime("- $end_date days"));
            } else {
                $end_date = date('Ymd');
            }
        }

        if ($start_date < 10) {
            $start_date = date('Ymd', strtotime("- $start_date days"));
        }
        $this->body_data = [
            'start_date' => $start_date,
            'end_date' => $end_date,
            'metrics' => $metrics,
            'method' => $method
        ];
    }

    private function buildQueryData(array $body_data = [])
    {
        $data = [
            'header' => [
                'username' => $this->user,
                'password' => $this->pwd,
                'token' => $this->token,
                'account_type' => $this->account_type
            ]
        ];
        if (!empty($body_data)) {
            $data['body'] = $body_data;
            $data['body']['site_id'] = $this->site_id;
        }
        if (!empty($this->filter_data)) {
            $data['body'] += $this->filter_data;
        }

        return json_encode($data);
    }

    /**
     * 解析返回数据
     * @return array
     */
    private function parseResult()
    {
        $result = json_decode($this->result, true);
        $this->data = $result;
        if (json_last_error() === JSON_ERROR_NONE) {
            if (isset($result['header'])) {
                switch ((int)$result['header']['status']) {
                    case 3:
                    case 2:
                        return array();
                    case 0:
                        if (!empty($result['body']['data'])) {
                            if (isset($result['body']['data'][0]['list'])) {
                                return $result['body']['data'][0]['list'];
                            }
                            if (isset($result['body']['data'][0]['result'])) {
                                return $result['body']['data'][0]['result'];
                            }
                            return $result['body']['data'];
                        }
                        return array();
                    default:
                        return (array)$result['body']['data'];
                }
            }
        }
        return array();
    }


}