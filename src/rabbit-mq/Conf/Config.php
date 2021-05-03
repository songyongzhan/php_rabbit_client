<?php

namespace Songyz\Rabbit\Conf;

use Songyz\Common\Library\Env;
use Songyz\Rabbit\Exception\ConfigException;

abstract class Config
{
    /**
     * @var array Rabbit主机信息
     *  [
     *      ['host' => "xxx" , 'port' => 5672 , 'user' => 'user' , 'password' => 'x@12345' , 'vhost' => '/'] ,
     *      ['host' => "xxx" , 'port' => 5672 , 'user' => 'user' , 'password' => 'x@345677654' , 'vhost' => '/']
     * ]
     */
    protected $hosts = [];

    /** @var array 加密配置 */
    protected $ssl = [];

    /** @var bool 是否开启Rabbit Debug模式，开发环境和测试环境可以开启，线上禁止开启 默认为false */
    protected $debug = false;

    public function getHosts(): array
    {
        return $this->hosts;
    }

    public function isDebug(): bool
    {
        return $this->debug;
    }


    public function getSslProtocol(): array
    {
        return $this->ssl;
    }

    /**
     *
     * getOptions
     * @return array
     * @throws ConfigException
     *
     * @author songyongzhan <574482856@qq.com>
     * @date 2021/5/3 09:36
     */
    public function getOptions(): array
    {

        if ((Env::isDevelopment() || Env::isTest()) && $this->isDebug()) {
            define('AMQP_DEBUG', true);
        } else {
            define('AMQP_DEBUG', false);
        }

        //集群机器集合
        if (empty($this->getHosts())) {
            throw new ConfigException('缺少集群节点配置信息');
        }
        foreach ($this->getHosts() as &$host) {
            if (empty($host['host'])) {
                throw new ConfigException('缺少Rabbit节点的主机地址');
            }
            if (empty($host['user'])) {
                throw new ConfigException('缺少Rabbit节点的登录账户');
            }
            if (empty($host['password'])) {
                throw new ConfigException('缺少Rabbit节点的登录密码');
            }
            if (empty($host['port'])) {
                $host['port'] = 5672; //如果没有设置Rabbit节点端口号，设置默认端口号
            }
            if (empty($host['vhost'])) {
                throw new ConfigException('缺少Rabbit节点的虚拟主机');
            }
        }
        unset($host);

        //登录选项
        $options = [
            'insist'         => false,
            'login_method'   => 'AMQPLAIN',
            'login_response' => null,
            'locale'         => 'en_US',
            'read_timeout'   => 10,
            'keepalive'      => false,
            'write_timeout'  => 10,
            'heartbeat'      => 0,
        ];

        //证书验证
        if (!empty($this->getSslProtocol())) {
            if (empty($this->getSslProtocol()['cafile'])) {
                throw new ConfigException('缺少ca证书文件');
            }
            if (file_exists($this->getSslProtocol()['cafile'])) {
                throw new ConfigException('ca证书文件不存在');
            }
            if (empty($this->getSslProtocol()['local_cert'])) {
                throw new ConfigException('缺少本地证书文件');
            }
            if (file_exists($this->getSslProtocol()['local_cert'])) {
                throw new ConfigException('本地证书文件不存在');
            }
            $options['ssl_protocol'] = $this->getSslProtocol();
        }
        return $options;
    }
}
