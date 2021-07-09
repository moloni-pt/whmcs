<?php

use Moloni\Tools;
use Moloni\Api\Settings\DocumentSets;
use Moloni\Api\GlobalSettings\TaxExemptions;
use Moloni\Api\Settings\MeasurementUnits;
use Moloni\Api\Settings\MaturityDates;
use Moloni\Api\Settings\PaymentMethods;
use Moloni\Model\WhmcsDB;

?>

<section id="moloni">

<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<link href='https://fonts.googleapis.com/css?family=Roboto:400,300,100,500,700' rel='stylesheet' type='text/css'>
<link rel="stylesheet" scope href="<?= Tools::getPublicUrl('compiled.css') ?>">

    <div class="row">
        <div class="col">
            <a href='https://moloni.com' target='_BLANK'>
                <img src='<?= Tools::getPublicUrl('logo.png') ?>' class='moloni-logo'>
            </a>
        </div>
        <div class="right">
            <div>
                <a href="addonmodules.php?module=moloni&action=logout" class="waves-effect waves-light btn red logoutMoloni">
                    <span>Sair</span>
                    <i class='material-icons'>logout</i>
                </a>
            </div>
        </div>
    </div>

    <div class="row menuMoloni">
        <div class="col">
            <a class="black-text" href="addonmodules.php?module=moloni">Faturação</a>
        </div>
        <div class="col">
            <a class="black-text" href="addonmodules.php?module=moloni&action=docs">Documentos</a>
        </div>
        <div class="col menuAtivo">
            <a class="black-text" href="addonmodules.php?module=moloni&action=config">Configuração</a>
        </div>
    </div>

    <div class="row">
      <div class="col s12" style='margin-top: 5px;'>
			<form method='POST' id='moloniOptions' action='<?php echo Tools::genURL("config", "save"); ?>'>
			<div class="row">
                <div class="seccaoTituloConfig">
                    <i class='material-icons'>keyboard_arrow_down</i>
                    <h5 class="configTitulo">Documentos</h5>
                </div>
                <div class="configMoloni">
                    <!-------------------------- Série de Documentos (from Moloni) ------------------------------>
                    <div class='input-field'>
                        <h5>Série de documentos</h5>
                        <select name='document_set'>
                            <option value='' disabled selected>Selecionar série de documentos a usar</option>
                             <?php
                                foreach(DocumentSets::getAll() as $key=>$docSet){
                                    echo "<option value='".$docSet['document_set_id']."' ".
                                          ((defined('DOCUMENT_SET') && $docSet['document_set_id'] == DOCUMENT_SET) ? "selected" : "").
                                         ">".$docSet['name'].
                                         ($docSet['for_invoice_receipt'] == TRUE ? " (FR)" : "").
                                         "</option>";
                                }
                             ?>
                        </select>
                    </div>


                    <!-------------------------- Tipo de Documento (from Moloni) ------------------------------>
                    <div class='input-field'>
                        <h5>Tipo de documento</h5>
                        <select name='document_type'>
                            <option value='' disabled selected>Tipo de documento a usar</option>
                            <option value='invoices' 		<?php echo ((defined('DOCUMENT_TYPE') && DOCUMENT_TYPE == 'invoices') ? "selected" : ""); ?>>Fatura</option>
                            <option value='invoiceReceipts' <?php echo ((defined('DOCUMENT_TYPE') && DOCUMENT_TYPE == 'invoiceReceipts') ? "selected" : ""); ?>>Fatura/Recibo</option>
                            <option value='estimates' 		<?php echo ((defined('DOCUMENT_TYPE') && DOCUMENT_TYPE == 'estimates') ? "selected" : ""); ?>>Orçamento</option>
                            <option value='billsOfLading' 	<?php echo ((defined('DOCUMENT_TYPE') && DOCUMENT_TYPE == 'billsOfLading') ? "selected" : ""); ?>>Guias de Transporte</option>
                        </select>
                    </div>


                    <!-------------------------- Estado do documento (rascunho/fechado) ------------------------------>
                    <div class='input-field'>
                        <h5>Estado do documento</h5>
                        <select name='document_status'>
                            <option value='' disabled selected>Estados do documento inserido no moloni</option>
                            <option value='0' <?php echo ((defined('DOCUMENT_STATUS') && DOCUMENT_STATUS == '0') ? "selected" : ""); ?>>Rascunho</option>
                            <option value='1' <?php echo ((defined('DOCUMENT_STATUS') && DOCUMENT_STATUS == '1') ? "selected" : ""); ?>>Fechado</option>
                        </select>
                    </div>
                </div>
			</div>

			<div class="row">
                <div class="seccaoTituloConfig">
                    <i class='material-icons'>keyboard_arrow_down</i>
                    <h5 class="configTitulo">Encomendas</h5>
                </div>
                <div class="configMoloni">
                    <!-------------------------- Encomendas por gerar desde dia X ------------------------------>
                    <div class='input-field'>
                        <h5>Encomendas desde</h5>
                        <input placeholder='Introduza data' type="date" class="datepicker" name='after_date' id='after_date' value='<?php if(defined('AFTER_DATE')) echo AFTER_DATE; ?>'>
                    </div>
                    <!-------------------------- Documentos gerados desde dia X ------------------------------>
                    <div class='input-field'>
                        <h5>Documentos desde</h5>
                        <input placeholder='Introduza data' type="date" class="datepicker" name='after_date_doc' id='after_date_doc' value='<?php if(defined('AFTER_DATE_DOC')) echo AFTER_DATE_DOC; ?>'>
                    </div>
                </div>
			</div>

			<div class="row">
                <div class="seccaoTituloConfig">
                    <i class='material-icons'>keyboard_arrow_down</i>
                    <h5 class="configTitulo">Valores por defeito</h5>
                </div>
                <div class="configMoloni">
                    <!-------------------------- Razão de isenção a ser usada quando o artigo não tem IVA (from Moloni) ------------------------------>
                    <div class='input-field'>
                        <h5>Razão de isenção</h5>
                        <select name='exemption_reason'>
                            <option value='' disabled selected>Razão de isenção a ser usada por defeito</option>
                             <?php
                                $getALl = TaxExemptions::getAll();
                                foreach($getALl as $key=>$value){

                                    echo "<option value='".$value['code']."' ".
                                          ((defined('EXEMPTION_REASON') && $value['code'] == EXEMPTION_REASON) ? "selected" : "").
                                         ">".$value['code']." - ".$value['name'].
                                         "</option>";
                                }
                             ?>
                        </select>
                    </div>

                    <!-------------------------- Unidade de medida a ser usada por defeito ao inserir artigos (from Moloni) ------------------------------>
                    <div class='input-field'>
                        <h5>Unidade de medida</h5>
                        <select name='measure_unit'>
                            <option value='' disabled selected>Unidade de medida a ser usada por defeito</option>
                             <?php
                                $getALl = MeasurementUnits::getAll();
                                foreach($getALl as $key=>$value){

                                    echo "<option value='".$value['unit_id']."' ".
                                          ((defined('MEASURE_UNIT') && $value['unit_id'] == MEASURE_UNIT) ? "selected" : "").
                                         ">".$value['name'].
                                         "</option>";
                                }
                             ?>
                        </select>
                    </div>

                    <!-------------------------- Prazo de vencimento (from Moloni) ------------------------------>
                    <div class='input-field'>
                        <h5>Prazo de vencimento</h5>
                        <select name='maturity_date'>
                            <option value='' disabled selected>Prazo de vencimento</option>
                             <?php
                                $getALl = MaturityDates::getAll();
                                foreach($getALl as $key=>$value){

                                    echo "<option value='".$value['maturity_date_id']."' ".
                                          ((defined('MATURITY_DATE') && $value['maturity_date_id'] == MATURITY_DATE) ? "selected" : "").
                                         ">".$value['name'] .
                                         "</option>";
                                }
                             ?>
                        </select>
                    </div>

                    <!-------------------------- Método de pagamento a ser usado por defeito (from Moloni) ------------------------------>
                    <div class='input-field'>
                        <h5>Método de pagamento</h5>
                        <select name='payment_method'>
                            <option value='' disabled selected>Método de pagamento a ser usado por defeito</option>
                             <?php
                                $getALl = PaymentMethods::getAll();
                                foreach($getALl as $key=>$value){

                                    echo "<option value='".$value['payment_method_id']."' ".
                                          ((defined('PAYMENT_METHOD') && $value['payment_method_id'] == PAYMENT_METHOD) ? "selected" : "").
                                         ">".$value['name'].
                                         "</option>";
                                }
                             ?>
                        </select>
                    </div>

                    <!-------------------------- Tipo de artigo (From AT) ------------------------------>
                    <div class='input-field'>
                        <h5>Tipo de artigo</h5>
                        <select name='at_category'>
                            <option value='' disabled selected>Tipo de artigo</option>
                            <option value='SS' 	<?php echo ((defined('AT_CATEGORY') && AT_CATEGORY == 'SS') ? "selected" : ""); ?>>Serviço (S/ Stock)</option>
                            <option value='M' 	<?php echo ((defined('AT_CATEGORY') && AT_CATEGORY == 'M') ? "selected" : ""); ?>>Mercadorias</option>
                            <option value='P' 	<?php echo ((defined('AT_CATEGORY') && AT_CATEGORY == 'P') ? "selected" : ""); ?>>Matérias-primas, subsidiárias e de consumo</option>
                            <option value='A' 	<?php echo ((defined('AT_CATEGORY') && AT_CATEGORY == 'A') ? "selected" : ""); ?>>Produtos acabados e intermédios</option>
                            <option value='S' 	<?php echo ((defined('AT_CATEGORY') && AT_CATEGORY == 'S') ? "selected" : ""); ?>>Subprodutos, desperdícios e refugos</option>
                            <option value='T' 	<?php echo ((defined('AT_CATEGORY') && AT_CATEGORY == 'T') ? "selected" : ""); ?>>Produtos e trabalhos em curso</option>
                        </select>
                    </div>

                    <!-------------------------- Actualizar dados do cliente caso já exista (Sim/Não) ------------------------------>
                    <div class='input-field'>
                        <input type="hidden" name="update_customer" value="0" />
                        <input id="update_customer" name="update_customer" type="checkbox" value="1" class="filled-in" <?php echo ((defined('UPDATE_CUSTOMER') && UPDATE_CUSTOMER == '1') ? "checked='checked'" : ""); ?> />
                        <label for="update_customer">Atualizar cliente</label>
                    </div>
                </div>
			</div>

			<div class="row">
                <div class="seccaoTituloConfig">
                    <i class='material-icons'>keyboard_arrow_down</i>
                    <h5 class="configTitulo">Automatização</h5>
                </div>
                <div class="configMoloni">
                    <!-------------------------- Gerar documento automaticamente quando é pago (Sim/Não) ------------------------------>
                    <div class='input-field checkboxConfigMoloni'>
                        <input type="hidden" name="invoice_auto" value="0" />
                        <input id="invoice_auto" name="invoice_auto" type="checkbox" value="1" class="filled-in" <?php echo ((defined('INVOICE_AUTO') && INVOICE_AUTO == '1') ? "checked='checked'" : ""); ?> />
                        <label for="invoice_auto">Gerar documento automaticamente</label>
                    </div>

                    <!-------------------------- Enviar email ao cliente quando é gerado o documento fechado (Sim/Não) ------------------------------>
                    <div class='input-field checkboxConfigMoloni'>
                        <input type="hidden" name="email_send" value="0" />
                        <input id="email_send" name="email_send" type="checkbox" value="1" class="filled-in" <?php echo ((defined('EMAIL_SEND') && EMAIL_SEND == '1') ? "checked='checked'" : ""); ?> />
                        <label for="email_send">Enviar email automaticamente (documento fechado)</label>
                    </div>

                    <!-------------------------- Remover taxa IVA dos produtos (Sim/Não) ------------------------------>
                    <div class='input-field checkboxConfigMoloni'>
                        <input type="hidden" name="remove_tax" value="0" />
                       <input id="remove_tax" name="remove_tax" type="checkbox" value="1" class="filled-in" <?php echo ((defined('REMOVE_TAX') && REMOVE_TAX == '1') ? "checked='checked'" : ""); ?> />
                       <label for="remove_tax">Remover IVA aos produtos quando gerar o documento</label>
                    </div>

                    <!-------------------------- Campo customizado dos produtos ------------------------------>
                    <div class='input-field'>
                        <h5>Referência customizada produtos</h5>
                        <select name='custom_reference'>
                            <option value='' disabled selected>Campo customizado a ser usado como referência de produto</option>
                            <option value='' >Não usar custom field</option>
                            <?php
                            $getAll = WhmcsDB::getCustomFieldProduct();
                            foreach($getAll as $key=>$value){

                                echo "<option value='".$value->fieldname."' ".
                                    ((defined('CUSTOM_REFERENCE') && $value->fieldname == CUSTOM_REFERENCE) ? "selected" : "").
                                    ">".$value->fieldname.
                                    "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <!-------------------------- Campo customizado NIF clientes ------------------------------>
                    <div class='input-field'>
                        <h5>NIF customizado cliente</h5>
                        <select name='custom_client'>
                            <option value='' disabled selected>Campo customizado a ser usado como NIF de cliente</option>
                            <option value='' >Não usar custom field</option>
                            <?php
                            $getAll = WhmcsDB::getCustomFieldClient();
                            foreach($getAll as $key=>$value){

                                echo "<option value='".$value->fieldname."' ".
                                    ((defined('CUSTOM_CLIENT') && $value->fieldname == CUSTOM_CLIENT) ? "selected" : "").
                                    ">".$value->fieldname.
                                    "</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
			</div>
			</form>
          <div>
              <a class="waves-effect waves-light btn saveConfigMoloni" onclick='$( "#moloniOptions" ).submit();'>Guardar</a>
          </div>
      </div>
</div>

</section>

<script type="text/javascript" src="<?= Tools::getPublicUrl('materialize/js/materialize.min.js') ?>"></script>

<script>

$(document).ready(function() {
    $('#moloni select').material_select();
	
	$('.datepicker').pickadate({
		selectMonths: true, // Creates a dropdown to control month
		selectYears: 15, // Creates a dropdown of 15 years to control year
		formatSubmit: "yyyy-mm-dd",
		format: 'yyyy-mm-dd',
	  });

    $('.datepicker').on('mousedown',function(event){ event.preventDefault(); })

	$('#moloni .caret').empty();

    $(document).on("click",".seccaoTituloConfig", function () {
        var proximoDiv = $(this).next();
        if((proximoDiv.is(":visible")) == true){
            $(this).find(">:first-child")[0].innerHTML = "keyboard_arrow_down";
        } else {
            $(this).find(">:first-child")[0].innerHTML = "keyboard_arrow_up";
        }
        proximoDiv.toggle();
    });
  });

</script>
