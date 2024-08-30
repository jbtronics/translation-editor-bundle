<?php

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
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

    $services->set('jbtronics.translations_editor.message_editor', \Jbtronics\TranslationEditorBundle\Service\MessageEditor::class)
        ->args([
            '$translator' => service('translator'),
            '$translationWriter' => service('translation.writer'),
            '$translationPath' => param('translator.default_path'),
        ]);

    //Register the controller
    $services->set(\Jbtronics\TranslationEditorBundle\Controller\SubmissionController::class)
        ->public()
        ->tag('controller.service_arguments')
        ->args([
            '$editor' => service('jbtronics.translations_editor.message_editor'),
        ])
    ;

};