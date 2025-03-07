<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Event;

class Registry
{
    /**
     * @var array<string, class-string<EventInterface>>
     */
    protected array $map = [];

    /**
     * @param class-string<EventInterface> $event
     *
     * @noinspection PhpUnused as this method is called on object creation by the container
     */
    public function addEventType(string $event): void
    {
        $this->map[(new $event())->getEvent()] = $event;
    }

    public function getEvent(string $event): ?EventInterface
    {
        /**
         * @var string                       $eventToCompare
         * @var class-string<EventInterface> $eventClass
         */
        foreach ($this->map as $eventToCompare => $eventClass) {
            if (($pos = strpos($eventToCompare, '*', 0)) !== false) {
                $eventToCompare = substr($eventToCompare, 0, $pos);
            }

            if (str_starts_with($event, $eventToCompare)) {
                /** @var EventInterface $instance */
                $instance = new $eventClass();
                $instance->setEvent($event);

                return $instance;
            }
        }

        return null;
    }
}
