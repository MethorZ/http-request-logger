# MethorZ Structured Logging

**PSR-3 structured logging with request tracking and performance monitoring for PSR-15 applications**

[![PHP Version](https://img.shields.io/badge/php-%5E8.2-blue)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

Automatically add request IDs and performance metrics to all logs in PSR-15 middleware applications. Zero configuration, framework-agnostic, production-ready.

---

## ✨ Features

- 🔖 **Request ID Tracking** - Unique ID for every request, added to all logs automatically
- ⏱️ **Performance Monitoring** - Track execution time and memory usage for requests and operations
- 🎯 **PSR-15 Middleware** - Drop-in middleware for automatic logging
- 📊 **Structured Logs** - JSON-compatible context for easy parsing and analysis
- 🔧 **PSR-3 Compatible** - Works with any PSR-3 logger (Monolog, etc.)
- 🌐 **Response Headers** - Optionally adds `X-Request-ID` header to responses
- 🚀 **Zero Configuration** - Works out-of-the-box with sensible defaults
- 🎨 **Customizable** - Full control over processors and logging behavior

---

## 📦 Installation

```bash
composer require methorz/structured-logging
```

---

## 🚀 Quick Start

### **1. Add Middleware to Your Application**

```php
use Methorz\StructuredLogging\Middleware\LoggingMiddleware;

// Mezzio / Laminas
$app->pipe(new LoggingMiddleware($logger));

// Any PSR-15 application
$dispatcher->pipe(new LoggingMiddleware($logger));
```

That's it! Every request will now have:
- Unique request ID in all logs
- Request start/end logging
- Automatic performance metrics
- Exception logging with context

---

## 📖 Detailed Usage

### **Request ID Processor**

Adds a unique request ID to all log records:

```php
use Methorz\StructuredLogging\Processor\RequestIdProcessor;
use Monolog\Logger;

$logger = new Logger('app');
$logger->pushProcessor(new RequestIdProcessor());

$logger->info('User logged in', ['user_id' => 123]);
// Log output includes: {"message": "User logged in", "extra": {"request_id": "req_..."}"}
```

**Custom Request ID**:
```php
$processor = new RequestIdProcessor('custom-request-id');
```

**Retrieve Request ID**:
```php
$requestId = $processor->getRequestId();
```

### **Performance Logger**

Track performance metrics for operations:

```php
use Methorz\StructuredLogging\Logger\PerformanceLogger;

$perfLogger = new PerformanceLogger($logger);

// Method 1: Start/End
$perfLogger->start('database-query');
$users = $repository->findAll();
$perfLogger->end('database-query', ['query' => 'SELECT * FROM users']);

// Method 2: Measure callable
$result = $perfLogger->measure('api-call', function () use ($apiClient) {
    return $apiClient->fetchData();
}, ['endpoint' => '/api/users']);

// Method 3: Log request performance
$startTime = microtime(true);
// ... handle request ...
$perfLogger->logRequest($startTime, [
    'method' => 'POST',
    'uri' => '/api/users',
    'status' => 201,
]);
```

**Logged Performance Metrics**:
```json
{
    "message": "Performance: database-query",
    "context": {
        "operation": "database-query",
        "duration_ms": 45.23,
        "memory_peak_mb": 12.5,
        "query": "SELECT * FROM users"
    }
}
```

### **Logging Middleware**

PSR-15 middleware for automatic request logging:

```php
use Methorz\StructuredLogging\Middleware\LoggingMiddleware;
use Methorz\StructuredLogging\Processor\RequestIdProcessor;

// Basic usage
$middleware = new LoggingMiddleware($logger);

// Custom request ID processor
$processor = new RequestIdProcessor('custom-id');
$middleware = new LoggingMiddleware($logger, $processor);

// Disable X-Request-ID header
$middleware = new LoggingMiddleware($logger, null, false);
```

**What It Logs**:

Request start:
```json
{
    "message": "Request started",
    "context": {
        "method": "POST",
        "uri": "https://example.com/api/users",
        "request_id": "req_673e5c2f47a0c1.23456789"
    }
}
```

Request completion:
```json
{
    "message": "Request completed",
    "context": {
        "method": "POST",
        "uri": "https://example.com/api/users",
        "status": 201,
        "duration_ms": 127.45,
        "memory_peak_mb": 15.2,
        "request_id": "req_673e5c2f47a0c1.23456789"
    }
}
```

Exception:
```json
{
    "message": "Request failed with exception",
    "context": {
        "method": "POST",
        "uri": "https://example.com/api/users",
        "exception": "User not found",
        "exception_class": "App\\Exception\\NotFoundException",
        "request_id": "req_673e5c2f47a0c1.23456789"
    }
}
```

---

## 🎯 Use Cases

### **Distributed Tracing**

Correlate logs across services using request IDs:

```php
// Service A
$requestId = $processor->getRequestId();
$client->request('POST', '/api/service-b', [
    'headers' => ['X-Request-ID' => $requestId],
]);

// Service B receives request with same ID
// All logs from both services share the request ID
```

### **Performance Bottleneck Detection**

Track slow operations:

```php
$perfLogger->start('slow-operation');
$result = $this->processData($largeDataset);
$perfLogger->end('slow-operation', [
    'records_processed' => count($largeDataset),
], LogLevel::WARNING); // Use WARNING level if it's slow
```

### **Production Debugging**

Find all logs for a specific request:

```bash
# Filter logs by request ID
cat app.log | jq 'select(.extra.request_id == "req_673e5c2f47a0c1.23456789")'
```

---

## 🔧 Configuration

### **Mezzio Configuration**

```php
// config/autoload/logging.global.php
use Methorz\StructuredLogging\Middleware\LoggingMiddleware;
use Methorz\StructuredLogging\Processor\RequestIdProcessor;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

return [
    'dependencies' => [
        'factories' => [
            LoggerInterface::class => function (ContainerInterface $container): Logger {
                $logger = new Logger('app');
                $logger->pushHandler(new StreamHandler('php://stdout', Logger::INFO));
                return $logger;
            },
            RequestIdProcessor::class => fn() => new RequestIdProcessor(),
            LoggingMiddleware::class => function (ContainerInterface $container): LoggingMiddleware {
                return new LoggingMiddleware(
                    $container->get(LoggerInterface::class),
                    $container->get(RequestIdProcessor::class),
                );
            },
        ],
    ],
];

// config/pipeline.php
$app->pipe(LoggingMiddleware::class);
```

---

## 📊 Log Structure

All logs follow a consistent structure for easy parsing:

```json
{
    "message": "User action",
    "context": {
        "user_id": 123,
        "action": "login"
    },
    "level": 200,
    "level_name": "INFO",
    "channel": "app",
    "datetime": "2024-11-26T10:30:45.123456+00:00",
    "extra": {
        "request_id": "req_673e5c2f47a0c1.23456789"
    }
}
```

---

## 🔍 Monolog Integration

Works seamlessly with Monolog:

```php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\JsonFormatter;
use Methorz\StructuredLogging\Processor\RequestIdProcessor;

$logger = new Logger('app');

// JSON formatter for structured logs
$handler = new StreamHandler('php://stdout', Logger::INFO);
$handler->setFormatter(new JsonFormatter());
$logger->pushHandler($handler);

// Add request ID processor
$logger->pushProcessor(new RequestIdProcessor());
```

---

## 🧪 Testing

```bash
# Run tests
composer test

# Static analysis
composer analyze

# Code style
composer cs-check
composer cs-fix
```

---

## 📄 License

MIT License. See [LICENSE](LICENSE) for details.

---

## 🤝 Contributing

Contributions welcome! See [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines.

---

## 🔗 Links

- [Documentation](docs/)
- [Changelog](CHANGELOG.md)
- [Issues](https://github.com/MethorZ/structured-logging/issues)

