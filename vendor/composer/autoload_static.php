<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInite71ca3355551cc6df0c18f99f57455fb
{
    public static $prefixLengthsPsr4 = array (
        'W' => 
        array (
            'WPD\\BeaverPopups\\' => 17,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'WPD\\BeaverPopups\\' => 
        array (
            0 => __DIR__ . '/../..' . '/app',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInite71ca3355551cc6df0c18f99f57455fb::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInite71ca3355551cc6df0c18f99f57455fb::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
