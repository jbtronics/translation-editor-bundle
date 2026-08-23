<?php

declare(strict_types=1);


namespace Jbtronics\TranslationEditorBundle\Command;

use Jbtronics\TranslationEditorBundle\Service\MessageEditor;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'translation:touch-catalog',
    description: 'Reloads and rewrites the message catalogue for the given domain and locale, to update their auto generated message IDs.',
)]
final class TouchCatalogCommand extends Command
{
    public function __construct(
        private readonly MessageEditor $messageEditor,
        private readonly array $enabledLocales = [],
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('domain', InputArgument::REQUIRED, 'The translation domain to update')
            ->addArgument('locale', InputArgument::OPTIONAL, 'The locale of the catalogue to update')
            ->addOption('all-locales', 'a', InputOption::VALUE_NONE, 'Update the catalogue for all locales enabled in the application (via framework.enabled_locales), instead of a single one')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $domain = $input->getArgument('domain');
        $locale = $input->getArgument('locale');
        $allLocales = $input->getOption('all-locales');

        if ($allLocales && $locale !== null) {
            $io->error('You cannot pass a locale argument and use --all-locales at the same time.');
            return Command::INVALID;
        }

        if (!$allLocales && $locale === null) {
            $io->error('You must either pass a locale argument or use --all-locales.');
            return Command::INVALID;
        }

        if ($allLocales) {
            $locales = $this->enabledLocales;

            if ($locales === []) {
                $io->error('No locales are enabled in the application. Configure framework.enabled_locales or pass a locale argument instead.');
                return Command::FAILURE;
            }
        } else {
            $locales = [$locale];
        }

        foreach ($locales as $localeToUpdate) {
            $this->messageEditor->updateMessageIds($localeToUpdate, $domain);
            $io->writeln(sprintf('Updated message IDs for domain "%s" and locale "%s".', $domain, $localeToUpdate));
        }

        $io->success(sprintf('Updated message IDs for domain "%s" (%d locale(s)).', $domain, count($locales)));

        return Command::SUCCESS;
    }
}
