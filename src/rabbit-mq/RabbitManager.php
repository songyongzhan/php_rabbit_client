<?php

namespace Songyz\Rabbit;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Songyz\Rabbit\Conf\Config;
use Songyz\Rabbit\Service\Rabbit;

final class RabbitManager
{

    /** @var RabbitManager */
    private static $instance;

    /** @var AMQPStreamConnection */
    private $connection;

    /** @var LoggerInterface 日志 */
    private $logger;


    /**
     * 实例化connection实例
     * RabbitManager constructor.
     *
     * @param Config $config
     * @param LoggerInterface|null $logger
     * @throws \Exception
     */
    private function __construct(Config $config, LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();

        $this->connection = AMQPStreamConnection::create_connection($config->getHosts(), $config->getOptions());
    }


    /**
     * 析构函数 释放掉所有的资源
     * @throws \Exception
     */
    public final function __destruct()
    {
        if (!is_null($this->connection)) {
            $this->connection->close();
            $this->connection = null;
        }
    }

    /**
     * 禁止克隆
     * __clone
     *
     * @author songyongzhan <574482856@qq.com>
     * @date 2021/5/2 11:32
     */
    private function __clone()
    {
    }

    /**
     * 获取channel
     * getChannel
     * @return \PhpAmqpLib\Channel\AMQPChannel
     *
     * @author songyongzhan <574482856@qq.com>
     * @date 2022/1/13 19:50
     */
    public function getChannel()
    {
        return $this->connection->channel();
    }

    /**
     * 获取连接
     * getConnection
     * @return mixed|AMQPStreamConnection
     *
     * @author songyongzhan <574482856@qq.com>
     * @date 2022/1/13 19:49
     */
    public function getConnection()
    {
        return $this->connection;
    }


    /**
     * rabbit
     * 获取mq实例
     */
    public function rabbit(): Rabbit
    {
        return new Rabbit($this->connection->channel(), $this->logger);
    }

    /**
     * 获取RabbitManger对象 单例模式
     * getInstance
     * @param Config|null $config
     * @param LoggerInterface|null $logger
     * @return RabbitManager
     * @throws \Exception
     *
     * @author songyongzhan <574482856@qq.com>
     * @date 2021/5/3 09:45
     */
    public static function getInstance(Config $config = null, LoggerInterface $logger = null)
    {
        if (self::$instance === null || !self::$instance instanceof RabbitManager) {
            self::$instance = new RabbitManager($config, $logger);
        }
        return self::$instance;
    }


}
