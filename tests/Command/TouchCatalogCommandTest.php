<?php

declare(strict_types=1);

namespace Jbtronics\TranslationEditorBundle\Tests\Command;

use Jbtronics\TranslationEditorBundle\Command\TouchCatalogCommand;
use Jbtronics\TranslationEditorBundle\Service\MessageEditor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Translation\Reader\TranslationReaderInterface;
use Symfony\Component\Translation\Writer\TranslationWriterInterface;

class TouchCatalogCommandTest extends TestCase
{
    //MessageEditor is final and cannot be mocked directly, so we use a real instance wired to
    //mocked reader/writer collaborators and observe its effect through the writer.
    private TranslationWriterInterface&MockObject $writer;
    private MessageEditor $messageEditor;

    protected function setUp(): void
    {
        $reader = $this->createMock(TranslationReaderInterface::class);
        $this->writer = $this->createMock(TranslationWriterInterface::class);

        $this->messageEditor = new MessageEditor(
            translationWriter: $this->writer,
            translationReader: $reader,
            translationPath: '/translations',
        );
    }

    private function getCommandTester(array $enabledLocales = []): CommandTester
    {
        return new CommandTester(new TouchCatalogCommand($this->messageEditor, $enabledLocales));
    }

    public function testUpdatesCatalogueForGivenLocale(): void
    {
        $this->writer->expects(self::once())->method('write');

        $tester = $this->getCommandTester();
        $exitCode = $tester->execute([
            'domain' => 'messages',
            'locale' => 'de',
        ]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('Updated message IDs for domain "messages" and locale "de".', $tester->getDisplay());
    }

    public function testFailsWhenNeitherLocaleNorAllLocalesIsGiven(): void
    {
        $this->writer->expects(self::never())->method('write');

        $tester = $this->getCommandTester();
        $exitCode = $tester->execute([
            'domain' => 'messages',
        ]);

        self::assertSame(Command::INVALID, $exitCode);
        self::assertStringContainsString('You must either pass a locale argument or use --all-locales.', $tester->getDisplay());
    }

    public function testFailsWhenBothLocaleAndAllLocalesAreGiven(): void
    {
        $this->writer->expects(self::never())->method('write');

        $tester = $this->getCommandTester();
        $exitCode = $tester->execute([
            'domain' => 'messages',
            'locale' => 'de',
            '--all-locales' => true,
        ]);

        self::assertSame(Command::INVALID, $exitCode);
        self::assertStringContainsString('You cannot pass a locale argument and use --all-locales', $tester->getDisplay());
    }

    public function testFailsWhenAllLocalesIsGivenButNoLocalesAreEnabled(): void
    {
        $this->writer->expects(self::never())->method('write');

        $tester = $this->getCommandTester(enabledLocales: []);
        $exitCode = $tester->execute([
            'domain' => 'messages',
            '--all-locales' => true,
        ]);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertStringContainsString('No locales are enabled in the application.', $tester->getDisplay());
    }

    public function testUpdatesCatalogueForAllEnabledLocales(): void
    {
        $this->writer->expects(self::exactly(3))->method('write');

        $tester = $this->getCommandTester(enabledLocales: ['en', 'de', 'fr']);
        $exitCode = $tester->execute([
            'domain' => 'messages',
            '--all-locales' => true,
        ]);

        self::assertSame(Command::SUCCESS, $exitCode);
        $display = $tester->getDisplay();
        self::assertStringContainsString('Updated message IDs for domain "messages" and locale "en".', $display);
        self::assertStringContainsString('Updated message IDs for domain "messages" and locale "de".', $display);
        self::assertStringContainsString('Updated message IDs for domain "messages" and locale "fr".', $display);
        self::assertStringContainsString('Updated message IDs for domain "messages" (3 locale(s)).', $display);
    }
}
