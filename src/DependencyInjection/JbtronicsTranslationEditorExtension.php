<?php

declare(strict_types=1);


namespace Jbtronics\TranslationEditorBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class JbtronicsTranslationEditorExtension extends Extension
{

    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../config'));
        $loader->load('services.php');

        //Retrieve the configuration for this bundle and process it
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('jbtronics.translation_editor.translations_path', $config['translations_path']);
        $container->setParameter('jbtronics.translation_editor.format', $config['format']);
        $container->setParameter('jbtronics.translation_editor.xliff_version', $config['xliff_version']);
        $container->setParameter('jbtronics.translation_editor.writer_options', $config['writer_options']);
        $container->setParameter('jbtronics.translation_editor.use_intl', $config['use_intl']);
    }
}
