<?php

namespace App\Controllers;

use App\Models\FileModel;
use App\Models\LogModel;

class ReportsController extends BaseController
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

    // Методы для генерации отчетов

	public function exportExcel($id)
    {
        $file = $this->fileModel->find($id);
        
        if (!$file) {
            return redirect()->back()->with('error', 'Файл не найден');
        }

        // Получаем журнал действий для этого файла
        $logs = $this->logModel->where('file_id', $id)
                              ->orderBy('created_at', 'DESC')
                              ->findAll();

        $exportName = 'file_info_' . date('Y-m-d_H-i-s') . '_' . pathinfo($file['original_name'], PATHINFO_FILENAME) . '.xlsx';
        $exportPath = WRITEPATH . 'uploads/' . $exportName;

        if ($this->fileModel->exportFileInfoToExcel($file, $logs, $exportPath)) {
            // Логируем экспорт
            $this->logModel->logAction(
                $id,
                $file['original_name'],
                'export_excel',
                'Экспорт информации о файле'
            );

            return $this->response->download($exportPath, null)->setFileName($exportName);
        } else {
            return redirect()->back()->with('error', 'Ошибка при экспорте в Excel');
        }
    }

    public function generatePdf($id)
    {
        $file = $this->fileModel->find($id);
        
        if (!$file) {
            return redirect()->back()->with('error', 'Файл не найден');
        }

        // Получаем журнал действий для этого файла
        $logs = $this->logModel->where('file_id', $id)
                              ->orderBy('created_at', 'DESC')
                              ->findAll();

        $pdfName = 'file_info_' . date('Y-m-d_H-i-s') . '_' . pathinfo($file['original_name'], PATHINFO_FILENAME) . '.pdf';
        $pdfPath = WRITEPATH . 'uploads/' . $pdfName;

        if ($this->fileModel->generateFileInfoPdf($file, $logs, $pdfPath)) {
            // Логируем генерацию PDF
            $this->logModel->logAction(
                $id,
                $file['original_name'],
                'export_pdf',
                'Генерация отчета о файле'
            );

            return $this->response->download($pdfPath, null)->setFileName($pdfName);
        } else {
            return redirect()->back()->with('error', 'Ошибка при генерации PDF');
        }
    }

}