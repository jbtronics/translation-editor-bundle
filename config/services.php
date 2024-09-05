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
            '$translationReader' => service('translation.reader'),
            '$translationWriter' => service('translation.writer'),
            '$translationPath' => param('jbtronics.translation_editor.translations_path'),
            '$format' => param('jbtronics.translation_editor.format'),
            '$xliffVersion' => param('jbtronics.translation_editor.xliff_version'),
            '$writerOptions' => param('jbtronics.translation_editor.writer_options'),
            '$useIntl' => param('jbtronics.translation_editor.use_intl'),
        ]);

    //Register the controller
    $services->set(\Jbtronics\TranslationEditorBundle\Controller\SubmissionController::class)
        ->public()
        ->tag('controller.service_arguments')
        ->args([
            '$editor' => service('jbtronics.translations_editor.message_editor'),
            '$debugEnabled' => param('kernel.debug'),
        ])
    ;

};
