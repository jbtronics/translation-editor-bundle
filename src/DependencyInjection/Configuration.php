<?php

declare(strict_types=1);


namespace Jbtronics\TranslationEditorBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('jbtronics_translation_editor');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode //@phpstan-ignore-line
            ->children()

            ->scalarNode('translations_path')->defaultValue('%translator.default_path%')->end()
            ->scalarNode('format')->defaultValue('xlf')->end()
            ->scalarNode('xliff_version')->defaultValue('2.0')->end()
            ->arrayNode('writer_options')->scalarPrototype()->defaultNull()->end()

            ->end();

        return $treeBuilder;
    }
}