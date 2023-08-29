<?php

declare(strict_types=1);

namespace TXC\Box\Infrastructure\CompilerPasses\Twig;

use TXC\Box\Infrastructure\DependencyInjection\ContainerBuilder;
use TXC\Box\Infrastructure\Environment\Settings;
use TXC\Box\Interfaces\CompilerPass;

class TranslateResourcesCompilerPass implements CompilerPass
{
    public function process(ContainerBuilder $container, Settings $settings): void
    {
        $appRoot = Settings::getAppRoot();
        $resourcesContainer = $container->findDefinition(TranslateResourcesContainer::class);

        $availableLocales = $settings->get('slim.available_locales');
        $defaultLocale = $settings->get('slim.locale');
        if (!in_array($defaultLocale, $availableLocales)) {
            array_unshift($availableLocales, $defaultLocale);
        }

        $pattern = '%s/i18n/%s/LC_MESSAGES/messages.mo';
        foreach ($availableLocales as $locale) {
            $path = sprintf($pattern, $appRoot, $locale);
            if (!file_exists($path)) {
                continue;
            }
            $resourcesContainer->method('registerLocale', $locale, $path);
        }

        $container->addDefinitions([TranslateResourcesContainer::class => $resourcesContainer]);
    }
}
