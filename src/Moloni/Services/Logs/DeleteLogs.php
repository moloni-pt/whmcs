<?php

namespace Moloni\Services\Logs;

use Moloni\Facades\LoggerFacade;
use WHMCS\Database\Capsule;

class DeleteLogs
{
    private $since;

    public function __construct($since = '')
    {
        if (empty($since)) {
            $since = date('Y-m-d H:i:s', strtotime("-1 week"));
        }

        $this->since = $since;
    }

    public function run()
    {
        Capsule::table('moloni_logs')
            ->where('created_at', '<', $this->since)
            ->delete();
    }

    public function saveLog()
    {
        LoggerFacade::info('Registos antigos eliminados com sucesso.', [
            'tag' => 'service:logs:delete',
            'since' => $this->since
        ]);
    }
}
