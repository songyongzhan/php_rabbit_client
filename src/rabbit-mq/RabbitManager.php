<?php

namespace Songyz\Rabbit;

use Bhc\Library\Support\System\Env;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Rabbit\Conf\BAProduction;
use Rabbit\Conf\Development;
use Rabbit\Conf\PreProduction;
use Rabbit\Conf\Test;
use Rabbit\Conf\TXYProduction;
use Rabbit\Conf\XYProduction;
use Rabbit\Contracts\IConf;
use Rabbit\Exception\ConfigException;
use Songyz\Rabbit\Conf\Config;
use Songyz\Rabbit\Service\Rabbit;

final class RabbitManager
{


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
     * @throws ConfigException
     * @throws Exception
     */
    private function __construct(Config $config = null, LoggerInterface $logger = null)
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
     * 获取RabbitManger对象 单例模式
     * instance
     *
     * @param IConf $config
     * @param LoggerInterface $logger
     * @return RabbitManager
     * @throws Exception
     */
    public static function getInstance(IConf $config = null, LoggerInterface $logger = null)
    {
        if (self::$instance === null || !self::$instance instanceof RabbitManager) {
            self::$instance = new RabbitManager($config, $logger);
        }
        return self::$instance;
    }


    /**
     * rabbit
     * 获取mq实例
     */
    public function rabbit(): Rabbit
    {
        return new Rabbit($this->connection->channel(), $this->logger);
    }


}
