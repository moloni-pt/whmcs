<?php

use Moloni\Api\Companies;
use Moloni\Tools;

$companies = Companies::getAll();

?>

<section id="moloni" class="companySelect">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" scope href="<?= Tools::getPublicUrl('compiled.min.css') ?>">

    <div class="container">
        <div class="row center-align">
            <img src="<?= Tools::getPublicUrl('img/whmcs_logo.png') ?>" alt="">
        </div>
        <div class="row white">
            <div class="row center-align escolhaEmpresa">Escolha a sua empresa</div>
            <div class="row empresasMoloni">
                <?php foreach ($companies as $company) : ?>
                <div class="col" style="margin-right:2%">
                    <div class="card" onclick='window.location = "addonmodules.php?module=moloni&company_id=<?php echo $company['company_id']; ?>";'>
                        <div class="card-content">
                            <span><?php echo $company['name']; ?></span>
                            <span class="cardVat"><?php echo $company['vat']; ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<?php include MOLONI_TEMPLATE_PATH . 'Blocks/footer.php'; ?>
