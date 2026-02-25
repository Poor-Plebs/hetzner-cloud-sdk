# poor-plebs/hetzner-cloud-sdk

[![CI](https://github.com/Poor-Plebs/hetzner-cloud-sdk/actions/workflows/ci.yml/badge.svg)](https://github.com/Poor-Plebs/hetzner-cloud-sdk/actions/workflows/ci.yml)
[![codecov](https://codecov.io/gh/Poor-Plebs/hetzner-cloud-sdk/branch/main/graph/badge.svg)](https://codecov.io/gh/Poor-Plebs/hetzner-cloud-sdk)

**[What is it for?](#what-is-it-for)** |
**[What are the requirements?](#what-are-the-requirements)** |
**[How to install it?](#how-to-install-it)** |
**[How to use it?](#how-to-use-it)** |
**[How to contribute?](#how-to-contribute)**

Framework-agnostic Hetzner Cloud API SDK with typed models and safe token obfuscation.

## What is it for?

A fully async PHP SDK for the [Hetzner Cloud API](https://docs.hetzner.cloud/). All methods return `GuzzleHttp\Promise\PromiseInterface`, allowing non-blocking usage. Features include:

- Typed readonly models with factory methods
- Bearer token obfuscation in logs and exceptions
- Automatic retry on connection failures and `Retry-After` responses
- PSR-3 logger and PSR-16 cache integration

### Supported Resources

- **Servers** — list, get, create, delete, power on/off, rebuild
- **Firewalls** — list, get, create, delete, set rules, apply/remove resources
- **SSH Keys** — list, get, create, delete
- **Actions** — get

## What are the requirements?

- PHP 8.4 or above
- `ext-json`

## How to install it?

```bash
composer require poor-plebs/hetzner-cloud-sdk
```

## How to use it?

```php
use PoorPlebs\HetznerCloudSdk\HetznerCloudClient;

$client = new HetznerCloudClient(
    apiToken: 'your-hetzner-api-token',
    cache: $yourPsr16Cache,
);

// List servers
$response = $client->servers()->list()->wait();
foreach ($response->result as $server) {
    echo $server->name . ' — ' . $server->status . PHP_EOL;
}

// Get a single server
$response = $client->servers()->get(123)->wait();
echo $response->result->publicNet->ipv4->ip;

// Create a server
$response = $client->servers()->create([
    'name' => 'my-server',
    'server_type' => 'cx22',
    'image' => 'ubuntu-24.04',
    'location' => 'fsn1',
])->wait();

// Power off a server
$client->servers()->powerOff(123)->wait();

// List firewalls
$response = $client->firewalls()->list()->wait();

// Create an SSH key
$response = $client->sshKeys()->create(
    name: 'deploy-key',
    publicKey: 'ssh-rsa AAAA...',
)->wait();

// Set a PSR-3 logger
$client->setLogger($yourPsr3Logger);
```

## How to contribute?

`poor-plebs/hetzner-cloud-sdk` follows semantic versioning. Read more on
[semver.org][1].

Create issues to report problems or requests. Fork and create pull requests to
propose solutions and ideas.

### Development Setup

This project uses modern PHP tooling with strict quality standards:

- **Testing**: [Pest PHP](https://pestphp.com/) v4 with parallel execution
- **Static Analysis**: PHPStan at level `max` with strict and deprecation rules
- **Code Style**: PHP-CS-Fixer (PSR-12)
- **Coverage Requirements**: Minimum 80% code coverage and 80% type coverage

### Available Commands

```bash
composer test          # Run tests (parallel, no coverage)
composer coverage      # Run tests with coverage (min 80%)
composer type-coverage # Check type coverage (min 80%)
composer static        # Run PHPStan analysis
composer cs            # Check code style
composer csf           # Fix code style
composer ci            # Run full CI pipeline
```

If local PHP/Composer are unavailable, use Docker via `bin/dc <composer-args...>`.

[1]: https://semver.org
