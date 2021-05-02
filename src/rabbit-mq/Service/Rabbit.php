<?php

namespace Songyz\Rabbit\Service;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use Songyz\Rabbit\Facade\IExchange;
use Songyz\Rabbit\Message\Message;

class Rabbit
{
    /** @var LoggerInterface */
    protected $logger;

    /** @var AMQPChannel */
    protected $channel;

    /** @var array 消费者标识 */
    protected $consumerTag;

    /** @var IExchange[] 业务交换 */
    protected $exchanges;

    /** @var array 队列和回调映射关系 */
    protected $queue2Callback;

    /**
     * Rabbit constructor.
     *
     * @param AMQPChannel $channel
     * @param LoggerInterface|null $logger
     */
    public function __construct(AMQPChannel $channel, LoggerInterface $logger = null)
    {
        $this->channel = $channel;
        $this->logger = $logger ?? new NullLogger();
        $this->exchanges = [];
        //通过限流，每一次到达消费者的信息只有一条
        $this->channel->basic_qos(null, 1, null);
    }


    /**
     * 发送单挑消息
     * publish
     *
     * @param Message $message
     * @param IExchange $exchange
     * @param string|null $routingKey
     * @return IRabbit
     * @throws ExchangeException
     */
    public function publish(Message $message, IExchange $exchange, string $routingKey = null): IRabbit
    {

        if ($routingKey == null || empty($routingKey)) { //如果消息路由标识为空，将消息分发到所有关注的路由key
            foreach ($exchange->getRoutingKeys() as $routingKey) {
                $this->channel->basic_publish($message->getAMQPMessage(), $exchange->getName(), $routingKey);
            }
        } else {
            $exchange->isAvailableRoutingKey($routingKey);
            $this->channel->basic_publish($message->getAMQPMessage(), $exchange->getName(), $routingKey);
        }
        return $this;
    }


    /**
     * 设置消费者关注的消息标签，
     * consume
     *
     * @param string $queue
     * @param callable|null $callback
     * @return Rabbit
     *
     * @author zhanglei <zhanglei8@guahao.com>
     * @date   2020-07-22 12:57:47
     */
    public function consume(string $queue, callable $callback = null): Rabbit
    {

        if ($callback != null && is_callable($callback)) {
            list($queue,) = $this->channel->queue_declare($queue, true, true, false, false, false);
            $this->queue2Callback[$queue] = $callback;
        }

        return $this;
    }

    /**
     * 消费回调处理  将消息类型转换
     * callbackHandle
     *
     * @param callable $callback
     * @param string $queue
     * @return callable
     *
     * @author zhanglei <zhanglei8@guahao.com>
     * @date   2020-07-25 15:45:07
     */
    protected function callbackHandle(callable $callback, string $queue): callable
    {
        return function (AMQPMessage $message) use ($callback, $queue) {
            $back = function (callable $callback, ResponseMessage $message, callable $back) {
                if ($message->isReTry()) { //校验消息是否可重试
                    $message->releaseTry(); //释放一次消息可重试次数
                    $result = call_user_func($callback, $message);
                    if ($result === true) { //处理成功  提交消息ack
                        $message->ack();
                    } else {
                        if ($message->isRetry()) { // 重试三次
                            $back($callback, $message, $back); //递归调用 消费者业务处理回调
                        } else {
                            $message->noAck();
                        }
                    }
                }
            };

            $responseMessage = new ResponseMessage($message, $queue);
            try {
                $back($callback, $responseMessage, $back);
            } catch (RabbitException $exception) {
                $this->logger->info("| 通道 : {$this->channel->getChannelId()} " . "| 队列 : {$queue} | 消息ID : {$responseMessage->getMessageId()}",
                    [
                        'message' => "",
                        'code'    => $exception->getCode(),
                        'file'    => "{$exception->getFile()}  {$exception->getLine()}"
                    ]);
                $responseMessage->noAck();
            }
        };
    }

    /**
     * 开始消费
     * start
     *
     * @return void
     * @throws Exception|\ErrorException
     *
     */
    public function start()
    {
        if (empty($this->queue2Callback)) {
            throw new RabbitException('未什么声明队列和消费处理方法');
        }
        foreach ($this->queue2Callback as $queue => $callback) {
            $consumerTag = $this->channel->basic_consume(
                $queue,
                '',
                false,
                false,
                false,
                false,
                $this->callbackHandle($callback, $queue)
            );
            if (is_string($consumerTag) && mb_strlen($consumerTag) > 0) {
                $this->consumerTag[] = $consumerTag; //保存消费者队列
            }
        }

        while ($this->channel->is_consuming()) {
            $this->channel->wait();
        }
    }

    /**
     * stop
     * 停止消费
     *
     * @return void
     */
    public function stop()
    {
        if ($this->consumerTag) {
            foreach ($this->consumerTag as $consumerTag) {
                //断开连接
                $this->channel->basic_cancel($consumerTag);
            }
            $this->consumerTag = [];
        }
    }

    /**
     * 析构方法
     *
     */
    public function __destruct()
    {
        $this->stop();
        if ($this->channel !== null) {
            $this->channel->close();
        }
    }
}
