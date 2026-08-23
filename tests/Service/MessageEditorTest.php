<?php

declare(strict_types=1);

namespace Jbtronics\TranslationEditorBundle\Tests\Service;

use Jbtronics\TranslationEditorBundle\Service\MessageEditor;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\Reader\TranslationReaderInterface;
use Symfony\Component\Translation\Writer\TranslationWriterInterface;

class MessageEditorTest extends TestCase
{
    private function makeReader(MessageCatalogue $catalogueToReturn): TranslationReaderInterface
    {
        $reader = $this->createMock(TranslationReaderInterface::class);
        $reader->method('read')
            ->willReturnCallback(function (string $directory, MessageCatalogue $catalogue) use ($catalogueToReturn) {
                $catalogue->addCatalogue($catalogueToReturn);
            });

        return $reader;
    }

    public function testEditMessageWritesUpdatedCatalogueForOnlyTheAffectedDomain(): void
    {
        $existing = new MessageCatalogue('en', [
            'messages' => ['existing.key' => 'Existing Value'],
            'validators' => ['other.key' => 'Other Value'],
        ]);

        $reader = $this->makeReader($existing);
        $writer = $this->createMock(TranslationWriterInterface::class);

        $writer->expects(self::once())
            ->method('write')
            ->with(
                self::callback(function (MessageCatalogue $catalogue) {
                    self::assertSame('en', $catalogue->getLocale());
                    self::assertSame(['messages'], $catalogue->getDomains());
                    self::assertSame(
                        ['existing.key' => 'Existing Value', 'new.key' => 'New Value'],
                        $catalogue->all('messages')
                    );

                    return true;
                }),
                'xlf',
                self::callback(function (array $options) {
                    self::assertSame('/translations', $options['path']);
                    self::assertSame('2.0', $options['xliff_version']);
                    self::assertSame('en', $options['default_locale']);

                    return true;
                })
            );

        $editor = new MessageEditor(
            translationWriter: $writer,
            translationReader: $reader,
            translationPath: '/translations',
            format: 'xlf',
            xliffVersion: '2.0',
            defaultLocale: 'en',
        );

        $editor->editMessage('new.key', 'en', 'messages', 'New Value');
    }

    public function testEditMessageAppendsIntlIcuSuffixToDomainWhenConfigured(): void
    {
        $existing = new MessageCatalogue('de', [
            'messages+intl-icu' => ['existing.key' => 'Bestehend'],
        ]);

        $reader = $this->makeReader($existing);
        $writer = $this->createMock(TranslationWriterInterface::class);

        $writer->expects(self::once())
            ->method('write')
            ->with(
                self::callback(function (MessageCatalogue $catalogue) {
                    //getDomains() strips the "+intl-icu" suffix, so the plain domain name is reported
                    self::assertSame(['messages'], $catalogue->getDomains());
                    self::assertSame(
                        'Neuer Wert',
                        $catalogue->get('new.key', 'messages+intl-icu')
                    );
                    self::assertSame(
                        'Bestehend',
                        $catalogue->get('existing.key', 'messages+intl-icu')
                    );

                    return true;
                }),
                self::anything(),
                self::anything()
            );

        $editor = new MessageEditor(
            translationWriter: $writer,
            translationReader: $reader,
            translationPath: '/translations',
            useIntl: true,
        );

        $editor->editMessage('new.key', 'de', 'messages', 'Neuer Wert');
    }

    public function testWriteOptionsOmitXliffSpecificKeysForNonXliffFormats(): void
    {
        $existing = new MessageCatalogue('en', ['messages' => []]);
        $reader = $this->makeReader($existing);
        $writer = $this->createMock(TranslationWriterInterface::class);

        $writer->expects(self::once())
            ->method('write')
            ->with(
                self::anything(),
                'yaml',
                self::callback(function (array $options) {
                    self::assertArrayNotHasKey('xliff_version', $options);
                    self::assertArrayNotHasKey('default_locale', $options);
                    self::assertSame('/translations', $options['path']);

                    return true;
                })
            );

        $editor = new MessageEditor(
            translationWriter: $writer,
            translationReader: $reader,
            translationPath: '/translations',
            format: 'yaml',
        );

        $editor->editMessage('some.key', 'en', 'messages', 'Some Value');
    }

    public function testWriterOptionsAreMergedIntoWriteOptions(): void
    {
        $existing = new MessageCatalogue('en', ['messages' => []]);
        $reader = $this->makeReader($existing);
        $writer = $this->createMock(TranslationWriterInterface::class);

        $writer->expects(self::once())
            ->method('write')
            ->with(
                self::anything(),
                self::anything(),
                self::callback(function (array $options) {
                    self::assertSame('some_value', $options['custom_option']);
                    //writer options should be able to override the path too, since they are merged last
                    self::assertSame('/translations', $options['path']);

                    return true;
                })
            );

        $editor = new MessageEditor(
            translationWriter: $writer,
            translationReader: $reader,
            translationPath: '/translations',
            writerOptions: ['custom_option' => 'some_value'],
        );

        $editor->editMessage('some.key', 'en', 'messages', 'Some Value');
    }

    public function testUpdateMessageIdsRewritesCatalogueWithoutChangingMessages(): void
    {
        $existing = new MessageCatalogue('en', [
            'messages' => ['a' => '1', 'b' => '2'],
        ]);

        $reader = $this->makeReader($existing);
        $writer = $this->createMock(TranslationWriterInterface::class);

        $writer->expects(self::once())
            ->method('write')
            ->with(
                self::callback(function (MessageCatalogue $catalogue) {
                    self::assertSame(['a' => '1', 'b' => '2'], $catalogue->all('messages'));

                    return true;
                }),
                self::anything(),
                self::anything()
            );

        $editor = new MessageEditor(
            translationWriter: $writer,
            translationReader: $reader,
            translationPath: '/translations',
        );

        $editor->updateMessageIds('en', 'messages');
    }

    public function testDomainMetadataIsCopiedToTheSubCatalogue(): void
    {
        $existing = new MessageCatalogue('en', [
            'messages' => ['a' => '1'],
        ]);
        $existing->setMetadata('a', ['notes' => [['content' => 'a note']]], 'messages');

        $reader = $this->makeReader($existing);
        $writer = $this->createMock(TranslationWriterInterface::class);

        $writer->expects(self::once())
            ->method('write')
            ->with(
                self::callback(function (MessageCatalogue $catalogue) {
                    self::assertSame(
                        ['notes' => [['content' => 'a note']]],
                        $catalogue->getMetadata('a', 'messages')
                    );

                    return true;
                }),
                self::anything(),
                self::anything()
            );

        $editor = new MessageEditor(
            translationWriter: $writer,
            translationReader: $reader,
            translationPath: '/translations',
        );

        $editor->updateMessageIds('en', 'messages');
    }
}
