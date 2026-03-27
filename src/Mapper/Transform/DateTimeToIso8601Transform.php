<?php

declare(strict_types=1);

namespace MicroModule\Rest\Mapper\Transform;

use Symfony\Component\ObjectMapper\TransformCallableInterface;

/**
 * Transform DateTime to ISO8601 string format.
 */
final readonly class DateTimeToIso8601Transform implements TransformCallableInterface
{
    public function __invoke(mixed $value, object $source, ?object $target): mixed
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format(\DateTimeInterface::ATOM);
        }

        return $value;
    }
}
