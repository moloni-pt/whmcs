<?php

use Moloni\Tools;
use Moloni\Error;
use Moloni\Model\WhmcsDB;

?>
<section id="moloni">
    <?php
    $activeTab = '';

    include MOLONI_TEMPLATE_PATH . 'Blocks/header.php';
    include MOLONI_TEMPLATE_PATH . 'Blocks/messages.php';
    include MOLONI_TEMPLATE_PATH . 'Blocks/navbar.php';
    ?>

    <div class="row">
        <div class="col s12">
            <div>
                <?php $orders = WhmcsDB::getAllOrders(); ?>

                <table class='highlight display moloniTable'>
                    <thead>
                    <tr>
                        <th class='center-align' data-field="id">Número</th>
                        <th data-field="name">Cliente</th>
                        <th data-field="email">Email</th>
                        <th data-field="date">Data</th>
                        <th data-field="status">Estado</th>
                        <th data-field="total">Total</th>
                        <th data-field="acts" style="width:190px !important;">
                            <div>Ações</div>
                        </th>
                    </tr>
                    </thead>

                    <tbody>
                    <?php
                    foreach ($orders as $order) {
                        echo "<tr>";
                        $orderNumero = (!empty($order['invoice']->invoicenum)) ? $order['invoice']->invoicenum : $order['invoice']->id;
                        echo "<td class='center-align'><a style='text-transform: none' class='waves-effect waves-light btn blue white-text' href='invoices.php?action=edit&id=" . $order['invoice']->id . "' target='_BLANK'>" . $orderNumero . "</a></td>";
                        echo "<td><a href='clientssummary.php?userid=" . $order['client']->id . "' target='_BLANK'>" . $order['client']->firstname . " " . $order['client']->lastname . "</a></td>";
                        echo "<td>" . $order['client']->email . "</td>";
                        echo "<td>" . $order['invoice']->date . "</td>";
                        echo "<td>" . (($order['invoice']->status == 'Paid') ? 'Pago' : 'Não Pago') . "</td>";
                        echo "<td>" . $order['currency']->prefix . ($order['invoice']->subtotal + $order['invoice']->tax + $order['invoice']->tax2) . $order['currency']->suffix . "</td>";
                        echo "
							<td class='acoesBtnMoloni'>
							    <a class='waves-effect waves-light btn' style='background:#275F96' href='" . Tools::genURL("invoice", "gen&id=" . $order['invoice']->id) . "'><i class='material-icons'>add_circle_outline</i></a>
							    <a class='waves-effect waves-light btn red btn deleteDoc' href='" . Tools::genURL("invoice", "delete&id=" . $order['invoice']->id) . "'><i class='material-icons'>delete</i></a>
							</td>
						";
                        echo "</tr>";
                    }
                    ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</section>

<?php include MOLONI_TEMPLATE_PATH . 'Blocks/footer.php'; ?>

<script>
    $(document).ready(function() {
        var index = 0;
        var order = 'asc';
        if (localStorage.getItem("encIndex") !== null && localStorage.getItem("encOrder") !== null) {
            switch (localStorage.getItem("encIndex")) {
                case 'id':
                    index = 0;
                    break;
                case 'name':
                    index = 1;
                    break;
                case 'email':
                    index = 2;
                    break;
                case 'date':
                    index = 3;
                    break;
                case 'status':
                    index = 4;
                    break;
                case 'total':
                    index = 5;
                    break;
                case 'acts':
                    index = 6
                    break;
            }
            if (localStorage.getItem("encOrder") === 'descending') {
                order = 'desc';
            }
        }

        $('.highlight').dataTable({
            "aaSorting": [[index, order]],
            "sPaginationType": "simple_numbers",
            "sDom": '<"top"<"MolSearch"f><"MolShowing"l>>rt<"bottom row"<"MolInfo col s6"i><"MolPagination col s6"p>><"clear">',
            "oLanguage": {
                "sLengthMenu": "_MENU_",
                "sZeroRecords": "Sem resultados encontrados",
                "sInfo": "A mostrar <b>_START_</b> - <b>_END_</b> de <b>_TOTAL_</b> encomendas",
                "sInfoEmpty": "Sem resultados para apresentar",
                "sInfoFiltered": "(Filtrados de _MAX_)",
                "sSearch": "",
                "sSearchPlaceholder": "Pesquisar...",
                "oPaginate": {
                    "sPrevious": "Anterior",
                    "sNext": "Seguinte",
                }
            }
        });

        function deselect(e) {
            $('.pop').slideFadeToggle(function() {
                e.removeClass('selected');
            });
        }

        $(function() {
            $('.check_error').on('click', function() {
                if ($(this).hasClass('selected')) {
                    deselect($(this));
                } else {
                    $(this).addClass('selected');
                    $('.pop').slideFadeToggle();
                }
                return false;
            });

            $('.close').on('click', function() {
                deselect($('.check_error'));
                return false;
            });
        });

        $.fn.slideFadeToggle = function(easing, callback) {
            return this.animate({opacity: 'toggle', height: 'toggle'}, 'fast', easing, callback);
        };

        //guardar ultima ordenacao
        $(".moloniTable th").on('click', function() {
            localStorage.setItem('encIndex', $(this).attr('data-field'));
            localStorage.setItem('encOrder', $(this).attr('aria-sort'));
        });

        //Mostrar ou esconder variáveis debug
        var verMais = $('#debugMoloniAPI');
        var divArrayApi = $('#showDebugMoloni');

        verMais.click(function() {
            if (divArrayApi.is(":visible")) {
                $('#showDebugMoloni').fadeOut(300);
                verMais[0].innerHTML = ' Ver mais';
            } else {
                $('#showDebugMoloni').fadeIn(300);
                verMais[0].innerHTML = ' Ver menos';
            }
        });
    });
</script>
