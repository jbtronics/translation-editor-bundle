<?php

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes): void {
    $routes->add('jbtronics_translation_editor_edit', '/_profiler/translations/edit')
        ->controller([\Jbtronics\TranslationEditorBundle\Controller\SubmissionController::class, 'editMessage'])
        //->methods(['POST'])
    ;

};