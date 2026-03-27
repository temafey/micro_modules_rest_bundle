<?php

declare(strict_types=1);

namespace MicroModule\Rest\Mapper\Transform;

use MicroModule\Base\Domain\ValueObject\Uuid;
use Symfony\Component\ObjectMapper\TransformCallableInterface;

/**
 * Transform UUID value object to string representation.
 */
final readonly class UuidToStringTransform implements TransformCallableInterface
{
    public function __invoke(mixed $value, object $source, ?object $target): mixed
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Uuid) {
            return $value->toNative();
        }

        if (is_string($value)) {
            return $value;
        }

        return (string) $value;
    }
}
