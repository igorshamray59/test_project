<?php

namespace App\Models;

use CodeIgniter\Model;

class LogModel extends Model
{
    protected $table            = 'action_logs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'file_id', 'filename', 'action', 'user_ip', 'user_agent', 'details', 'created_at'
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    // Логирует действие с файлом
    public function logAction($fileId, $filename, $action, $details = '')
    {
        $data = [
            'file_id'    => $fileId,
            'filename'   => $filename,
            'action'     => $action,
            'user_ip'    => $this->getUserIP(),
            'user_agent' => $this->getUserAgent(),
            'details'    => $details
        ];

        return $this->insert($data);
    }

    // Получает IP пользователя
    private function getUserIP()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        }
    }

    // Получает User Agent
    private function getUserAgent()
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    }

    // Получает журнал действий с пагинацией
    public function getLogs($perPage = 20, $page = 1)
    {
        return $this->orderBy('created_at', 'DESC')
                    ->paginate($perPage, 'default', $page);
    }

    // Получает pager для журнала
    public function getLogsPager()
    {
        return $this->pager;
    }

    //Получает журнал действий для конкретного файла
    public function getFileLogs($fileId, $perPage = 10, $page = 1)
    {
        return $this->where('file_id', $fileId)
                    ->orderBy('created_at', 'DESC')
                    ->paginate($perPage, 'default', $page);
    }
}