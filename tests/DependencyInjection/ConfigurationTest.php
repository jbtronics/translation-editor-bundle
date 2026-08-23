<?php

declare(strict_types=1);

namespace Jbtronics\TranslationEditorBundle\Tests\DependencyInjection;

use Jbtronics\TranslationEditorBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends TestCase
{
    private function process(array $configs): array
    {
        $processor = new Processor();

        return $processor->processConfiguration(new Configuration(), $configs);
    }

    public function testDefaultValues(): void
    {
        $config = $this->process([]);

        self::assertSame('%translator.default_path%', $config['translations_path']);
        self::assertSame('xlf', $config['format']);
        self::assertSame('2.0', $config['xliff_version']);
        self::assertSame('%kernel.default_locale%', $config['default_locale']);
        self::assertFalse($config['use_intl_icu_format']);
        self::assertSame([], $config['writer_options']);
    }

    public function testValuesCanBeOverridden(): void
    {
        $config = $this->process([
            [
                'translations_path' => '/some/path',
                'format' => 'yaml',
                'xliff_version' => '1.2',
                'default_locale' => 'de',
                'use_intl_icu_format' => true,
                'writer_options' => ['some_option' => 'some_value'],
            ],
        ]);

        self::assertSame('/some/path', $config['translations_path']);
        self::assertSame('yaml', $config['format']);
        self::assertSame('1.2', $config['xliff_version']);
        self::assertSame('de', $config['default_locale']);
        self::assertTrue($config['use_intl_icu_format']);
        self::assertSame(['some_option' => 'some_value'], $config['writer_options']);
    }

    public function testMultipleConfigsAreMerged(): void
    {
        $config = $this->process([
            ['translations_path' => '/first/path'],
            ['format' => 'po'],
        ]);

        //Second config should win for the format, but the first config's path should still be set,
        //as it was not overridden by the second config
        self::assertSame('/first/path', $config['translations_path']);
        self::assertSame('po', $config['format']);
    }
}
