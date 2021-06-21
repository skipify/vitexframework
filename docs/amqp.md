# Rabbitmq

## 简单使用

发送消息：

```php
    $amap = new Amqp();
    $message = new Message();
    $message->setHeader('name',"john");
    $message->setBodyContent("all-有一个消息");
    $message->setRoutingKey("r.*");
    $amap->publishMessage("fanout1",$message);
    
```


消费消息

```php
$amap = new Amqp();
$amap->consume("fanout1", 'r.mac', new AmqpTest());

class AmqpTest implements ProcessInterface
{
    public function process(\AMQPEnvelope $message, \AMQPQueue $q)
    {
        print_r($message->getBody());
        //确认消息
        $q->ack($message->getDeliveryTag());
    }
}

```


## 详细解释

### 配置

在配置文件中增加以下内容

```php
[
  'amqp'=>[
    'host' => '127.0.0.1',
    'vhost' => '/',
    'port' => 5672,
    'login' => 'admin',
    'password' => 'admin'
	]
]

```

### Message

message是一个消息包装类，可以把相关数据包起来，主要分为 消息主体和属性

```php
    $message = new Message();
    $message->setHeader('name',"john");
    $message->setBodyContent("all-有一个消息");
    $message->setRoutingKey("r.*");

```

- setHeader 设置header属性
- setAttribute  设置属性
- setBodyContent 设置发送消息内容
- setBody  设置发送消息内容
- setRoutingKey 设置 RoutingKey

**特别注意**

`setBody`为设置消息主体的功能，需要传递一个实现了`vitex\service\amqp\body\BodyInterface`的类。系统提供了2个实现，一个为字符串的一个为数组的：

- Json
- Text

最终都会编译为 `string`

```php
$message = new Message();
$body = new Json([
  "id" => 1,
	"name" => "name"
]);
$message->setBody($body);
```


### AmqpUtil
连接amqp的单例

获取连接

```AmqpUtil::instance()->connect()```

获取一个Channel
```$channel = AmqpUtil::instance()->defaultChannel();```

获取一个默认的Queue
```$queue = AmqpUtil::instance()->defaultQueue();```

可以指定一个 AmqpConfig的实例来指定配置

```AmqpUtil::instance($config)->connect()```



### Amqp

创建一个交换机
`createSimpleExchange`
也可直接使用 ```Exchange``` 自定义创建

发布简单的消息

```publishMessage```

发布消息

```publish```


消费消息

`consume`
消费者必须为 `ProcessInterface`实例

```php
$amap = new Amqp();
$amap->consume("fanout1", 'r.mac', new AmqpTest());

class AmqpTest implements ProcessInterface
{
    public function process(\AMQPEnvelope $message, \AMQPQueue $q)
    {
        print_r($message->getBody());
        //确认消息
        $q->ack($message->getDeliveryTag());
    }
}

```



### 实例

一个使用实例

```php
//通道
$channel = AmqpUtil::instance()->defaultChannel();
//交换机
$exchange = new Exchange($channel);
$exchange->setName("pushFanoutExchange");
//队列（此处已经存在不再声明）
$queue = new Queue($channel);
$queue->setName("pushFanoutQueue");

$message = new Message();
$body = new Json([
  "orgid"=> 29,
]);
$message->setBody($body);
$message->setRoutingKey("push");
$amqp = new Amqp();
$amqp->publish($exchange,$message);
```

