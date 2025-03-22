<?php

declare(strict_types=1);


namespace Jbtronics\TranslationEditorBundle\Service;

use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\Reader\TranslationReaderInterface;
use Symfony\Component\Translation\Writer\TranslationWriterInterface;

/**
 * This services handles the editing of translation messages using the configured options
 * @internal
 */
final class MessageEditor
{
    public function __construct(
        private readonly TranslationWriterInterface $translationWriter,
        private readonly TranslationReaderInterface $translationReader,
        private readonly string $translationPath,
        private readonly string $format = "xlf",
        private readonly string $xliffVersion = "2.0",
        private readonly array $writerOptions = [],
        private readonly bool $useIntl = false
    )
    {
    }

    /**
     * Load the current translations for the given locale. This is always directly loaded from the files, so that
     * we do not have to deal with the cached translator service.
     * @param  string  $locale
     * @return MessageCatalogue
     */
    private function loadCurrentTranslations(string $locale): MessageCatalogue
    {
        $messageCatalogue = new MessageCatalogue($locale);
        $this->translationReader->read($this->translationPath, $messageCatalogue);

        return $messageCatalogue;
    }

    /**
     * Create a sub catalogue only containing the given domain
     * @param  MessageCatalogue  $catalogue
     * @param  string  $messageDomain The domain to extract
     * @return MessageCatalogue
     */
    private function getDomainOnlyCatalogue(MessageCatalogue $catalogue, string $messageDomain): MessageCatalogue
    {
        //We only want a subcatalogue for the domain we are editing
        $domainCatalogue = new MessageCatalogue($catalogue->getLocale(), [
            $messageDomain => $catalogue->all($messageDomain)
        ]);

        //Copy over the metadata
        foreach ($catalogue->getMetadata('', $messageDomain) ?? [] as $key => $metadata) {
            $domainCatalogue->setMetadata($key, $metadata, $messageDomain);
        }

        //Copy over catalogue metadata
        foreach ($catalogue->getCatalogueMetadata('', $messageDomain) ?? [] as $key => $metadata) {
            $domainCatalogue->setCatalogueMetadata($key, $metadata);
        }

        return $domainCatalogue;
    }

    /**
     * Edit the given message to new value. If it is not existing yet, it will be created.
     * @param  string  $messageId The ID of the message to edit
     * @param  string  $messageLocale The locale of the message
     * @param  string  $messageDomain The domain of the message
     * @param  string  $newMessage The new content of the message
     * @return void
     */
    public function editMessage(string $messageId, string $messageLocale, string $messageDomain, string $newMessage): void
    {
        //Get the catalogue for the message locale (we cannot use normal translator service, as the catalogue is
        //cached there and contains no metadata. Also it contains the bundle strings, we do not need)
        $catalogue = $this->loadCurrentTranslations($messageLocale);

        if ($this->useIntl) {
            $messageDomain = sprintf('%s+intl-icu', $messageDomain);
        }

        //We only want a subcatalogue for the domain we are editing
        $domainCatalogue = $this->getDomainOnlyCatalogue($catalogue, $messageDomain);

        //Apply our message change
        $domainCatalogue->set($messageId, $newMessage, $messageDomain);

        $writeOptions = [
            'path' => $this->translationPath,
        ];

        if (in_array($this->format, ['xlf', 'xliff'], true)) {
            $writeOptions['xliff_version'] = $this->xliffVersion;
        }

        //Apply the writer options array
        $writeOptions = array_merge($writeOptions, $this->writerOptions);

        //Write the catalogue
        $this->translationWriter->write($domainCatalogue, $this->format, $writeOptions);
    }
}
