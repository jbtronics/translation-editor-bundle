<?php

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container) {

    $services = $container->services()
        ->defaults()->private();

    //We override the default translation data collector, so we can inject our own template
    $services->set('jbtronics.translations_editor.data_collector', \Symfony\Component\Translation\DataCollector\TranslationDataCollector::class)
        ->tag('data_collector', [
            'template' => '@JbtronicsTranslationEditor/profiler/main.html.twig',
            'id' => 'translation',
            'priority' => 255
        ])
        ->args([
            service('translator.data_collector')
        ])
    ;

};