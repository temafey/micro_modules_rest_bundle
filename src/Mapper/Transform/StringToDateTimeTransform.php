<?php

declare(strict_types=1);

namespace MicroModule\Rest\Mapper\Transform;

use Symfony\Component\ObjectMapper\TransformCallableInterface;

/**
 * Transform ISO8601 string to DateTime object.
 */
final readonly class StringToDateTimeTransform implements TransformCallableInterface
{
    public function __invoke(mixed $value, object $source, ?object $target): mixed
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value;
        }

        if (is_string($value)) {
            return new \DateTimeImmutable($value);
        }

        return $value;
    }
}
