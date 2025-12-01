<?php

namespace App\Controllers;

use App\Models\FileModel;
use App\Models\LogModel;

class LogsController extends BaseController
{
    protected $fileModel;
    protected $logModel;
    protected $allowedTypes = [
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/octet-stream'
    ];
    protected $allowedExtensions = ['xls', 'xlsx'];

    public function __construct()
    {
        $this->fileModel = new FileModel();
        $this->logModel = new LogModel();
    }

	// Методы для просмотра журнала действий проведенных с файлами
    public function logs()
    {
        $perPage = 20;
        $currentPage = $this->request->getGet('page') ?? 1;

        $data = [
            'logs' => $this->logModel->getLogs($perPage, $currentPage),
            'pager' => $this->logModel->getLogsPager()
        ];

        return view('logs', $data);
    }

	public function fileLogs($fileId)
	{
		$file = $this->fileModel->find($fileId);
		if (!$file) {
			return redirect()->back()->with('error', 'Файл не найден');
		}

		$perPage = 10;
		$currentPage = $this->request->getGet('page') ?? 1;

		$data = [
			'file' => $file,
			'logs' => $this->logModel->getFileLogs($fileId, $perPage, $currentPage),
			'pager' => $this->logModel->pager // Исправлено - используем pager из модели
		];

		return view('file_logs', $data);
	}
}