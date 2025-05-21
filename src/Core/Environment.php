<?php

declare(strict_types=1);

namespace Src\Core;

class Environment
{
    private static ?Environment $instance = null;
    private string $environment;
    private bool $isProduction;
    private bool $isDevelopment;
    private bool $isTesting;

    private function __construct()
    {
        $this->environment = $_ENV['APP_ENV'] ?? 'development';
        $this->isProduction = $this->environment === 'production';
        $this->isDevelopment = $this->environment === 'development';
        $this->isTesting = $this->environment === 'testing';
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function isProduction(): bool
    {
        return $this->isProduction;
    }

    public function isDevelopment(): bool
    {
        return $this->isDevelopment;
    }

    public function isTesting(): bool
    {
        return $this->isTesting;
    }

    public function getEnvironment(): string
    {
        return $this->environment;
    }

    public function shouldShowErrors(): bool
    {
        return !$this->isProduction;
    }

    public function shouldLogErrors(): bool
    {
        return true;
    }

    public function shouldLogDetails(): bool
    {
        return !$this->isProduction;
    }

    public function getBasePath(): string
    {
        return $_ENV['APP_BASE_PATH'] ?? '/api';
    }
}