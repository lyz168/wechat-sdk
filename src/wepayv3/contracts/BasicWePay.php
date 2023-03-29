<?php

namespace lyz\wepayv3\contracts;

use lyz\wepayv3\exceptions\InvalidArgumentException;
use lyz\wepayv3\exceptions\InvalidResponseException;
use lyz\wepayv3\Cert;

/**
 * 微信支付基础类
 * Class BasicWePay
 * @package lyz\wepayv3\contracts
 */
abstract class BasicWePay
{
    /**
     * 接口基础地址
     * @var string
     */
    protected $base = 'https://api.mch.weixin.qq.com';

    /**
     * 实例对象静态缓存
     * @var array
     */
    static $instances = [];

    /**
     * 配置参数
     * @var array
     */
    protected $config = [
        'appid'        => '', // 微信绑定APPID，需要配置
        'mch_id'       => '', // 微信商户编号，需要配置
        'mch_v3_key'   => '', // 微信商户密钥，需要配置
        'cert_public'  => '', // 商户公钥内容，需要配置
        'cert_private' => '', // 商户密钥内容，需要配置
        'cert_serial'  => '', // 商户证书序号，无需配置
    ];

    /**
     * BasicWePayV3 constructor.
     * @param array $options [mch_id, mch_v3_key, cert_public, cert_private] (cert_public, cert_private 文件路径或内容)
     */
    public function __construct(array $options = [])
    {
        if (empty($options['mch_id'])) {
            throw new InvalidArgumentException("Missing Config -- [mch_id]");
        }
        if (empty($options['mch_v3_key'])) {
            throw new InvalidArgumentException("Missing Config -- [mch_v3_key]");
        }
        if (empty($options['cert_private'])) {
            throw new InvalidArgumentException("Missing Config -- [cert_private]");
        }
        if (empty($options['cert_public'])) {
            throw new InvalidArgumentException("Missing Config -- [cert_public]");
        }

        if (stripos($options['cert_public'], '-----BEGIN CERTIFICATE-----') === false) {
            if (file_exists($options['cert_public'])) {
                $options['cert_public'] = file_get_contents($options['cert_public']);
            } else {
                throw new InvalidArgumentException("File Non-Existent -- [cert_public]");
            }
        }

        if (stripos($options['cert_private'], '-----BEGIN PRIVATE KEY-----') === false) {
            if (file_exists($options['cert_private'])) {
                $options['cert_private'] = file_get_contents($options['cert_private']);
            } else {
                throw new InvalidArgumentException("File Non-Existent -- [cert_private]");
            }
        }

        $this->config['appid'] = isset($options['appid']) ? $options['appid'] : '';
        $this->config['mch_id'] = $options['mch_id'];
        $this->config['mch_v3_key'] = $options['mch_v3_key'];
        $this->config['cert_public'] = $options['cert_public'];
        $this->config['cert_private'] = $options['cert_private'];
        $this->config['cert_serial'] = openssl_x509_parse($this->config['cert_public'])['serialNumberHex'];

        if (empty($this->config['cert_serial'])) {
            throw new InvalidArgumentException("Failed to parse certificate public key");
        }
    }

    /**
     * 静态创建对象
     * @param array $config
     * @return static
     */
    public static function instance($config)
    {
        $key = md5(get_called_class() . serialize($config));
        if (isset(self::$instances[$key])) return self::$instances[$key];
        return self::$instances[$key] = new static($config);
    }

    /**
     * 模拟发起请求
     * @param string $method   请求访问
     * @param string $pathinfo 请求路由
     * @param string|array $jsondata 请求数据 json_encode($data, JSON_UNESCAPED_UNICODE)
     * @param bool $verify 是否验证
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     */
    public function doRequest($method, $pathinfo, $jsondata = '', $verify = false)
    {
        if (is_array($jsondata)) {
            $jsondata = json_encode($jsondata, JSON_UNESCAPED_UNICODE);
        }

        // $url_parts = parse_url($pathinfo);
        // $pathinfo = ($url_parts['path'] . (!empty($url_parts['query']) ? "?${url_parts['query']}" : ""));

        list($time, $nonce) = [time(), uniqid() . rand(1000, 9999)];
        $signstr = join("\n", [$method, $pathinfo, $time, $nonce, $jsondata, '']);
        // 生成数据签名TOKEN
        $token = sprintf(
            'mchid="%s",nonce_str="%s",timestamp="%d",serial_no="%s",signature="%s"',
            $this->config['mch_id'],
            $nonce,
            $time,
            $this->config['cert_serial'],
            $this->signBuild($signstr)
        );

        $arr_url = parse_url($_SERVER['HTTP_HOST']);
        $ua = empty($arr_url['path']) ? 'https://github.com/lyz168/wechat-sdk' : $arr_url['path'];

        list($header, $content) = $this->_doRequestCurl($method, $this->base . $pathinfo, [
            'data' => $jsondata,
            // v3 必须设置 Accept, Content-Type 为 application/json  图片上传API除外
            // User-Agent: 1.使用HTTP客户端默认的 User-Agent 2.遵循HTTP协议，使用自身系统和应用的名称和版本等信息，组成自己独有的User-Agent
            'header' => [
                "Accept: application/json",
                "Content-Type: application/json",
                "Authorization: WECHATPAY2-SHA256-RSA2048 {$token}", // Authorization: 认证类型 签名信息
                'User-Agent: ' . $ua,
                // "Accept-Language: en", // 应答的语种: en,zh-CN,zh-HK,zh-TW
            ],
        ]);

        if ($verify) {
            $headers = [
                // 'timestamp' => '', // Wechatpay-Timestamp 应答时间戳
                // 'nonce' => '',     // Wechatpay-Nonce     应答随机串
                // 'signature' => '', // Wechatpay-Signature 应答签名
                // 'serial' => '',    // Wechatpay-Serial    平台证书序列号
            ];
            foreach (explode("\n", $header) as $line) {
                if (stripos($line, 'Wechatpay') !== false) {
                    list($name, $value) = explode(':', $line);
                    list(, $keys) = explode('wechatpay-', strtolower($name));
                    $headers[$keys] = trim($value);
                }
            }
            try {
                // 顺序不能乱
                $string = join("\n", [$headers['timestamp'], $headers['nonce'], $content, '']);
                if (!$this->signVerify($string, $headers['signature'], $headers['serial'])) {
                    throw new InvalidResponseException("验证响应签名失败");
                }
            } catch (\Exception $exception) {
                throw new InvalidResponseException($exception->getMessage(), $exception->getCode());
            }
        }
        return json_decode($content, true);
    }

    /**
     * 通过CURL模拟网络请求
     * @param string $method  请求方法
     * @param string $url     请求地址
     * @param array  $options 请求参数 [data, header]
     * @return array [header, content]
     */
    private function _doRequestCurl($method, $url, $options = [])
    {
        $curl = curl_init();
        // POST数据设置
        if (strtolower($method) === 'post') {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $options['data']);
        }
        // CURL头信息设置
        if (!empty($options['header'])) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $options['header']);
        }
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 60);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);

        $content = curl_exec($curl);
        $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);

        curl_close($curl);

        return [substr($content, 0, $headerSize), substr($content, $headerSize)];
    }

    /**
     * 生成数据签名
     * @param string $data 签名内容
     * @return string
     */
    protected function signBuild($data)
    {
        /*
        $data:(签名串一共有五行，每一行为一个参数。行尾以 \n（换行符，ASCII编码值为0x0A）结束，包括最后一行。如果参数本身以\n结束，也需要附加一个\n)
            请求方法\n     (GET,POST,PUT)
            URL\n          (获取请求的绝对URL，并去除域名部分得到参与签名的URL。如果请求中有查询参数，URL末尾应附加有'?'和对应的查询字符串。)
            请求时间戳\n   (格林威治时间起至现在的总秒数)
            请求随机串\n   (调用随机数函数生成，将得到的值转换为字符串)
            请求报文主体\n ( 请求方法为GET时，报文主体为空。
                            当请求方法为POST或PUT时，请使用真实发送的JSON报文。
                            图片上传API，请使用meta对应的JSON报文。)
        */
        // 使用商户私钥对待签名串进行 SHA256 with RSA 签名，并对签名结果进行Base64编码得到签名值
        $pkeyid = openssl_pkey_get_private($this->config['cert_private']);
        openssl_sign($data, $signature, $pkeyid, 'sha256WithRSAEncryption');
        return base64_encode($signature);
    }

    /**
     * 验证内容签名
     * @param string $data   签名内容
     * @param string $sign   原签名值
     * @param string $serial 证书序号
     * @return int
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    protected function signVerify($data, $sign, $serial = '')
    {
        $cert = $this->fileCache($serial);
        if (empty($cert)) {
            Cert::instance($this->config)->download();
            $cert = $this->fileCache($serial);
        }
        // $sign 字段值使用 Base64 进行解码，得到应答签名
        return @openssl_verify($data, base64_decode($sign), openssl_x509_read($cert), 'sha256WithRSAEncryption');
    }

    /**
     * 文件缓存(用于平台证书)
     * @param string $name
     * @param null|string $content
     * @return string
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    protected function fileCache($name, $content = null)
    {
        if (is_null($content)) {
            return base64_decode(Tools::getCache($name) ?: '');
        } else {
            return Tools::setCache($name, base64_encode($content), 7200);
        }
    }
}
