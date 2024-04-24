<?php

use Moloni\Admin\Dispatcher;
use Moloni\Installer\Installer;

if (!defined('MOLONI_ADDON_VERSION')) {
    define('MOLONI_ADDON_VERSION', '8.0.1');
}

function moloni_config()
{
    return [
        "name" => "Moloni",
        "description" => "Facturação fácil e automática? É para já!",
        "version" => MOLONI_ADDON_VERSION,
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

function moloni_upgrade($vars)
{
    try {
        require_once __DIR__ . '/vendor/autoload.php';
        return Installer::update();
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

    if (isset($_GET['ajax']) && filter_var($_GET['ajax'], FILTER_VALIDATE_BOOLEAN)) {
        $dispatcher->dispatchAjax();
    } else {
        $dispatcher->dispatch($vars);
    }
}
