<?php
namespace Songyz\Rabbit\Command;

use Exception;
use Illuminate\Console\Command;
use Rabbit\Message\ResponseMessage;
use Rabbit\RabbitManager;

abstract class RabbitCommand extends Command
{

    /** @var string 队列名称 */
    protected $queue;

    /** @var IRabbit */
    protected $rabbit;

    /**
     * AbsConsumerCommand constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct();

        $this->prepareRabbit();
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
        $this->rabbit  = $rabbitManager->rabbit();

        $this->rabbit->consume($this->queue, [$this , 'consume']);
    }

    public function handle()
    {
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
     *
     */
    abstract public function consume(ResponseMessage $message) : bool;

}