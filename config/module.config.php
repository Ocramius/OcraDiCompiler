<?php
return array(
    'ocra_di_compiler' => array(
        // Filename where the compiled instantiation code will be written
        'compiled_di_instantiator_filename' => 'data/compiled_di_instantiator.php',
        // Filename where the compiled Di definitions will be written
        'compiled_di_definitions_filename' => 'data/compiled_di_definitions.php',
    ),

    'service_manager' => array(
        'factories' => array(
            'DependencyInjector'      => 'OcraDiCompiler\\Mvc\\Service\\DiFactory',
            'ControllerLoader'        => 'OcraDiCompiler\\Mvc\\Service\\ControllerLoaderFactory',
            'ViewHelperManager'       => 'OcraDiCompiler\Mvc\Service\ViewHelperManagerFactory',
            'ControllerPluginManager' => 'OcraDiCompiler\Mvc\Service\ControllerPluginManagerFactory',
        ),
    ),
);