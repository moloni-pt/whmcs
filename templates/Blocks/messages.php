<?php

use Moloni\Error;

if (Error::$exists) {
    if (!empty(Error::$success)) {
        $echoSuccess = "<div class='alert alert-success'>";
        $echoSuccess .= Error::$success['message'];

        if (!empty(Error::$success['moloniURL'])) {
            $echoSuccess .= " | <a target='_blank' rel='noopener noreferrer' href='" . Error::$success['moloniURL'] . "' style='cursor:pointer'> Consultar aqui documento no Moloni</a>";
        }

        if (!empty(Error::$success['downloadURL'])) {
            $echoSuccess .= " | <a target='_blank' rel='noopener noreferrer' href='" . Error::$success['downloadURL'] . "' style='cursor:pointer'> Fazer download de documento aqui</a>";
        }

        echo $echoSuccess . "</div>";
    }

    if (!empty(Error::$warning)) {
        echo "<div class='alert alert-warning'>" . Error::$warning['message'] . "</div>";
    }

    if (!empty(Error::$error)) {
        $echoError = "<div class='alert alert-danger'>";
        $echoError .= Error::$error['where'] . " - " . Error::$error['message'];

        if (!empty(Error::$error['data'])) {
            $echoError .= " | <a id='debugMoloniAPI' style='cursor:pointer'> Ver mais</a>";
        }

        echo $echoError . '</div>';

        if (!empty(Error::$error['data'])) {
            echo '<div id="showDebugMoloni" style="display:none">';
            echo '<pre class="alert alert-warning">';
            print_r(Error::$error['data']);
            echo '</pre>';
            echo '</div>';
        }
    }
}
