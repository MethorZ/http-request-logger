<?php

declare(strict_types=1);

namespace MethorZ\RequestLogger\Tests\Integration;

use PHPUnit\Framework\TestCase;

use function dirname;
use function escapeshellarg;
use function exec;
use function implode;
use function var_export;

use const PHP_BINARY;

/**
 * Runs the middleware in a separate PHP process whose autoloader refuses
 * every Monolog class, mirroring an application that does not install
 * the optional monolog/monolog package.
 */
final class WithoutMonologTest extends TestCase
{
    public function testMiddlewareHandlesRequestWhenMonologIsNotInstalled(): void
    {
        $autoload = var_export(dirname(__DIR__, 2) . '/vendor/autoload.php', true);
        $script = <<<PHP
            \$loader = require {$autoload};
            \$loader->unregister();
            spl_autoload_register(static function (string \$class) use (\$loader): void {
                if (!str_starts_with(\$class, 'Monolog\\\\')) {
                    \$loader->loadClass(\$class);
                }
            });

            \$middleware = new MethorZ\RequestLogger\Middleware\LoggingMiddleware(new Psr\Log\NullLogger());
            \$handler = new class implements Psr\Http\Server\RequestHandlerInterface {
                public function handle(
                    Psr\Http\Message\ServerRequestInterface \$request,
                ): Psr\Http\Message\ResponseInterface {
                    return new Nyholm\Psr7\Response(204);
                }
            };
            \$response = \$middleware->process(new Nyholm\Psr7\ServerRequest('GET', '/'), \$handler);

            echo \$response->getStatusCode(), '|', \$response->hasHeader('X-Request-ID') ? 'header' : 'no-header';
            PHP;

        $output = [];
        $exitCode = 0;
        exec(escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg($script) . ' 2>&1', $output, $exitCode);

        $this->assertSame(0, $exitCode, implode("\n", $output));
        $this->assertSame(['204|header'], $output);
    }
}
