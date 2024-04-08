<?php

use Moloni\Tools;
use Moloni\Model\WhmcsDB;
use Moloni\Enums\DocumentType;
use Moloni\Api\Settings\DocumentSets;
use Moloni\Api\GlobalSettings\TaxExemptions;
use Moloni\Api\Settings\MeasurementUnits;
use Moloni\Api\Settings\MaturityDates;
use Moloni\Api\Settings\PaymentMethods;

?>

<section id="moloni">
    <?php
    $activeTab = 'config';

    include MOLONI_TEMPLATE_PATH . 'Blocks/header.php';
    include MOLONI_TEMPLATE_PATH . 'Blocks/navbar.php';
    ?>

    <div class="row">
        <div class="col s12">
            <form method='POST' id='moloniOptions' action='<?php echo Tools::genURL("config", "save"); ?>'>
                <div class="row">
                    <div class="seccaoTituloConfig">
                        <i class='material-icons'>keyboard_arrow_down</i>
                        <h5 class="configTitulo">Documentos</h5>
                    </div>

                    <div class="configMoloni">
                        <div class='input-field'>
                            <select name='document_set' id="document_set">
                                <option value='' disabled selected>Selecionar série de documentos a usar</option>
                                <?php $documentSets = DocumentSets::getAll(); ?>
                                <?php foreach ($documentSets as $key => $documentSet) : ?>
                                    <option value="<?= $documentSet['document_set_id'] ?>"
                                        <?= Tools::isSelected("DOCUMENT_SET", $documentSet['document_set_id']) ?>
                                    ><?= $documentSet['name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                            <label for="document_set">Série de documentos</label>
                        </div>


                        <!-------------------------- Tipo de Documento (from Moloni) ------------------------------>
                        <div class='input-field'>
                            <select name='document_type' id="document_type">
                                <option value='' disabled selected>Tipo de documento a usar</option>
                                <?php $documentTypes = DocumentType::getDocumentTypeForRender() ?>

                                <?php foreach ($documentTypes as $id => $label) : ?>
                                    <option value="<?= $id ?>" <?= Tools::isSelected("DOCUMENT_TYPE", $id) ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                            <label for="document_type">Tipo de documento</label>
                        </div>


                        <!------------------------ Estado do documento (rascunho/fechado) ---------------------------->
                        <div class='input-field'>
                            <select name='document_status' id="document_status">
                                <option value='' disabled selected>Estados do documento inserido no moloni</option>
                                <option value='0'
                                    <?= Tools::isSelected("DOCUMENT_STATUS", 0) ?>>Rascunho
                                </option>
                                <option value='1'
                                    <?= Tools::isSelected("DOCUMENT_STATUS", 1) ?>>Fechado
                                </option>
                            </select>
                            <label for="document_status">Estado do documento</label>
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
                            <input placeholder='Introduza data'
                                   name='after_date'
                                   id='after_date'
                                   class="input-field-date"
                                   type="date"
                                   value='<?= (defined('AFTER_DATE')) ? AFTER_DATE : '' ?>'>
                            <label for="after_date">Encomendas desde</label>
                        </div>

                        <!-------------------------- Documentos gerados desde dia X ------------------------------>
                        <div class='input-field'>
                            <input placeholder='Introduza data'
                                   name='after_date_doc'
                                   id='after_date_doc'
                                   class="input-field-date"
                                   type="date"
                                   value='<?= (defined('AFTER_DATE_DOC')) ? AFTER_DATE_DOC : '' ?>'>
                            <label for="after_date_doc">Documentos desde</label>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="seccaoTituloConfig">
                        <i class='material-icons'>keyboard_arrow_down</i>
                        <h5 class="configTitulo">Valores por defeito</h5>
                    </div>
                    <div class="configMoloni">
                        <div class='input-field'>
                            <select name='exemption_reason' id="exemption_reason">
                                <option value='' disabled selected>Razão de isenção a ser usada por defeito</option>
                                <?php
                                $getALl = TaxExemptions::getAll();
                                foreach ($getALl as $key => $value) : ?>
                                    <option value='<?= $value['code'] ?>'
                                        <?= Tools::isSelected("EXEMPTION_REASON", $value['code']) ?>
                                    ><?= $value['code'] . " - " . $value['name'] ?>
                                    </option>;
                                    }
                                <?php endforeach; ?>
                            </select>
                            <label for="exemption_reason">Razão de isenção</label>
                        </div>

                        <div class='input-field'>
                            <select name='measure_unit' id="measure_unit">
                                <option value='' disabled selected>Unidade de medida a ser usada por defeito</option>

                                <?php
                                $getALl = MeasurementUnits::getAll();
                                foreach ($getALl as $key => $value) : ?>
                                    <option value='<?= $value['unit_id'] ?>'
                                        <?= Tools::isSelected("MEASURE_UNIT", $value['unit_id']) ?>
                                    ><?= $value['name'] ?>
                                    </option>;
                                    }
                                <?php endforeach; ?>

                            </select>
                            <label for="measure_unit">Unidade de medida</label>
                        </div>

                        <!-------------------------- Prazo de vencimento (from Moloni) ------------------------------>
                        <div class='input-field'>
                            <select name='maturity_date' id="maturity_date">
                                <option value='' disabled selected>Prazo de vencimento</option>

                                <?php
                                $getALl = MaturityDates::getAll();
                                foreach ($getALl as $key => $value) : ?>
                                    <option value='<?= $value['maturity_date_id'] ?>'
                                        <?= Tools::isSelected("MATURITY_DATE", $value['maturity_date_id']) ?>
                                    ><?= $value['name'] ?>
                                    </option>;
                                    }
                                <?php endforeach; ?>

                            </select>
                            <label for="maturity_date">Prazo de vencimento</label>
                        </div>

                        <div class='input-field'>
                            <select name='payment_method' id="payment_method">
                                <option value='' disabled selected>Método de pagamento a ser usado por defeito</option>

                                <?php
                                $getALl = PaymentMethods::getAll();
                                foreach ($getALl as $key => $value) : ?>
                                    <option value='<?= $value['payment_method_id'] ?>'
                                        <?= Tools::isSelected("PAYMENT_METHOD", $value['payment_method_id']) ?>
                                    ><?= $value['name'] ?>
                                    </option>;
                                    }
                                <?php endforeach; ?>
                            </select>
                            <label for="payment_method">Método de pagamento</label>
                        </div>

                        <div class='input-field'>
                            <select name='at_category' id="at_category">
                                <option value='' disabled selected>Tipo de artigo</option>
                                <option value='SS'
                                    <?= Tools::isSelected("AT_CATEGORY", "SS") ?>
                                >Serviço (S/ Stock)
                                </option>
                                <option value='M'
                                    <?= Tools::isSelected("AT_CATEGORY", "M") ?>
                                >Mercadorias
                                </option>
                                <option value='P' <?= Tools::isSelected("AT_CATEGORY", "P") ?>
                                >Matérias-primas, subsidiárias e de consumo
                                </option>
                                <option value='A' <?= Tools::isSelected("AT_CATEGORY", "A") ?>
                                >Produtos acabados e intermédios
                                </option>
                                <option value='S' <?= Tools::isSelected("AT_CATEGORY", "S") ?>
                                >Subprodutos, desperdícios e refugos
                                </option>
                                <option value='T' <?= Tools::isSelected("AT_CATEGORY", "T") ?>
                                >Produtos e trabalhos em curso
                                </option>
                            </select>
                            <label for="at_category">Tipo de artigo</label>
                        </div>

                        <label class="input-field input-field-checkbox">
                            <input id="update_customer" name="update_customer" type="checkbox" value="1"
                                   class="filled-in"
                                <?= (defined('UPDATE_CUSTOMER') && UPDATE_CUSTOMER == '1') ? "checked='checked'" : "" ?>
                            />
                            <span>Atualizar cliente</span>
                        </label>
                    </div>
                </div>

                <div class="row">
                    <div class="seccaoTituloConfig">
                        <i class='material-icons'>keyboard_arrow_down</i>
                        <h5 class="configTitulo">Automatização</h5>
                    </div>

                    <div class="configMoloni">
                        <label class="input-field input-field-checkbox">
                            <input id="invoice_auto" name="invoice_auto" type="checkbox" value="1"
                                   class="filled-in"
                                <?= (defined('INVOICE_AUTO') && INVOICE_AUTO == '1') ? "checked='checked'" : "" ?>
                            />
                            <span>Gerar documento automaticamente</span>
                        </label>

                        <label class="input-field input-field-checkbox">
                            <input id="email_send" name="email_send" type="checkbox" value="1"
                                   class="filled-in"
                                <?= (defined('EMAIL_SEND') && EMAIL_SEND == '1') ? "checked='checked'" : "" ?>
                            />
                            <span>Enviar email automaticamente (documento fechado)</span>
                        </label>

                        <label class="input-field input-field-checkbox">
                            <input id="remove_tax" name="remove_tax" type="checkbox" value="1"
                                   class="filled-in"
                                <?= (defined('REMOVE_TAX') && REMOVE_TAX == '1') ? "checked='checked'" : "" ?>
                            />
                            <span>Remover IVA aos produtos quando gerar o documento</span>
                        </label>

                        <!-------------------------- Campo customizado dos produtos ------------------------------>
                        <div class='input-field'>
                            <select name='custom_reference' id="custom_reference">
                                <option value='' disabled selected>Campo customizado a ser usado como referência de
                                    produto
                                </option>
                                <option value=''>Não usar custom field</option>
                                <?php
                                $getALl = WhmcsDB::getCustomFieldProduct();
                                foreach ($getALl as $key => $value) : ?>
                                    <option value='<?= $value->fieldname ?>'
                                        <?= Tools::isSelected("CUSTOM_REFERENCE", $value->fieldname) ?>
                                    ><?= $value->fieldname ?>
                                    </option>;
                                    }
                                <?php endforeach; ?>
                            </select>
                            <label for="custom_reference">Referência customizada produtos</label>
                        </div>
                        <!-------------------------- Campo customizado NIF clientes ------------------------------>
                        <div class='input-field'>
                            <select name='custom_client' id="custom_client">
                                <option value='' disabled selected>Campo customizado a ser usado como NIF de cliente
                                </option>
                                <option value=''>Não usar custom field</option>
                                <?php
                                $getALl = WhmcsDB::getCustomFieldClient();
                                foreach ($getALl as $key => $value) : ?>
                                    <option value='<?= $value->fieldname ?>'
                                        <?= Tools::isSelected("CUSTOM_CLIENT", $value->fieldname) ?>
                                    ><?= $value->fieldname ?>
                                    </option>;
                                    }
                                <?php endforeach; ?>

                            </select>
                            <label for="custom_client">NIF customizado cliente</label>
                        </div>
                    </div>
                </div>
            </form>
            <div>
                <a class="waves-effect waves-light btn saveConfigMoloni"
                   onclick='$( "#moloniOptions" ).submit();'>Guardar</a>
            </div>
        </div>
    </div>
</section>

<?php include MOLONI_TEMPLATE_PATH . 'Blocks/footer.php'; ?>

<script>
    $(document).ready(function() {
        $('#moloni select').formSelect();
        $('#moloni .caret').empty();

        $(document).on("click", ".seccaoTituloConfig", function() {
            var proximoDiv = $(this).next();
            if ((proximoDiv.is(":visible"))) {
                $(this).find(">:first-child")[0].innerHTML = "keyboard_arrow_down";
            } else {
                $(this).find(">:first-child")[0].innerHTML = "keyboard_arrow_up";
            }
            proximoDiv.toggle();
        });
    });

</script>
