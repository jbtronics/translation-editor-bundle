<?php

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes): void {

    //Only register the routes if we are in the dev environment
    if ($routes->env() !== 'dev') {
        return;
    }

    $routes->add('jbtronics_translation_editor_edit', '/_profiler/translations/edit')
        ->controller([\Jbtronics\TranslationEditorBundle\Controller\SubmissionController::class, 'editMessage'])
        ->methods(['POST'])
    ;

};