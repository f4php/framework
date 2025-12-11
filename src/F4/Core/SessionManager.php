<?php

declare(strict_types=1);

namespace F4\Core;

use F4\Core\SessionManagerInterface;

use Exception;

use function session_name;
use function session_get_cookie_params;
use function session_regenerate_id;
use function session_set_cookie_params;
use function session_start;
use function session_status;
use function session_unset;

class SessionManager implements SessionManagerInterface
{
    public bool $started {
        get { return session_status() === PHP_SESSION_ACTIVE; }
    }
    public function __construct() {}
    public function get(?string $key = null): mixed {
        $this->ensureStarted();
        return match($key === null) {
            true => $_SESSION ?? [],
            default => $_SESSION[$key] ?? null,
        };
    }
    public function getName(): string {
        return session_name() ?: '';
    }
    public function getParameters(): array {
        return session_get_cookie_params();
    }
    public function regenerate(bool $deleteOldSession = false): bool {
        $this->ensureStarted();
        return session_regenerate_id($deleteOldSession);
    }
    public function reset(): bool {
        $this->ensureStarted();
        return session_unset();
    }
    public function set(string $key, mixed $value): mixed {
        $this->ensureStarted();
        return $_SESSION[$key] = $value;
    }
    public function setName(string $name): bool {
        return session_name($name) !== false;
    }
    public function setParameters(array $parameters): bool {
        return session_set_cookie_params([
            ...[
                'lifetime' => 0,
                'path' => '/',
                'domain' => '',
                'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
                'httponly' => true,
                'samesite' => 'Lax'
            ],
            ...$parameters
        ]);
    }
    public function start(array $options = []): bool {
        return session_start($options);
    }
    protected function ensureStarted(): bool
    {
        return match($this->started ?: $this->start()) {
            true => true,
            default => throw new Exception('Failed to start session'),
        };
    }

}
