<?php

declare(strict_types=1);

namespace Jbtronics\TranslationEditorBundle\Tests;

use Jbtronics\TranslationEditorBundle\DependencyInjection\JbtronicsTranslationEditorExtension;
use Jbtronics\TranslationEditorBundle\JbtronicsTranslationEditorBundle;
use PHPUnit\Framework\TestCase;

class JbtronicsTranslationEditorBundleTest extends TestCase
{
    public function testGetContainerExtensionReturnsTheBundleExtension(): void
    {
        $bundle = new JbtronicsTranslationEditorBundle();

        self::assertInstanceOf(JbtronicsTranslationEditorExtension::class, $bundle->getContainerExtension());
    }
}
