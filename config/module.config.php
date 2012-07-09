<?php
return array(
    'ocra_di_compiler' => array(
        // Class name for the compiled Di
        'compiled_di_classname' => 'CompiledDi',
        // Namespace for the compiled class
        'compiled_di_namespace' => 'OcraDiCompiler\\__GC__',
        // Filename where the compiled code will be written
        'compiled_di_filename' => 'data/CompiledDi.php',
        // Filename where the compiled Di definitions will be written
        'compiled_di_definitions_filename' => 'data/compiled_di_definitions.php',
    ),

    'service_manager' => array(
        'factories' => array(
            'DependencyInjector'      => 'OcraDiCompiler\\Service\\CompiledDiFactory',
            'ControllerLoader'        => 'OcraDiCompiler\\Service\\Mvc\\ControllerLoaderFactory',
            'ControllerPluginManager' => 'OcraDiCompiler\\Service\\Mvc\\ControllerPluginManagerFactory',
            'ViewHelperManager'       => 'OcraDiCompiler\\Service\\Mvc\\ViewHelperManagerFactory',
        ),
    ),
);