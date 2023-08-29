<?php

declare(strict_types=1);

namespace TXC\Box\Infrastructure\CompilerPasses\Twig;

class TranslateResourcesContainer
{
    private array $translations = [];

    public function registerLocale(string $locale, string $path): void
    {
        if (array_key_exists($locale, $this->getTranslations())) {
            throw new \RuntimeException(sprintf('Locale "%s" already registered in container', $locale));
        }
        $this->translations[$locale] = $path;
    }

    /**
     * @return string[]
     */
    public function getTranslations(): array
    {
        return $this->translations;
    }

    public function getTranslation(string $pattern): ?string
    {
        return $this->translations[$pattern] ?? null;
    }
}
