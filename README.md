# php_rabbit_client

## 使用说明



## 使用小提示

* 所有的队列都应该设置一个过期时间
* 所有的队列设置时候，都应设置死信队列，将死信队列设置消费任务，持久化数据



## 使用说明

### 使用前的配置

#### 定义服务提供者
```php
<?php
namespace App\Providers;

use App\Services\RabbitConfig;
use Illuminate\Support\ServiceProvider;
use Songyz\Rabbit\RabbitManager;

class RabbitMqProvider extends ServiceProvider
{
    public function boot()
    {
        $this->app->singleton(RabbitManager::class, function () {
            return RabbitManager::getInstance(new RabbitConfig(), app('log'));
        });
    }

    /**
     * 获取提供者提供的服务
     *
     * @return array
     */
    public function provides()
    {
        return [RabbitManager::class];
    }
}
```
在 `config/app.php` 中，将 `App\Providers\RabbitMqProvider::class`
 添加进来,这里面缺少一个`rabbitConfig`配置,如下：
 
#### rabbitConfig配置
```php
<?php

namespace App\Services;

use Songyz\Rabbit\Conf\Config;

class RabbitConfig extends Config
{

    public function __construct()
    {
        $this->hosts = $this->initEnvironment();
    }

    /**
     * 根据环境变量 设置不同的配置
     * 此配置本身需要放在env中去维护，但考虑到env不支持数组，需要特定的格式去配置 较为繁琐
     * 因此放在这里去做配置
     * initEnvironment
     * @return array|array[]
     *
     */
    private function initEnvironment()
    {
        $environment = app()->environment();

        switch ($environment) {
            case "development":
                return [
                    [
                        'host'     => "192.168.1.196",
                        'port'     => 5672,
                        'user'     => 'guest',
                        'password' => 'guest',
                        'vhost'    => '/'
                    ],
                ];
                break;
            case "test":
                return [];
                break;
            case "pre-production":
                return [
                    [
                        'host'     => "192.168.1.198",
                        'port'     => 5672,
                        'user'     => 'guest',
                        'password' => 'guest',
                        'vhost'    => '/'
                    ],
                ];
                break;
            case "production":
                return [
                    [
                        'host'     => "192.168.1.199",
                        'port'     => 5672,
                        'user'     => 'guest',
                        'password' => 'guest',
                        'vhost'    => '/'
                    ],
                ];
                break;
            default:
                return [];
                break;
        }
    }
}
```
`initEnvironment` 根据实际环境改写。

### 生产者示例

#### 交换机定义

```php
<?php

namespace App\Services;

use Songyz\Rabbit\Exchange\Exchange;

class PhpFirstExchange extends Exchange
{
    /** @var string 交换机名称 */
    protected $name = "php-f-exchange";
    /** @var string
     * 交换机路由key  可以为空 直连模式
     * 交换机路由key topic 及 fanout 可指定对对应的key
     */
    protected $routingKey = "";

}
```
>  交换机定义 一定要继承 Exchange

#### 1. 单条生产者-无确认

```php
$rabbitManager = app(RabbitManager::class);
$rabbit = $rabbitManager->rabbit();
$exchange = new PhpFirstExchange();
$message = new Message("test-php 2021-05-03 14:19:03");
$rabbit1 = $rabbit->publish($message, $exchange, '');
```

#### 2. 单条生产者-确认消息
```php
$rabbitManager = app(RabbitManager::class);
$rabbit = $rabbitManager->rabbit();
$exchange = new PhpFirstExchange();
 
$message = new Message("test-php 2021-05-03 14:19:03");
$rabbit1 = $rabbit->publish($message, $exchange, '', true);
```

#### 3. 批量发送消息-无确认

```php
<?php

namespace App\Services;

use Songyz\Rabbit\Message\Message;
use Songyz\Rabbit\RabbitManager;

class MqService
{
    public function mq()
    {
        $rabbitManager = app(RabbitManager::class);
        $rabbit = $rabbitManager->rabbit();
        $exchange = new PhpFirstExchange();

        $result = [];
        for ($i = 0; $i <= 30; $i++) {
            array_push($result, new Message(json_encode(['content' => "test-php" . microtime(true)])));
        }

        $rabbit->publishMulti($result, $exchange, '');
    }

}
```

#### 3. 批量发送消息-确认

```php
<?php

namespace App\Services;

use Songyz\Rabbit\Message\Message;
use Songyz\Rabbit\RabbitManager;

class MqService
{
    public function mq()
    {
        $rabbitManager = app(RabbitManager::class);
        $rabbit = $rabbitManager->rabbit();
        $exchange = new PhpFirstExchange();

        $result = [];
        for ($i = 0; $i <= 30; $i++) {
            array_push($result, new Message(json_encode(['content' => "test-php" . microtime(true)])));
        }

        $rabbit->publishMulti($result, $exchange, '', true);
    }
}
```



### 消费者

消费者只提供一种模式，每次发送 `1` 条消息，消费完毕，并重试`3`次, 这两个参数都是包里定制好的，不能修改。

消费者带有消息确认，`ack` 或 `nack`。

```php
<?php

namespace App\Console\Commands;

use App\Models\HealthRecord;
use Songyz\Rabbit\Command\RabbitCommand;
use Songyz\Rabbit\Message\ResponseMessage;

class MqConsumer extends RabbitCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mq:xf';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '脚本消费';
    
    /** @var string 要消费的队列 */
    protected $queue = 'php-f-queue3';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        parent::handle();
        return 0;
    }

    public function consume(ResponseMessage $message): bool
    {
        $this->line("消费者标签：{$message->getDeliveryTag()}  |  信息：" . $message->getBody());

        $body = json_decode($message->getBody(), true);
        // if (! empty($body) && is_array($body)) {
            return false;
        // }
        //处理你的业务逻辑
        
        //如果成功 则返回 true  失败就返回 false 默认会重试三次
        
        return true;
    }
}
```

`$queue` 这个属性一定要定义，这是消费的队列

启动脚本 `php artisan mq:xf`

## 优点

1. 简化MQ的实例化过程
2. 简化消费者 消息确认
3. 简化生产者-发布消息，带有消息确认

## 不足
1. 消息确认需要同步等待

