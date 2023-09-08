<?php

namespace Moloni\Installer;

use Exception;
use WHMCS\Database\Capsule;

class Installer
{
    private static $errors = [];

    /**
     * Run installation script
     *
     * @see https://developers.whmcs.com/addon-modules/installation-uninstallation/
     *
     * @return array|string[]
     */
    public static function install()
    {
        self::installMoloni();
        self::installMoloniConfigs();
        self::installMoloniInvoices();
        self::installMoloniLogs();
        self::setDefaultSettings();

        if (!empty(self::$errors)) {
            return self::$errors[0];
        }

        return [
            'status' => 'success',
            'description' => 'Addon Moloni instalado sucesso.'
        ];
    }

    /**
     * Run remove script
     *
     * @see https://developers.whmcs.com/addon-modules/installation-uninstallation/
     *
     * @return array|string[]
     */
    public static function remove()
    {
        self::removeMoloni();
        self::removeMoloniConfigs();

        return ['status' => 'success', 'description' => 'O Addon foi desinstalado com sucesso'];
    }

    /**
     * Run upgrade script
     *
     * @see https://developers.whmcs.com/addon-modules/upgrades/
     *
     * @return mixed|string[]
     */
    public static function update()
    {
        self::installMoloniLogs();

        if (!empty(self::$errors)) {
            return self::$errors[0];
        }

        return [
            'status' => 'success',
            'description' => 'Addon Moloni atualizado com sucesso.'
        ];
    }

    //        Queries        //

    private static function installMoloni()
    {
        try {
            if (!Capsule::schema()->hasTable('moloni')) {
                Capsule::schema()->create(
                    'moloni',
                    function ($table) {
                        $table->increments('id');
                        $table->text('access_token');
                        $table->text('refresh_token');
                        $table->integer('company_id');
                        $table->dateTime('date_login');
                        $table->dateTime('date_expire');
                    }
                );
            }
        } catch (Exception $e) {
            self::$errors = [
                'status' => "error",
                'description' => 'Falha ao criar tabela moloni: ' . $e->getMessage(),
            ];
        }
    }

    private static function installMoloniConfigs()
    {
        try {
            if (!Capsule::schema()->hasTable('moloni_configs')) {
                Capsule::schema()->create(
                    'moloni_configs',
                    function ($table) {
                        /** @var \Illuminate\Database\Schema\Blueprint $table */
                        $table->increments('id');
                        $table->text('label');
                        $table->text('name');
                        $table->text('description');
                        $table->text('value');
                    }
                );
            }
        } catch (Exception $e) {
            self::$errors = [
                'status' => "error",
                'description' => 'Falha ao criar tabela moloni_configs: ' . $e->getMessage(),
            ];
        }
    }

    private static function installMoloniInvoices()
    {
        # Correr queries de inicio
        # Invoice Status
        #	0 - Inserido como rascunho
        #	1 - Inserido fechado enviado
        #	2 - Inserido fechado
        #	3 - Inserido com erro
        #	4 - Não gerar documento
        # 	5 - Erro ao inserir

        try {
            if (!Capsule::schema()->hasTable('moloni_invoices')) {
                Capsule::schema()->create(
                    'moloni_invoices',
                    function ($table) {
                        $table->increments('id');
                        $table->integer('order_id');
                        $table->float('order_total');
                        $table->integer('invoice_id');
                        $table->date('invoice_date');
                        $table->integer('invoice_status');
                        $table->float('invoice_total');
                        $table->float('value');
                    }
                );
            }
        } catch (Exception $e) {
            self::$errors = [
                'status' => "error",
                'description' => 'Falha ao criar tabela moloni_invoices: ' . $e->getMessage(),
            ];
        }
    }

    private static function installMoloniLogs()
    {
        try {
            if (!Capsule::schema()->hasTable('moloni_logs')) {
                Capsule::schema()->create(
                    'moloni_logs',
                    function ($table) {
                        $table->increments('id');
                        $table->text('log_level');
                        $table->integer('company_id');
                        $table->text('message');
                        $table->text('context');
                        $table->dateTime('created_at');
                    }
                );
            }
        } catch (Exception $e) {
            self::$errors = [
                'status' => "error",
                'description' => 'Falha ao criar tabela moloni_logs: ' . $e->getMessage(),
            ];
        }
    }

    private static function removeMoloni()
    {
        if (Capsule::schema()->hasTable('moloni')) {
            Capsule::schema()->dropIfExists('moloni');
        }
    }

    private static function removeMoloniConfigs()
    {
        if (Capsule::schema()->hasTable('moloni_configs')) {
            Capsule::schema()->dropIfExists('moloni_configs');
        }
    }

    //        Defaults        //

    private static function setDefaultSettings()
    {
        $options = [];
        $options[] = ["label" => "document_set", "name" => "Série de documentos", "description" => ""];
        $options[] = ["label" => "after_date", "name" => "Encomendas desde", "description" => ""];
        $options[] = ["label" => "after_date_doc", "name" => "Documentos desde", "description" => ""];
        $options[] = ["label" => "exemption_reason", "name" => "Razão de isenção", "description" => ""];
        $options[] = ["label" => "payment_method", "name" => "Método de pagamento", "description" => ""];
        $options[] = ["label" => "measure_unit", "name" => "Unidade de medida", "description" => ""];
        $options[] = ["label" => "maturity_date", "name" => "Prazo de vencimento", "description" => ""];
        $options[] = ["label" => "update_customer", "name" => "Atualizar cliente", "description" => ""];
        $options[] = ["label" => "document_status", "name" => "Estado do documento", "description" => ""];
        $options[] = ["label" => "invoice_auto", "name" => "Gerar automaticamente", "description" => ""];
        $options[] = ["label" => "email_send", "name" => "Enviar email", "description" => ""];
        $options[] = ["label" => "remove_tax", "name" => "Remover IVA", "description" => ""];
        $options[] = ["label" => "client_prefix", "name" => "Prefixo do cliente", "description" => ""];
        $options[] = ["label" => "product_prefix", "name" => "Prefixo do artigo", "description" => ""];
        $options[] = ["label" => "document_type", "name" => "Tipo de documento", "description" => ""];
        $options[] = ["label" => "at_category", "name" => "Tipo de artigo AT", "description" => ""];
        $options[] = ["label" => "custom_reference", "name" => "Campo customizado Ref Produto", "description" => ""];
        $options[] = ["label" => "custom_client", "name" => "Campo customizado NIF cliente", "description" => ""];

        $pdo = Capsule::connection()->getPdo();
        $pdo->beginTransaction();

        try {
            /** @noinspection SqlResolve */
            $statement = $pdo->prepare(
                'INSERT INTO moloni_configs (label, name, description) VALUES (:label, :name, :description)'
            );

            foreach ($options as $option) {
                $statement->execute(
                    [
                        ':label' => $option['label'],
                        ':name' => $option['name'],
                        ':description' => $option['description'],
                    ]
                );
            }

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();

            self::$errors = [
                'status' => "error",
                'description' => 'Falha ao inserir dados na tabela moloni_configs: ' . $e->getMessage(),
            ];
        }
    }
}
