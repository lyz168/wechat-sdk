<?php

namespace lyz\wechat\utils;

use lyz\wechat\exceptions\InvalidArgumentException;

use function PHPSTORM_META\type;

/**
 * A basic CURL wrapper 仅用于当前项目
 * @package lyz\wechat\utils
 */
class Curl
{
    /**
     * 存储当前CURL请求的资源句柄
     */
    protected $ch;

    /**
     * 唯一id
     *
     * @var string
     */
    public $id;

    /**
     * 请求地址
     * @var string
     */
    public $url;

    /**
     * 为请求读取和写入cookie的文件
     * @var array
     */
    private $cookies = array();

    /**
     * 与请求一起发送的标头的关联数组
     * @var array
     */
    private $headers = array();

    /**
     * 与请求一起发送的 CURLOPT 选项的关联数组
     * @var array
     */
    protected $options = array();

    /**
     * 初始化Curl对象
     *
     * @param array  $options  CURLOPT 选项
     */
    public function __construct($options = [])
    {
        if (!extension_loaded('curl')) {
            throw new \ErrorException('cURL library is not loaded');
        }

        $this->id = uniqid('', true);

        $this->ch = curl_init();

        if (isset($options)) {
            $this->setOpts($options);
        }

        $this->setOpt(CURLOPT_RETURNTRANSFER, true);

        $this->setOpts([
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HEADER => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);
    }

    /**
     * GET
     * @param string $url
     * @param array|string $data get请求参数
     * @return array|boolean
     **/
    public function get($url = '', $data = [])
    {
        if ($url !== '') {
            $this->setUrl((string)$url, $data);
        }

        $this->setOpt(CURLOPT_CUSTOMREQUEST, 'GET');
        $this->setOpt(CURLOPT_HTTPGET, true);

        return $this->exec();
    }

    /**
     * POST
     *
     * @param string $url
     * @param array  $data post 请求参数
     * @return array|boolean
     **/
    public function post($url = '', $data = array())
    {
        if ($url !== '') {
            $this->setUrl((string)$url);
        }

        $this->setOpt(CURLOPT_POST, true);
        $this->setOpt(CURLOPT_POSTFIELDS, $this->buildPostData($data));

        return $this->exec();
    }

    /**
     * 执行
     *
     * @return array|boolean
     **/
    public function exec()
    {
        if (empty($this->url)) {
            throw new \ErrorException('url is must');
        }

        $response = curl_exec($this->ch);
        $error_code = curl_errno($this->ch);

        if ($error_code > 0) {
            // error_code: error_message
            $errorMessage = curl_strerror($error_code) . '(' . $error_code . '): ' . curl_error($this->ch);
            throw new \Exception($errorMessage);
        } else {
            // CURLOPT_HEADER 为 true 时使用下面方法提取数据
            // // headers 正则表达式
            // $pattern = '#HTTP/\d\.\d.*?$.*?\r\n\r\n#ims';

            // // 从响应中提取 headers
            // preg_match_all($pattern, $response, $matches);
            // $headers_string = array_pop($matches[0]);
            // $headers = explode("\r\n", str_replace("\r\n\r\n", '', $headers_string));

            // // 从响应正文中删除 headers
            // $body = str_replace($headers_string, '', $response);

            // // 从第一个 headers 中提取版本和状态
            // $version_and_status = array_shift($headers);
            // preg_match('#HTTP/(\d\.\d)\s(\d\d\d)\s(.*)#', $version_and_status, $matches);
            // $headers['Http-Version'] = $matches[1];
            // $headers['Status-Code'] = $matches[2];
            // $headers['Status'] = $matches[2] . ' ' . $matches[3];

            // // headers 转换为关联数组
            // foreach ($headers as $header) {
            //     preg_match('#(.*?)\:\s(.*)#', $header, $matches);
            //     $headers[$matches[1]] = $matches[2];
            // }

            $response = json_decode($response, true);
        }

        $this->close();

        return $response;
    }

    /**
     * Close
     */
    public function close()
    {
        if (is_resource($this->ch) || $this->ch instanceof \CurlHandle) {
            curl_close($this->ch);
        }

        $this->ch = null;
        $this->options = null;
    }

    /**
     * POST数据过滤处理
     * @param array $data 需要处理的数据
     * @return array|string
     */
    private function buildPostData($data)
    {
        if (!is_array($data)) return $data;

        return Tools::arr2json($data);
    }

    /**
     * Set Url
     *
     * @access public
     * @param  $url
     * @param  $mixed_data
     */
    public function setUrl($url, $mixed_data = '')
    {
        $this->url = $this->buildUrl($url, $mixed_data);
        $this->setOpt(CURLOPT_URL, $this->url);

        return $this;
    }

    /**
     * 获取已经设置的 Opt 的值
     *
     * @access public
     * @param  $option
     *
     * @return mixed
     */
    public function getOpt($option)
    {
        return isset($this->options[$option]) ? $this->options[$option] : null;
    }

    /**
     * Set Opt
     *
     * @access public
     * @param  $option
     * @param  $value
     *
     * @return boolean
     */
    public function setOpt($option, $value)
    {
        // 固定选项值
        $required_options = [
            CURLOPT_RETURNTRANSFER => 'CURLOPT_RETURNTRANSFER', // 将curl_exec()获取的信息以文件流的形式返回，而不是直接输出
        ];

        if (in_array($option, array_keys($required_options), true) && $value !== true) {
            trigger_error($required_options[$option] . ' is a required option', E_USER_WARNING);
        }

        $success = curl_setopt($this->ch, $option, $value);
        if ($success) {
            $this->options[$option] = $value;
        }
        return $success;
    }

    /**
     * Set Opts
     *
     * @access public
     * @param  $options
     *
     * @return boolean
     *   Returns true if all options were successfully set. If an option could not be successfully set, false is
     *   immediately returned, ignoring any future options in the options array. Similar to curl_setopt_array().
     */
    public function setOpts($options)
    {
        foreach ($options as $option => $value) {
            if (!$this->setOpt($option, $value)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Set Header
     *
     * @access public
     * @param  $key
     * @param  $value
     */
    public function setHeader($key, $value)
    {
        $this->headers[$key] = $value;
        $headers = [];
        foreach ($this->headers as $key => $value) {
            $headers[] = $key . ': ' . $value;
        }
        $this->setOpt(CURLOPT_HTTPHEADER, $headers);
    }

    /**
     * Set Headers
     *
     * @param string[] $headers
     */
    public function setHeaders($headers)
    {
        foreach ($headers as $header) {
            list($key, $value) = explode(':', $header, 2);
            $key = trim($key);
            $value = trim($value);
            $this->headers[$key] = $value;
        }

        $headers = [];
        foreach ($this->headers as $key => $value) {
            $headers[] = $key . ': ' . $value;
        }

        $this->setOpt(CURLOPT_HTTPHEADER, $headers);
    }

    /**
     * 从请求中删除内部标头
     * Using `curl -H "Host:" ...' is equivalent to $ch->removeHeader('Host');.
     *
     * @access public
     * @param  $key
     */
    public function removeHeader($key)
    {
        $this->setHeader($key, '');
    }

    /**
     * 设置证书文件
     *
     * @param [type] $ssl_key
     * @return void
     */
    public function setSslKey($ssl_key)
    {
        if (!file_exists($ssl_key)) {
            throw new InvalidArgumentException("Certificate files that do not exist. --- [ssl_key]");
        }

        curl_setopt($this->ch, CURLOPT_SSLKEYTYPE, 'PEM');
        curl_setopt($this->ch, CURLOPT_SSLKEY, $ssl_key);

        return $this;
    }

    /**
     * 设置证书文件
     *
     * @param [type] $ssl_cer
     * @return void
     */
    public function setSslCer($ssl_cer)
    {
        if (!file_exists($ssl_cer)) {
            throw new InvalidArgumentException("Certificate files that do not exist. --- [ssl_cer]");
        }

        curl_setopt($this->ch, CURLOPT_SSLCERTTYPE, 'PEM');
        curl_setopt($this->ch, CURLOPT_SSLCERT, $ssl_cer);

        return $this;
    }



    /**
     * Build Url
     *
     * @access public
     * @param  string $url
     * @param  string|array $mixed_data
     *
     * @return string
     */
    public function buildUrl($url, $mixed_data = '')
    {
        $query_string = '';
        if (!empty($mixed_data)) {
            $query_mark = strpos($url, '?') > 0 ? '&' : '?';
            if (is_string($mixed_data)) {
                $query_string .= $query_mark . $mixed_data;
            } elseif (is_array($mixed_data)) {
                $query_string .= $query_mark . http_build_query($mixed_data, '', '&');
            }
        }
        return $url . $query_string;
    }

    public function getId()
    {
        return $this->id;
    }
}
