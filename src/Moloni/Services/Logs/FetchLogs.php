<?php

namespace Moloni\Services\Logs;

use Moloni\Core\Storage;
use WHMCS\Database\Capsule;

class FetchLogs
{
    private $request;
    private $logList = [];
    private $logTotals = 0;

    public function __construct($request = [])
    {
        $this->request = $request;
    }

    public function run()
    {
        $this->fetchAllLogs();

        return [
            'data' => $this->logList,
            'recordsTotal' => $this->logTotals,
            'recordsFiltered' => $this->logTotals,
        ];
    }

    /**
     * Fetch logs paginated
     *
     * @see https://blog.whmcs.guru/development/whmcs-database-tips-tricks-queries-2/
     *
     * @return void
     */
    private function fetchAllLogs()
    {
        $capsule = Capsule::table('moloni_logs');
        $capsule->whereIn('company_id', [0, Storage::$MOLONI_COMPANY_ID ?: 0]);

        // Manual search
        $search = $this->request['search']['value'] ?: '';

        if (!empty($search)) {
            $capsule->where('message', 'like', "%$search%");
        }

        if (isset($this->request['order'][0]['dir'])) {
            $order = strtoupper($this->request['order'][0]['dir']);
        } else {
            $order = ' DESC';
        }

        $capsule->orderBy("created_at", $order);

        $this->logTotals = $capsule->count();

        // Lets limit results
        $offset = $this->request['start'] ?: 0;
        $length = $this->request['length'] ?: 10;

        $capsule
            ->offset($offset)
            ->limit($length);

        $this->logList = $capsule->get();
    }
}
