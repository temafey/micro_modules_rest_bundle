# MicroModule REST Bundle

Reusable REST API infrastructure bundle for Symfony-based microservices. Provides standardized HTTP-layer components to eliminate duplication across projects.

## Features

- **API Versioning** — Version manager, resolver, registry, and response builder
- **Base Controllers** — Abstract API controller with common REST patterns
- **DTO Mapping** — Symfony ObjectMapper integration with custom transforms (UUID, DateTime)
- **JSON Path Filtering** — Response field filtering via JSON Path expressions
- **Event Listeners** — API versioning, process UUID, trailing slash redirect, domain exception handling
- **CSRF Protection** — Stateless CSRF token service and validation listener
- **Pagination & HATEOAS Traits** — Pagination, resource links, and JSON path filter traits

## Installation

```bash
composer require micro-module/rest-bundle
```

## Configuration

Register the bundle in `config/bundles.php`:

```php
return [
    // ...
    MicroModule\Rest\RestBundle::class => ['all' => true],
];
```

## Requirements

- PHP ^8.4
- Symfony ^7.0 || ^8.0

## Optional Dependencies

- `symfony/object-mapper` — for DtoMapper component
- `symfony/json-path` — for JsonPathFilter component
- `ramsey/uuid` — for ProcessUuidListener and UuidToStringTransform
- `symfony/security-csrf` — for CSRF protection components

## License

Proprietary
