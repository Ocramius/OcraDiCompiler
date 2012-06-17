<?php
return array(
    'ocra_di_compiler' => array(
        // Set to true if you want the module to attempt to generate a compiled Di automatically
        'auto_generate'         => false,
        // Disable if you don't want the module to override the default Di factory
        'override_di_factory'   => true,
        // Class name for the compiled Di
        'compiled_di_classname' => 'CompiledDi',
        // Namespace for the compiled class
        'compiled_di_namespace' => 'OcraDiCompiler\\__GC__',
        // Filename where the compiled code will be written
        'compiled_di_filename' => 'data/CompiledDi.php',
    ),
);