<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit0cbc7c3003ddf0c7b455a1801b5574dd
{
    public static $prefixLengthsPsr4 = array (
        'R' => 
        array (
            'Ristretto\\' => 10,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Ristretto\\' => 
        array (
            0 => __DIR__ . '/..' . '/runs-on-coffee/ristretto/src',
        ),
    );

    public static $classMap = array (
        'Docopt' => __DIR__ . '/..' . '/docopt/docopt/src/docopt.php',
        'Docopt\\Argument' => __DIR__ . '/..' . '/docopt/docopt/src/docopt.php',
        'Docopt\\BranchPattern' => __DIR__ . '/..' . '/docopt/docopt/src/docopt.php',
        'Docopt\\Command' => __DIR__ . '/..' . '/docopt/docopt/src/docopt.php',
        'Docopt\\Either' => __DIR__ . '/..' . '/docopt/docopt/src/docopt.php',
        'Docopt\\ExitException' => __DIR__ . '/..' . '/docopt/docopt/src/docopt.php',
        'Docopt\\Handler' => __DIR__ . '/..' . '/docopt/docopt/src/docopt.php',
        'Docopt\\LanguageError' => __DIR__ . '/..' . '/docopt/docopt/src/docopt.php',
        'Docopt\\LeafPattern' => __DIR__ . '/..' . '/docopt/docopt/src/docopt.php',
        'Docopt\\OneOrMore' => __DIR__ . '/..' . '/docopt/docopt/src/docopt.php',
        'Docopt\\Option' => __DIR__ . '/..' . '/docopt/docopt/src/docopt.php',
        'Docopt\\Optional' => __DIR__ . '/..' . '/docopt/docopt/src/docopt.php',
        'Docopt\\OptionsShortcut' => __DIR__ . '/..' . '/docopt/docopt/src/docopt.php',
        'Docopt\\Pattern' => __DIR__ . '/..' . '/docopt/docopt/src/docopt.php',
        'Docopt\\Required' => __DIR__ . '/..' . '/docopt/docopt/src/docopt.php',
        'Docopt\\Response' => __DIR__ . '/..' . '/docopt/docopt/src/docopt.php',
        'Docopt\\SingleMatch' => __DIR__ . '/..' . '/docopt/docopt/src/docopt.php',
        'Docopt\\Tokens' => __DIR__ . '/..' . '/docopt/docopt/src/docopt.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit0cbc7c3003ddf0c7b455a1801b5574dd::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit0cbc7c3003ddf0c7b455a1801b5574dd::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit0cbc7c3003ddf0c7b455a1801b5574dd::$classMap;

        }, null, ClassLoader::class);
    }
}