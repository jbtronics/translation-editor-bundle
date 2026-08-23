<?php

declare(strict_types=1);

namespace Jbtronics\TranslationEditorBundle\Tests\DependencyInjection;

use Jbtronics\TranslationEditorBundle\Command\TouchCatalogCommand;
use Jbtronics\TranslationEditorBundle\Controller\SubmissionController;
use Jbtronics\TranslationEditorBundle\DependencyInjection\JbtronicsTranslationEditorExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class JbtronicsTranslationEditorExtensionTest extends TestCase
{
    private function buildContainer(array $configs = []): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', true);
        $container->setParameter('kernel.default_locale', 'en');
        $container->setParameter('kernel.enabled_locales', ['en']);
        $container->setParameter('translator.default_path', '%kernel.project_dir%/translations');
        $container->setParameter('kernel.project_dir', '/app');

        $extension = new JbtronicsTranslationEditorExtension();
        $extension->load($configs, $container);

        return $container;
    }

    public function testDefaultParametersAreRegistered(): void
    {
        $container = $this->buildContainer();

        self::assertSame(
            '%translator.default_path%',
            $container->getParameter('jbtronics.translation_editor.translations_path')
        );
        self::assertSame('xlf', $container->getParameter('jbtronics.translation_editor.format'));
        self::assertSame('2.0', $container->getParameter('jbtronics.translation_editor.xliff_version'));
        self::assertSame([], $container->getParameter('jbtronics.translation_editor.writer_options'));
        self::assertFalse($container->getParameter('jbtronics.translation_editor.use_intl_icu_format'));
        self::assertSame(
            '%kernel.default_locale%',
            $container->getParameter('jbtronics.translation_editor.default_locale')
        );
    }

    public function testParametersAreResolved(): void
    {
        $container = $this->buildContainer();
        $bag = $container->getParameterBag();

        self::assertSame(
            '/app/translations',
            $bag->resolveValue($container->getParameter('jbtronics.translation_editor.translations_path'))
        );
        self::assertSame(
            'en',
            $bag->resolveValue($container->getParameter('jbtronics.translation_editor.default_locale'))
        );
    }

    public function testCustomConfigOverridesDefaults(): void
    {
        $container = $this->buildContainer([
            ['translations_path' => '/custom/translations', 'format' => 'yaml'],
        ]);

        self::assertSame('/custom/translations', $container->getParameter('jbtronics.translation_editor.translations_path'));
        self::assertSame('yaml', $container->getParameter('jbtronics.translation_editor.format'));
    }

    public function testMessageEditorServiceIsRegistered(): void
    {
        $container = $this->buildContainer();

        self::assertTrue($container->hasDefinition('jbtronics.translations_editor.message_editor'));
    }

    public function testDataCollectorServiceIsRegistered(): void
    {
        $container = $this->buildContainer();

        self::assertTrue($container->hasDefinition('jbtronics.translations_editor.data_collector'));
    }

    public function testSubmissionControllerIsRegisteredAsPublic(): void
    {
        $container = $this->buildContainer();

        self::assertTrue($container->hasDefinition(SubmissionController::class));
        self::assertTrue($container->getDefinition(SubmissionController::class)->isPublic());
    }

    public function testTouchCatalogCommandIsRegisteredWhenConsoleIsAvailable(): void
    {
        $container = $this->buildContainer();

        //symfony/console is available in the test environment (it is an optional runtime dependency),
        //so the command should be registered and tagged accordingly
        self::assertTrue($container->hasDefinition(TouchCatalogCommand::class));
        self::assertTrue($container->getDefinition(TouchCatalogCommand::class)->hasTag('console.command'));
    }
}
