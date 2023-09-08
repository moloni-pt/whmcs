<?php

namespace Moloni\Logs;

use Moloni\Core\Storage;
use Moloni\Error;
use Psr\Log\AbstractLogger;
use WHMCS\Database\Capsule;

class Logger extends AbstractLogger
{
    public function log($level, $message, array $context = [])
    {
        $pdo = Capsule::connection()->getPdo();
        $pdo->beginTransaction();

        try {
            $statement = $pdo->prepare('
                INSERT INTO moloni_logs(log_level, company_id, message, context, created_at)
                VALUES (:log_level, :company_id, :message, :context, :created_at)
            ');

            $statement->execute([
                ':log_level' => $level,
                ':company_id' => empty(Storage::$MOLONI_COMPANY_ID) ? 0 : Storage::$MOLONI_COMPANY_ID,
                ':message' => $message,
                ':context' => json_encode($context),
                ':created_at' => date('Y-m-d H:i:s')
            ]);

            $pdo->commit();
            return true;
        } catch (\Exception $e) {
            Error::create('Logs', 'Erro ao inserir em moloni_logs:' . $e->getMessage());
            $pdo->rollBack();

            return false;
        }
    }
}
