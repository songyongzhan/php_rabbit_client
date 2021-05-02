<?php

namespace Songyz\Rabbit\Facade;

interface IExchange
{

    /**
     * 获取交换机名称
     * getName
     *
     * @return string
     */
    public function getName(): string;


    /**
     * 获取交换机里面可用的路由key
     * isAvailableRoutingKey
     *
     * @param string $routingKey
     * @return void
     */
    public function isAvailableRoutingKey(string $routingKey): void;


    /**
     * 获取消息路由
     * getRoutingKeys
     *
     * @return array
     */
    public function getRoutingKeys();


}
