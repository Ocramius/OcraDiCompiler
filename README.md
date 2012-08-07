# OcraDiCompiler

A compiler module to make `Zend\Di\Di` based applications blazing fast!

## Requirements

 -  [ZendFramework 2](https://github.com/zendframework/zf2).
 -  Any application similar to the
    [ZendSkeletonApplication](https://github.com/zendframework/ZendSkeletonApplication).

## Installation

 1.  Add `"ocramius/ocra-di-compiler": "dev-master"` to your `composer.json`
 2.  Run `php composer.phar install`
 3.  Enable the module in your `config/application.config.php` by adding `OcraDiCompiler` to `modules`

## Functionality

OcraDiCompiler interacts with the bootstrap process of your application by trying to write a compiled `Zend\Di\Di` class
into your `data` directory.
The compiled Di container will be available after the `bootstrap` event of your application. Before that, any attempt
to access Di will fetch the default one.
You can customize where the compiled Di class will be written (and from where it will be read) by overriding the values
in `config/module.config.php` in your own config file in the `config/autoload` directory.

## Performance comparison

Given a default setup of an application with `doctrine/DoctrineORMModule` enabled and following class:

```php
<?php
class MyClass
{
    public function __construct(\Doctrine\ORM\EntityManager $em)
    {
    }
}
```

following code

```php
<?php
$serviceManager->get('Application')->bootstrap();
$serviceManager->get('Di')->get('MyClass');
```

should be noticeably faster (please leave me your feedback and your personal results!).

## Credits

Many thanks to [Sascha Oliver Prolic](https://github.com/prolic/), who wrote the implementation of the `DiProxyGenerator`
and patiently waited for me to get this module implemented