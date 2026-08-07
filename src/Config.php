<?php

declare(strict_types=1);

namespace BayarcashDemo;

use RuntimeException;

/** Reads config.php. Gitignored, because it holds your credentials. */
final class Config
{
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public static function load(?string $path = null): self
    {
        $path ??= dirname(__DIR__) . '/config.php';

        if (! is_file($path)) {
            throw new RuntimeException(
                "Config not found at {$path}\n" .
                'Copy config.sample.php to config.php and fill in your credentials.'
            );
        }

        $data = require $path;

        if (! is_array($data)) {
            throw new RuntimeException('config.php must return an array.');
        }

        return new self($data);
    }

    public function environment(): string
    {
        return $this->data['environment'] ?? 'sandbox';
    }

    public function credentials(?string $environment = null): array
    {
        $environment ??= $this->environment();
        $all = $this->data['environments'] ?? [];

        if (! isset($all[$environment])) {
            throw new RuntimeException(
                "No credentials for environment '{$environment}' in config.php. " .
                'Available: ' . (implode(', ', array_keys($all)) ?: 'none')
            );
        }

        return $all[$environment] + ['base_uri' => null];
    }

    public function hasEnvironment(string $environment): bool
    {
        return isset($this->data['environments'][$environment]);
    }

    public function timezone(): string
    {
        return $this->data['timezone'] ?? 'Asia/Kuala_Lumpur';
    }

    public function apiVersion(): string
    {
        return $this->data['api_version'] ?? 'v3';
    }

    public function baseUrl(): string
    {
        return rtrim($this->data['base_url'] ?? $this->guessBaseUrl(), '/') . '/';
    }

    public function url(string $path): string
    {
        return $this->baseUrl() . ltrim($path, '/');
    }

    public function db(): array
    {
        return ($this->data['db'] ?? []) + ['driver' => 'sqlite'];
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    private function guessBaseUrl(): string
    {
        $scheme = (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $dir    = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/');

        return "{$scheme}://{$host}{$dir}";
    }
}
