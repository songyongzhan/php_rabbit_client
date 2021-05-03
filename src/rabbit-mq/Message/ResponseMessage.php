<?php

namespace Songyz\Rabbit\Message;

use OutOfBoundsException;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

class ResponseMessage
{

    /** @var AMQPMessage */
    private $AMQPMessage;

    /** @var string 队列名称 */
    private $queue;

    /** @var int 重试次数 默认为三次 */
    private $retry;

    /**
     * ResponseMessage constructor.
     *
     * @param AMQPMessage $AMQPMessage
     * @param string $queue
     * @param int $retry
     */
    public function __construct(AMQPMessage $AMQPMessage, string $queue, int $retry = 3)
    {
        $this->AMQPMessage = $AMQPMessage;
        $this->queue = $queue;
        $this->retry = $retry;
    }

    /**
     * 消息内容类型
     * getContentType
     *
     * @return string
     *
     */
    public function getContentType(): string
    {
        try {
            return $this->AMQPMessage->get('content-type');
        } catch (\Exception $exception) {
            return '';
        }
    }

    /**
     * 消息大小
     * getBodySize
     *
     * @return int
     *
     */
    public function getBodySize(): int
    {
        return $this->AMQPMessage->getBodySize();
    }


    /**
     * 消息体
     * getBody
     *
     * @return string
     *
     */
    public function getBody(): string
    {
        return $this->AMQPMessage->getBody();
    }


    /**
     *  获取消息原始类
     * getAMQP
     * @return AMQPMessage
     *
     */
    public function getAMQP()
    {
        return $this->AMQPMessage;
    }

    /**
     * 消息持久化模式
     * getDeliveryMode
     *
     * @return int
     *
     */
    public function getDeliveryMode(): int
    {
        try {
            return $this->AMQPMessage->get('delivery_mode');
        } catch (OutOfBoundsException $exception) {
            return 2;
        }
    }


    /**
     * 是否重复投递
     * getReplyTo
     *
     * @return string
     */
    public function getReplyTo(): string
    {
        try {
            return $this->AMQPMessage->get('reply_to');
        } catch (OutOfBoundsException $exception) {
            return '';
        }
    }

    /**
     * 消息ID
     * getMessageId
     *
     * @return string
     */
    public function getMessageId(): string
    {
        try {
            return $this->AMQPMessage->get('message_id');
        } catch (OutOfBoundsException $exception) {
            return 0;
        }
    }

    /**
     * 消息类型
     * getType
     *
     * @return string
     *
     */
    public function getType(): string
    {
        try {
            return $this->AMQPMessage->get('type');
        } catch (OutOfBoundsException $exception) {
            return '';
        }
    }

    /**
     * 消息发送时的时间戳
     * getTimestamp
     *
     * @return int
     *
     */
    public function getTimestamp(): int
    {
        try {
            return $this->AMQPMessage->get('timestamp');
        } catch (OutOfBoundsException $exception) {
            return 0;
        }
    }


    /**
     * 消息头
     * getHeader
     *
     * @return AMQPTable|null
     *
     */
    public function getHeader()
    {
        try {
            return $this->AMQPMessage->get('application_headers');
        } catch (OutOfBoundsException $exception) {
            return null;
        }
    }

    /**
     * 消费发送的应用标识
     * getAppId
     *
     * @return string
     *
     */
    public function getAppId(): string
    {
        try {
            return $this->AMQPMessage->get('app_id');
        } catch (OutOfBoundsException $exception) {
            return '';
        }
    }


    /**
     * 获取消息投递标识
     * getDeliveryTag
     * @return int
     */
    public function getDeliveryTag(): int
    {
        return $this->AMQPMessage->getDeliveryTag();
    }

    /**
     * 消息的路由key
     * getRoutingKey
     *
     * @return string
     */
    public function getRoutingKey(): string
    {
        return $this->AMQPMessage->delivery_info['routing_key'];
    }

    /**
     * @return string
     *
     */
    public function getQueue(): string
    {
        return $this->queue;
    }

    /**
     * 减少消息重试次数
     * releaseTry
     */
    public function releaseTry()
    {
        if ($this->retry >= 0) {
            $this->retry--;
        }
    }

    /**
     * 是否可重试
     * isRetry
     *
     * @return bool
     *
     */
    public function isRetry(): bool
    {
        return $this->retry > 0;
    }

    /**
     * 是否重复消费
     * isRedelivered
     *
     * @return bool
     *
     */
    public function isRedelivered(): bool
    {
        return boolval($this->AMQPMessage->delivery_info['redelivered']);
    }

    /**
     * 获取消息投递的通道
     * getChannel
     *
     * @return AMQPChannel
     */
    public function getChannel() : AMQPChannel
    {
        return $this->AMQPMessage->getChannel();
        //return $this->AMQPMessage->delivery_info['channel'];
    }

    /**
     * 消息消费失败
     * noAck
     */
    public function noAck()
    {
        $this->getChannel()->basic_nack($this->getDeliveryTag());
    }

    /**
     * 消息消费成功
     * ack
     */
    public function ack()
    {
        $this->getChannel()
            ->basic_ack($this->getDeliveryTag());
    }


}
