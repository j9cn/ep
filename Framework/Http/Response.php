<?php
/**
 * Created by PhpStorm.
 * User: JH
 * Date: 2017/10/9
 * Time: 9:35
 */
declare(strict_types=1);

namespace EP\Http;


class Response
{
    /**
     * http头
     * @var array
     */
    protected $header = ['EP:' . EP_VER];

    /**
     * cookie
     * @var array
     */
    protected $cookie = [];

    /**
     * cookie配置
     * @var array
     */
    protected $cookie_config = [];

    /**
     * 返回头http类型
     * @var string
     */
    protected $content_type = 'html';

    /**
     * http 状态代码
     * @var int
     */
    protected $http_status = 200;

    /**
     * 停止发送标识
     * @var bool
     */
    protected $is_end_flush = false;

    /**
     * Response instance
     * @var object
     */
    static $instance;

    const MIME_TYPES = [
        'ez' => 'application/andrew-inset',
        'hqx' => 'application/mac-binhex40',
        'cpt' => 'application/mac-compactpro',
        'doc' => 'application/msword',
        'bin' => 'application/octet-stream',
        'dms' => 'application/octet-stream',
        'lha' => 'application/octet-stream',
        'lzh' => 'application/octet-stream',
        'exe' => 'application/octet-stream',
        'class' => 'application/octet-stream',
        'so' => 'application/octet-stream',
        'dll' => 'application/octet-stream',
        'oda' => 'application/oda',
        'pdf' => 'application/pdf',
        'ai' => 'application/postscript',
        'eps' => 'application/postscript',
        'ps' => 'application/postscript',
        'smi' => 'application/smil',
        'smil' => 'application/smil',
        'mif' => 'application/vnd.mif',
        'xls' => 'application/vnd.ms-excel',
        'ppt' => 'application/vnd.ms-powerpoint',
        'wbxml' => 'application/vnd.wap.wbxml',
        'wmlc' => 'application/vnd.wap.wmlc',
        'wmlsc' => 'application/vnd.wap.wmlscriptc',
        'bcpio' => 'application/x-bcpio',
        'vcd' => 'application/x-cdlink',
        'pgn' => 'application/x-chess-pgn',
        'cpio' => 'application/x-cpio',
        'csh' => 'application/x-csh',
        'dcr' => 'application/x-director',
        'dir' => 'application/x-director',
        'dxr' => 'application/x-director',
        'dvi' => 'application/x-dvi',
        'spl' => 'application/x-futuresplash',
        'gtar' => 'application/x-gtar',
        'hdf' => 'application/x-hdf',
        'js' => 'application/x-javascript',
        'json' => 'application/json',
        'skp' => 'application/x-koan',
        'skd' => 'application/x-koan',
        'skt' => 'application/x-koan',
        'skm' => 'application/x-koan',
        'latex' => 'application/x-latex',
        'nc' => 'application/x-netcdf',
        'cdf' => 'application/x-netcdf',
        'sh' => 'application/x-sh',
        'shar' => 'application/x-shar',
        'swf' => 'application/x-shockwave-flash',
        'sit' => 'application/x-stuffit',
        'sv4cpio' => 'application/x-sv4cpio',
        'sv4crc' => 'application/x-sv4crc',
        'tar' => 'application/x-tar',
        'tcl' => 'application/x-tcl',
        'tex' => 'application/x-tex',
        'texinfo' => 'application/x-texinfo',
        'texi' => 'application/x-texinfo',
        't' => 'application/x-troff',
        'tr' => 'application/x-troff',
        'roff' => 'application/x-troff',
        'man' => 'application/x-troff-man',
        'me' => 'application/x-troff-me',
        'ms' => 'application/x-troff-ms',
        'ustar' => 'application/x-ustar',
        'src' => 'application/x-wais-source',
        'xhtml' => 'application/xhtml+xml',
        'xht' => 'application/xhtml+xml',
        'zip' => 'application/zip',
        'au' => 'audio/basic',
        'snd' => 'audio/basic',
        'mid' => 'audio/midi',
        'midi' => 'audio/midi',
        'kar' => 'audio/midi',
        'mpga' => 'audio/mpeg',
        'mp2' => 'audio/mpeg',
        'mp3' => 'audio/mpeg',
        'aif' => 'audio/x-aiff',
        'aiff' => 'audio/x-aiff',
        'aifc' => 'audio/x-aiff',
        'm3u' => 'audio/x-mpegurl',
        'ram' => 'audio/x-pn-realaudio',
        'rm' => 'audio/x-pn-realaudio',
        'rpm' => 'audio/x-pn-realaudio-plugin',
        'ra' => 'audio/x-realaudio',
        'wav' => 'audio/x-wav',
        'pdb' => 'chemical/x-pdb',
        'xyz' => 'chemical/x-xyz',
        'bmp' => 'image/bmp',
        'gif' => 'image/gif',
        'ief' => 'image/ief',
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
        'jpe' => 'image/jpeg',
        'png' => 'image/png',
        'tiff' => 'image/tiff',
        'tif' => 'image/tiff',
        'djvu' => 'image/vnd.djvu',
        'djv' => 'image/vnd.djvu',
        'wbmp' => 'image/vnd.wap.wbmp',
        'ras' => 'image/x-cmu-raster',
        'pnm' => 'image/x-portable-anymap',
        'pbm' => 'image/x-portable-bitmap',
        'pgm' => 'image/x-portable-graymap',
        'ppm' => 'image/x-portable-pixmap',
        'rgb' => 'image/x-rgb',
        'xbm' => 'image/x-xbitmap',
        'xpm' => 'image/x-xpixmap',
        'xwd' => 'image/x-xwindowdump',
        'igs' => 'model/iges',
        'iges' => 'model/iges',
        'msh' => 'model/mesh',
        'mesh' => 'model/mesh',
        'silo' => 'model/mesh',
        'wrl' => 'model/vrml',
        'vrml' => 'model/vrml',
        'css' => 'text/css',
        'html' => 'text/html',
        'htm' => 'text/html',
        'asc' => 'text/plain',
        'txt' => 'text/plain',
        'rtx' => 'text/richtext',
        'rtf' => 'text/rtf',
        'sgml' => 'text/sgml',
        'sgm' => 'text/sgml',
        'tsv' => 'text/tab-separated-values',
        'wml' => 'text/vnd.wap.wml',
        'wmls' => 'text/vnd.wap.wmlscript',
        'etx' => 'text/x-setext',
        'xsl' => 'text/xml',
        'xml' => 'text/xml',
        'mpeg' => 'video/mpeg',
        'mpg' => 'video/mpeg',
        'mpe' => 'video/mpeg',
        'qt' => 'video/quicktime',
        'mov' => 'video/quicktime',
        'mxu' => 'video/vnd.mpegurl',
        'avi' => 'video/x-msvideo',
        'movie' => 'video/x-sgi-movie',
        'ice' => 'x-conference/x-cooltalk'
    ];


    const STATUS_DESCRIPTIONS = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported'
    ];

    /**
     * 单例模式
     * @return Response
     */
    static function getInstance(): Response
    {
        if (!self::$instance) {
            self::$instance = new Response();
        }
        return self::$instance;
    }

    /**
     * @param int $status
     *
     * @return Response
     */
    function setStatus(int $status = 200): self
    {
        $this->http_status = $status;
        return $this;
    }

    /**
     * @return int
     */
    function getHttpStatus(): int
    {
        return $this->http_status;
    }

    /**
     * 设置header信息
     *
     * @param string $header
     *
     * @return $this
     */
    function setHeader(string $header): self
    {
        $this->header[] = $header;
        return $this;
    }

    /**
     * 批量设置header信息
     * @see Response::sendHeader()
     *
     * @param array $headers
     *
     * @return Response
     */
    function setHeaders(array $headers): self
    {
        $this->header = $headers;
        return $this;
    }

    /**
     * 获取要发送到header信息
     * @return array
     */
    function getHeader(): array
    {
        return $this->header;
    }


    /**
     * 设置返回头类型
     *
     * @param string $content_type
     *
     * @return $this
     */
    function setContentType(string $content_type = 'html'): self
    {
        $this->content_type = strtolower($content_type);
        return $this;
    }

    /**
     * 返回ContentType
     * @return string
     */
    function getContentType(): string
    {
        return $this->content_type;
    }

    /**
     * 发送basicAuth认证
     *
     * @param array $config
     *
     * @return bool
     */
    function basicAuth(array $config): bool
    {
        $user = $_SERVER['PHP_AUTH_USER'] ?? false;
        $pw = $_SERVER['PHP_AUTH_PW'] ?? false;
        if (false !== $user && false !== $pw) {
            if (($user == $config['user']) && ($pw == $config['pw'])) {
                return true;
            }
        }
        $realm = $config['realm'] ?? 'Basic Auth';
        $failed_msg = $config['failed_msg'] ?? 'Auth Failed';
        $this->setStatus(401)->setHeader(sprintf('WWW-Authenticate: Basic realm="%s"',
            $realm))->displayOver($failed_msg);
        return false;
    }

    /**
     * 发送http 状态码
     *
     * @param int $code
     * @param string $descriptions
     */
    private function sendStatus(int $code = 200, string $descriptions = '')
    {
        if ($descriptions == '' && isset(self::STATUS_DESCRIPTIONS[$code])) {
            $descriptions = self::STATUS_DESCRIPTIONS[$code];
        }
        $this->setHeader("HTTP/1.1 {$code} {$descriptions}");
    }

    /**
     * 发送ContentType
     */
    private function sendContentType()
    {
        if (isset(self::MIME_TYPES[$this->content_type])) {
            $content_type = self::MIME_TYPES[$this->content_type];
        } else {
            $content_type = self::MIME_TYPES['html'];
        }
        $this->setHeader("Content-Type: {$content_type}; charset=utf-8");
    }


    /**
     * 发送Response头
     */
    private function sendHeader()
    {
        $this->sendContentType();
        $this->sendStatus($this->http_status);
        $herder = $this->getHeader();
        foreach ($herder as $content) {
            header($content);
        }
    }

    /**
     * 输出内容
     *
     * @param string $message
     * @param string $tpl
     *
     * @return bool
     */
    private function flushContent($message, string $tpl = ''): bool
    {
        if ('' !== $tpl && is_file($tpl)) {
            require "{$tpl}";
        } else {
            echo $message;
        }

        return true;
    }

    /**
     * 标识停止输出
     */
    function setEndFlush(): self
    {
        $this->is_end_flush = true;
        return $this;
    }

    /**
     * 获取标识状态,是否终止输出
     * @return bool
     */
    function isEndFlush(): bool
    {
        return $this->is_end_flush;
    }

    /**
     * 重定向
     *
     * @param string $url
     * @param int $status
     */
    function redirect(string $url, int $status = 302)
    {
        $this->setStatus($status)->setHeader("Location: {$url}")->displayOver();
    }

    /**
     * 调用模板输出信息
     *
     * @param string $content
     * @param string $tpl
     */
    function display($content = '', string $tpl = '')
    {
        if (!headers_sent() && PHP_SAPI != 'cli') {
            $this->sendHeader();
        }
        $this->flushContent($content, $tpl);
    }

    /**
     * 输出当前内容并结束
     *
     * @param string $content
     * @param string $tpl
     */
    function displayOver($content = '', $tpl = '')
    {
        $this->setEndFlush();
        $this->display($content, $tpl);
        exit(0);
    }


}