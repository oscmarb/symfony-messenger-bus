<?php

declare(strict_types=1);

namespace Oscmarb\MessengerBus;

use Oscmarb\Ddd\Domain\Service\Utils\CallableFirstParameterExtractor;
use Symfony\Component\Messenger\Exception\NoHandlerForMessageException;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;

class SymfonyMessengerBus
{
    private MessageBus $bus;

    /**
     * @param array $handlers
     * @param array<MiddlewareInterface> $middlewares
     * @param bool $expectsHandlers
     */
    public function __construct(array $handlers, array $middlewares, private bool $expectsHandlers)
    {
        if (false === $this->expectsHandlers) {
            $middlewares[] = new NotHandlerExpectedMiddleware();
        }

        $this->bus = new MessageBus(
            [
                ...$middlewares,
                new HandleMessageMiddleware(
                    new HandlersLocator(
                        CallableFirstParameterExtractor::forCallables(
                            new \ArrayObject($handlers)
                        )
                    )
                ),
            ]
        );
    }

    public function handle(mixed $data): void
    {
        try {
            $this->bus->dispatch($data);
        } catch (NoHandlerForMessageException $exception) {
            if (true === $this->expectsHandlers) {
                throw $this->noHandlerForMessageException($data) ?? $exception;
            }
        } catch (\Throwable $exception) {
            $prevException = $exception->getPrevious() ?? $exception;

            if (true === $prevException instanceof NoHandlerForMessageException && false === $this->expectsHandlers) {
                return;
            }

            throw $prevException;
        }
    }

    protected function noHandlerForMessageException(mixed $data): ?\Throwable
    {
        return null;
    }
}