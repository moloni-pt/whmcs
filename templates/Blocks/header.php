<?php

use Moloni\Tools;

?>

<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@100;300;400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" scope href="<?= Tools::getPublicUrl('compiled.min.css') ?>">

<div class="row">
    <div class="col">
        <a href='https://moloni.pt' target='_BLANK'>
            <img src='<?= Tools::getPublicUrl('img/logo.png') ?>' class='moloni-logo'>
        </a>
    </div>
    <div class="right">
        <div>
            <a href="<?= Tools::genURL('logout', '') ?>"
               class="waves-effect waves-light btn red logoutMoloni">
                <span>Sair</span>
                <i class='material-icons'>logout</i>
            </a>
        </div>
    </div>
</div>
