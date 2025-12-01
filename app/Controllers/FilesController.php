<?php

namespace App\Controllers;

use App\Models\FileModel;
use App\Models\LogModel;

class FilesController extends BaseController
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

    public function index()
    {
        $perPage = 10;
        $currentPage = $this->request->getGet('page') ?? 1;

        $data = [
            'files' => $this->fileModel->getFiles($perPage, $currentPage),
            'pager' => $this->fileModel->getPager()
        ];

        return view('index', $data);
    }

	// Загрузка файла
    public function upload()
    {
        if ($this->request->getMethod() === 'POST') {
            $file = $this->request->getFile('excel_file');
            
            if (!$file->isValid()) {
                return redirect()->back()->with('error', $file->getErrorString());
            }

            // Проверка типа файла
            if (!in_array($file->getClientMimeType(), $this->allowedTypes) || 
                !in_array($file->getClientExtension(), $this->allowedExtensions)) {
                return redirect()->back()->with('error', 'Недопустимый формат файла');
            }

            // Сохранение файла
            $newName = $file->getRandomName();
            $filePath = WRITEPATH . 'uploads/' . $newName;
            
            if (!$file->move(WRITEPATH . 'uploads/', $newName)) {
                return redirect()->back()->with('error', 'Ошибка при сохранении файла');
            }

            // Получаем информацию о файле
            $fileInfo = $this->fileModel->getFileInfo($filePath);

            // Сохраняем информацию о файле в БД
            $fileId = $this->fileModel->insert([
                'filename' => $newName,
                'original_name' => $file->getClientName(),
                'file_path' => $filePath,
                'file_size' => $file->getSize(),
                'row_count' => $fileInfo['row_count'],
                'column_count' => $fileInfo['column_count']
            ]);

            // Логируем загрузку
            $this->logModel->logAction(
                $fileId,
                $file->getClientName(),
                'upload',
                "Строк: {$fileInfo['row_count']}, Столбцов: {$fileInfo['column_count']}"
            );

            return redirect()->to('/')->with('success', 
                "Файл успешно загружен. Столбцов: {$fileInfo['column_count']}, Строк: {$fileInfo['row_count']}");
        }

        return view('upload');
    }
	// Просмотр файла 
    public function view($id)
    {
        $file = $this->fileModel->find($id);
        
        if (!$file) {
            return redirect()->back()->with('error', 'Файл не найден');
        }

        $perPage = 5;
        $currentPage = $this->request->getGet('page') ?? 1;

        // Получаем данные файла
        $fileData = $this->fileModel->getFileData($file['file_path'], $perPage, $currentPage);
        
        $data = [
            'file' => $file,
            'fileData' => $fileData,
            'totalRows' => $file['row_count'],
            'columnCount' => $file['column_count'],
            'currentPage' => $currentPage,
            'perPage' => $perPage
        ];
        
        return view('view', $data);
    }

	// Скачивание файла
	public function download($id)
    {
        $file = $this->fileModel->find($id);
        
        if (!$file) {
            return redirect()->back()->with('error', 'Файл не найден');
        }

        return $this->response->download($file['file_path'], null)
            ->setFileName($file['original_name']);
    }

	// Удаление файла
    public function delete($id)
    {
        $file = $this->fileModel->find($id);
        
        if (!$file) {
            return redirect()->back()->with('error', 'Файл не найден');
        }

        // Логируем удаление файла
        $this->logModel->logAction(
            $id,
            $file['original_name'],
            'delete_file',
            "Удален файл: {$file['original_name']}"
        );

        // Удаляем физический файл
        if (file_exists($file['file_path'])) {
            unlink($file['file_path']);
        }

        // Удаляем запись из БД
        $this->fileModel->delete($id);

        return redirect()->to('/')->with('success', 'Файл успешно удален');
    }

    // Методы для изменения строк файла

    public function addRow($fileId)
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(405)->setJSON(['error' => 'Method not allowed']);
        }

        $file = $this->fileModel->find($fileId);
        if (!$file) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Файл не найден'
            ]);
        }

        // Собираем данные из всех переданных столбцов
        $data = [];
        for ($colIndex = 0; $colIndex < $file['column_count']; $colIndex++) {
            $data['col_' . $colIndex] = $this->request->getPost('col_' . $colIndex) ?? '';
        }

        try {
            $result = $this->fileModel->addRowToFile($file['file_path'], $data, $file['column_count']);
            
            if ($result) {
                // Обновляем метаинформацию
                $this->fileModel->updateFileInfo($fileId);
                
                // Логируем добавление строки
                $this->logModel->logAction(
                    $fileId,
                    $file['original_name'],
                    'add_row',
                    'Добавлена новая строка'
                );

                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Строка успешно добавлена'
                ]);
            } else {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Ошибка при добавлении строки'
                ]);
            }
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Ошибка при добавлении строки: ' . $e->getMessage()
            ]);
        }
    }

    public function editRow($fileId)
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(405)->setJSON(['error' => 'Method not allowed']);
        }

        $file = $this->fileModel->find($fileId);
        if (!$file) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Файл не найден'
            ]);
        }

        $rowIndex = $this->request->getPost('row_index');
        
        // Собираем данные из всех переданных столбцов
        $data = [];
        for ($colIndex = 0; $colIndex < $file['column_count']; $colIndex++) {
            $data['col_' . $colIndex] = $this->request->getPost('col_' . $colIndex) ?? '';
        }

        try {
            $result = $this->fileModel->updateRowInFile($file['file_path'], $rowIndex, $data, $file['column_count']);
            
            if ($result) {
                // Логируем обновление строки
                $this->logModel->logAction(
                    $fileId,
                    $file['original_name'],
                    'update_row',
                    "Обновлена строка #{$rowIndex}"
                );

                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Строка успешно обновлена'
                ]);
            } else {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Ошибка при обновлении строки'
                ]);
            }
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Ошибка при обновлении строки: ' . $e->getMessage()
            ]);
        }
    }

    public function deleteRow($fileId)
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(405)->setJSON(['error' => 'Method not allowed']);
        }

        $file = $this->fileModel->find($fileId);
        if (!$file) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Файл не найден'
            ]);
        }

        $rowIndex = $this->request->getPost('row_index');

        try {
            $result = $this->fileModel->deleteRowFromFile($file['file_path'], $rowIndex);
            
            if ($result) {
                // Обновляем метаинформацию
                $this->fileModel->updateFileInfo($fileId);
                
                // Логируем удаление строки
                $this->logModel->logAction(
                    $fileId,
                    $file['original_name'],
                    'delete_row',
                    "Удалена строка #{$rowIndex}"
                );

                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Строка успешно удалена'
                ]);
            } else {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Ошибка при удалении строки'
                ]);
            }
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Ошибка при удалении строки: ' . $e->getMessage()
            ]);
        }
    }

}