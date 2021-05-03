<?php

namespace Songyz\Rabbit\Service;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use phpDocumentor\Reflection\Types\Boolean;
use Songyz\Rabbit\Exchange\Exchange;
use Songyz\Rabbit\Message\Message;
use Psr\Log\LoggerInterface;

/**
 * rabbit
 * Class Rabbit
 * @package Songyz\Rabbit\Service
 * @author songyongzhan <574482856@qq.com>
 * @date 2021/5/2 14:46
 */
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
     * 发送单条消息
     * publish
     *
     * @param Message $message
     * @param Exchange $exchange
     * @param string|null $routingKey
     * @param bool $confirm 是否开启确认模式
     * @param bool $asynchronous 是否异步确认  如果是false 则 同步等待  true为异步
     * @return Rabbit
     */
    public function publish(
        Message $message,
        Exchange $exchange,
        string $routingKey = '',
        bool $confirm = false,
        bool $asynchronous = false
    ): Rabbit {

        // if ($confirm) {
        //     //开启确认模式 确保消息发送到交换机
        //     $this->channel->confirm_select();
        //     if ($asynchronous) {
        //         $this->channel->set_ack_handler(function (AMQPMessage $message) {
        //             echo 'ack' . $message->getBody() . PHP_EOL;
        //             //如果发送到交换机 记录一个日志
        //         });
        //         $this->channel->set_nack_handler(function (AMQPMessage $message) {
        //             echo 'nack' . $message->getBody() . PHP_EOL;
        //             //如果没有发送到交换机 则记录日志
        //         });
        //     }
        // }
        $this->channel->basic_publish($message->getAMQPMessage(), $exchange->getName(), $routingKey);
        //如果开启了确认模式，且非异步确认 则等待
        // if ($confirm && !$asynchronous) {
        //     $this->channel->wait_for_pending_acks_returns(5);//set wait time
        // }

        return $this;
    }


    /**
     * 设置消费者关注的消息标签，
     * consume
     *
     * @param string $queue
     * @param callable|null $callback
     * @return Rabbit
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
            throw new RabbitException('未声明队列和消费处理方法');
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
