<?php

declare(strict_types=1);

namespace Oscmarb\MessengerBus;

use Oscmarb\Ddd\Domain\Service\Utils\CallableFirstParameterExtractor;
use Symfony\Component\Messenger\Exception\NoHandlerForMessageException;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

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

    public function dispatch(mixed $data): mixed
    {
        try {
            /** @var ?HandledStamp $stamp */
            $stamp = $this->bus->dispatch($data)->last(HandledStamp::class);

            return $stamp?->getResult();
        } catch (NoHandlerForMessageException $exception) {
            throw $this->noHandlerForMessageException($data) ?? $exception;
        } catch (\Throwable $exception) {
            throw $exception->getPrevious() ?? $exception;
        }
    }

    protected function noHandlerForMessageException(mixed $data): ?\Throwable
    {
        return null;
    }
}