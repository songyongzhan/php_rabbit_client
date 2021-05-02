<?php

namespace Songyz\Rabbit\Exchange;

/**
 * 抽象交换机
 * 使用的时候，请继承，并将 name 和 routingKey 进行设置
 * Class Exchange
 * @package Songyz\Rabbit\Exchange
 * @author songyongzhan <574482856@qq.com>
 * @date 2021/5/2 11:05
 */
abstract class Exchange
{
    /** @var string 交换机名称 */
    protected $name;
    /** @var string
     * 交换机路由key  可以为空 直连模式
     * 交换机路由key topic 及 fanout 可指定对对应的key
     */
    protected $routingKey;

    /**
     * getRoutingKeys
     * 获取消息路由
     *
     * @return String
     */
    public function getRoutingKey()
    {
        return $this->routingKey;
    }

    /**
     * getName
     * 交换机名称
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
}
