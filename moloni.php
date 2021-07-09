<?php

use Moloni\Admin\Dispatcher;
use Moloni\Installer\Installer;

function moloni_config()
{
    return [
        "name" => "Moloni",
        "description" => "Facturação fácil e automática? É para já!",
        "version" => "7.1",
        "author" => "Moloni",
    ];
}

function moloni_activate()
{
    try {
        require_once __DIR__ . '/vendor/autoload.php';
        return Installer::install();
    } catch (Exception $exception) {
        echo $exception->getMessage();
        exit;
    }
}

function moloni_deactivate()
{
    try {
        require_once __DIR__ . '/vendor/autoload.php';
        return Installer::remove();
    } catch (Exception $exception) {
        echo $exception->getMessage();
        exit;
    }
}

function moloni_output($vars)
{
    define('MOLONI_TEMPLATE_PATH', __DIR__ . '/templates/');
    define('MOLONI_PUBLIC_PATH', __DIR__ . '/public/');
    require_once __DIR__ . '/vendor/autoload.php';

    $dispatcher = new Dispatcher();
    $dispatcher->dispatch($vars);
}
