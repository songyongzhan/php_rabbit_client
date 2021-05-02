<?php

namespace Songyz\Rabbit\Message;

use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use Songyz\Common\Library\Snowflake;
use Songyz\Rabbit\ApplicationCode;
use Songyz\Rabbit\Exception\RabbitException;

class Message
{

    /** @var AMQPMessage */
    private $AMQPMessage;

    /**
     * Message constructor.
     *
     * @param string $body
     * @param string $appId
     * @throws RabbitException
     */
    public function __construct(string $body = '', string $appId = '')
    {
        if (empty($body)) {
            throw new RabbitException(ApplicationCode::MESSAGE_CONTENT_EMPTY_NOTICE_CONTENT,
                ApplicationCode::MESSAGE_CONTENT_EMPTY);
        }
        $this->AMQPMessage = new AMQPMessage($body);
        //这里的属性和 AMQPMessage 中的属性一一对应，请不要尝试修改
        $this->AMQPMessage->set('delivery_mode', AMQPMessage::DELIVERY_MODE_PERSISTENT);
        $this->AMQPMessage->set('message_id', Snowflake::generateId());
        $this->AMQPMessage->set('timestamp', time());
        $this->AMQPMessage->set('app_id', $appId);
    }

    /**
     * setContentType
     *
     * @param string $contentType
     *
     */
    public function setContentType(string $contentType)
    {
        $this->AMQPMessage->set('content_type', $contentType);
    }

    /**
     * setContentEncoding
     *
     * @param string $contentEncoding
     *
     */
    public function setContentEncoding(string $contentEncoding)
    {
        $this->AMQPMessage->set('content_encoding', $contentEncoding);
    }

    /**
     * setApplicationHeaders
     *
     * @param AMQPTable $table
     *
     */
    public function setApplicationHeaders(AMQPTable $table)
    {
        $this->AMQPMessage->set('application_headers', $table);
    }

    /**
     * setType
     *
     * @param string $type
     *
     */
    public function setType(string $type)
    {
        $this->AMQPMessage->set('type', $type);
    }

    /**
     * @return AMQPMessage
     */
    public function getAMQPMessage(): AMQPMessage
    {
        return $this->AMQPMessage;
    }
}
