<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticIniteaa3c3e3067a6e11ebd6a167fced14c4
{
    public static $prefixLengthsPsr4 = array (
        'L' => 
        array (
            'LupaSearch\\LupaSearchPlugin\\' => 28,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'LupaSearch\\LupaSearchPlugin\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticIniteaa3c3e3067a6e11ebd6a167fced14c4::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticIniteaa3c3e3067a6e11ebd6a167fced14c4::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticIniteaa3c3e3067a6e11ebd6a167fced14c4::$classMap;

        }, null, ClassLoader::class);
    }
}
