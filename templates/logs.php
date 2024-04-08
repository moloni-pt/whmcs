<?php

use Moloni\Tools;

?>

<section id="moloni">
    <?php
    $activeTab = 'logs';

    include MOLONI_TEMPLATE_PATH . 'Blocks/header.php';
    include MOLONI_TEMPLATE_PATH . 'Blocks/messages.php';
    include MOLONI_TEMPLATE_PATH . 'Blocks/navbar.php';
    ?>

    <div class="row">
        <div class="col s12" style='margin-top: 5px;'>
            <div>
                <table class='highlight display moloniTable' width="100%">
                    <thead>
                    <tr>
                        <th width="250px" data-field="id">Data</th>
                        <th width="200px" data-field="name">Nível</th>
                        <th data-field="documentset">Mensagem</th>
                        <th width="150px" data-field="date">Contexto</th>
                    </tr>
                    </thead>

                    <tbody>
                    <tr>
                        <td colspan="100%" class="dataTables_empty">
                            Aguarde, a obter dados...
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <div class="row">
        <a type="button"
           class="btn waves-effect waves-light red"
           href=" <?= Tools::genURL('logs', 'delete') ?>"
        >
            Apagar registos com mais de 1 semana
        </a>
    </div>

    <?php include MOLONI_TEMPLATE_PATH . 'Modals/logsContext.php'; ?>
</section>

<?php include MOLONI_TEMPLATE_PATH . 'Blocks/footer.php'; ?>

<script>
    $(document).ready(function() {
        var action = "<?= Tools::genURL('logs', 'getLogs') ?>";

        pt.moloni.Logs.init(action);
    });
</script>
