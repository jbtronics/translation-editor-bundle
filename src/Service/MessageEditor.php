<?php

declare(strict_types=1);


namespace Jbtronics\TranslationEditorBundle\Service;

use Symfony\Component\Translation\Catalogue\TargetOperation;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Component\Translation\Writer\TranslationWriterInterface;

class MessageEditor
{
    public function __construct(
        private readonly TranslatorBagInterface $translator,
        private readonly TranslationWriterInterface $translationWriter,
        private readonly string $translationPath,
    )
    {
    }

    /**
     * Create a subcatalogue only containing the given domain
     * @param  MessageCatalogue  $catalogue
     * @param  string  $messageDomain
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

    public function editMessage(string $messageId, string $messageLocale, string $messageDomain, string $message): void
    {
        //Get the catalogue for the message locale
        $catalogue = $this->translator->getCatalogue($messageLocale);

        //We only want a subcatalogue for the domain we are editing
        $domainCatalogue = $this->getDomainOnlyCatalogue($catalogue, $messageDomain);

        //Apply our message change
        $domainCatalogue->set($messageId, $message, $messageDomain);

        $writeOptions = [
            'path' => $this->translationPath,
            'xliff_version' => '2.0',
        ];

        //Write the catalogue
        $this->translationWriter->write($domainCatalogue, 'xlf', $writeOptions);
    }
}