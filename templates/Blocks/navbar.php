<?php

use Moloni\Tools;

if (!isset($activeTab)) {
    $activeTab = '';
}

?>

<div class="row menuMoloni">
    <div class="col <?= $activeTab === '' ? 'menuAtivo' : '' ?>">
        <a class="black-text" href="<?= Tools::genURL('', '') ?>">
            Faturação
        </a>
    </div>
    <div class="col <?= $activeTab === 'docs' ? 'menuAtivo' : '' ?>">
        <a class="black-text" href="<?= Tools::genURL('docs', '') ?>">
            Documentos
        </a>
    </div>
    <div class="col <?= $activeTab === 'config' ? 'menuAtivo' : '' ?>">
        <a class="black-text" href="<?= Tools::genURL('config', '') ?>">
            Configuração
        </a>
    </div>
    <div class="col <?= $activeTab === 'logs' ? 'menuAtivo' : '' ?>">
        <a class="black-text" href="<?= Tools::genURL('logs', '') ?>">
            Registos
        </a>
    </div>
</div>
