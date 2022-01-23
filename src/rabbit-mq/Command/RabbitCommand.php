<?php

namespace Songyz\Rabbit\Command;

use Exception;
use Illuminate\Console\Command;
use Songyz\Rabbit\Message\ResponseMessage;
use Songyz\Rabbit\RabbitManager;
use Songyz\Rabbit\Service\Rabbit;

abstract class RabbitCommand extends Command
{

    /** @var string 队列名称 */
    protected $queue;

    /** @var Rabbit */
    protected $rabbit;

    /**
     * AbsConsumerCommand constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct();
    }


    /**
     * 准备rabbit信息
     * prepareRabbit
     *
     * @throws Exception
     *
     */
    protected function prepareRabbit()
    {
        /** @var RabbitManager $rabbitManager */
        $rabbitManager = app(RabbitManager::class);

        $this->rabbit = $rabbitManager->rabbit();
    }

    /**
     * 使用说明
     * handle
     * @throws Exception
     *
     */
    public function handle()
    {
        $this->prepareRabbit();

        $this->rabbit->consume($this->queue, [$this, 'consume']);

        $this->start();
    }

    /**
     * 启动消费
     * start
     *
     *
     */
    protected function start()
    {
        $date = now()->format('Y-m-d H:i:s.u');
        $this->line("[$date] 队列：【{$this->queue}】 启动");
        $this->rabbit->start();
    }

    /**
     * consume
     *
     * @param ResponseMessage $message
     * @return bool
     */
    abstract public function consume(ResponseMessage $message): bool;

}
