<?php

declare(strict_types=1);


namespace Jbtronics\TranslationEditorBundle;

use Jbtronics\TranslationEditorBundle\DependencyInjection\JbtronicsTranslationEditorExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class JbtronicsTranslationEditorBundle extends AbstractBundle
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        return new JbtronicsTranslationEditorExtension();
    }
}