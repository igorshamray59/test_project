<?php

namespace App\Models;

use CodeIgniter\Model;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;

class FileModel extends Model
{
    protected $table            = 'files';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'filename', 'original_name', 'file_path', 'file_size', 
        'row_count', 'column_count', 'uploaded_at', 'updated_at'
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'uploaded_at';
    protected $updatedField  = 'updated_at';

    public function getFiles($perPage = 10, $page = 1)
    {
        return $this->orderBy('uploaded_at', 'DESC')
                    ->paginate($perPage, 'default', $page);
    }

    public function getPager()
    {
        return $this->pager;
    }

    // Получает данные из Excel файла с пагинацией
    public function getFileData($filePath, $perPage = 5, $page = 1)
    {
        try {
            $reader = ReaderEntityFactory::createXLSXReader();
            $reader->open($filePath);

            $data = [];
            $startRow = ($page - 1) * $perPage + 1;
            $endRow = $startRow + $perPage - 1;
            $currentRow = 0;

            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $rowIndex => $row) {
                    $currentRow++;
                    
                    // Проверяем попадает ли строка в диапазон пагинации
                    if ($currentRow >= $startRow && $currentRow <= $endRow) {
                        $rowData = ['id' => $currentRow];
                        $cells = $row->getCells();
                        
                        foreach ($cells as $colIndex => $cell) {
                            $rowData['col_' . $colIndex] = $cell->getValue();
                        }
                        
                        $data[] = $rowData;
                    }

                    // Прерываем если вышли за нужный диапазон
                    if ($currentRow > $endRow) {
                        break;
                    }
                }
                break;
            }

            $reader->close();
            return $data;

        } catch (\Exception $e) {
            log_message('error', 'Excel read error: ' . $e->getMessage());
            return [];
        }
    }

    // Получает количество столбцов в файле
    public function getFileColumnCount($filePath)
    {
        try {
            $reader = ReaderEntityFactory::createXLSXReader();
            $reader->open($filePath);

            $maxColumns = 0;

            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $rowIndex => $row) {
                    $cellCount = count($row->getCells());
                    if ($cellCount > $maxColumns) {
                        $maxColumns = $cellCount;
                    }
                    // Проверяем только первые 10 строк для производительности
                    if ($rowIndex >= 10) break;
                }
                break;
            }

            $reader->close();
            return $maxColumns;

        } catch (\Exception $e) {
            log_message('error', 'Excel column count error: ' . $e->getMessage());
            return 0;
        }
    }

    // Получает общее количество строк в файле
    public function getFileDataCount($filePath)
    {
        try {
            $reader = ReaderEntityFactory::createXLSXReader();
            $reader->open($filePath);

            $rowCount = 0;

            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $rowCount++;
                }
                break;
            }

            $reader->close();
            return $rowCount;

        } catch (\Exception $e) {
            log_message('error', 'Excel count error: ' . $e->getMessage());
            return 0;
        }
    }

    // Добавляет строку в Excel файл
    public function addRowToFile($filePath, $data, $columnCount)
    {
        try {
            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();

            // Находим последнюю строку
            $lastRow = $sheet->getHighestRow() + 1;

            // Добавляем данные
            for ($colIndex = 0; $colIndex < $columnCount; $colIndex++) {
                $columnLetter = $this->getColumnLetter($colIndex);
                $value = $data['col_' . $colIndex] ?? '';
                $sheet->setCellValue($columnLetter . $lastRow, $value);
            }

            // Сохраняем файл
            $writer = new Xlsx($spreadsheet);
            $writer->save($filePath);

            return true;

        } catch (\Exception $e) {
            log_message('error', 'Excel add row error: ' . $e->getMessage());
            return false;
        }
    }

    // Обновляет строку в Excel файле
    public function updateRowInFile($filePath, $rowIndex, $data, $columnCount)
    {
        try {
            // +1 потому что строки в Excel начинаются с 1
            $excelRow = $rowIndex + 1;

            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();

            // Обновляем данные
            for ($colIndex = 0; $colIndex < $columnCount; $colIndex++) {
                $columnLetter = $this->getColumnLetter($colIndex);
                $value = $data['col_' . $colIndex] ?? '';
                $sheet->setCellValue($columnLetter . $excelRow, $value);
            }

            // Сохраняем файл
            $writer = new Xlsx($spreadsheet);
            $writer->save($filePath);

            return true;

        } catch (\Exception $e) {
            log_message('error', 'Excel update row error: ' . $e->getMessage());
            return false;
        }
    }

    // Удаляет строку из Excel файла
    public function deleteRowFromFile($filePath, $rowIndex)
    {
        try {
            // +1 потому что строки в Excel начинаются с 1
            $excelRow = $rowIndex + 1;

            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();

            // Удаляем строку
            $sheet->removeRow($excelRow);

            // Сохраняем файл
            $writer = new Xlsx($spreadsheet);
            $writer->save($filePath);

            return true;

        } catch (\Exception $e) {
            log_message('error', 'Excel delete row error: ' . $e->getMessage());
            return false;
        }
    }

    // Получает букву колонки по индексу (A, B, C, ..., Z, AA, AB, ...)
    private function getColumnLetter($index)
    {
        $letters = '';
        while ($index >= 0) {
            $letters = chr(65 + ($index % 26)) . $letters;
            $index = floor($index / 26) - 1;
        }
        return $letters;
    }

    //Обновляет метаинформацию о файле
    public function updateFileInfo($fileId)
    {
        $file = $this->find($fileId);
        if (!$file) {
            return false;
        }

        $rowCount = $this->getFileDataCount($file['file_path']);
        $fileSize = filesize($file['file_path']);

        return $this->update($fileId, [
            'row_count' => $rowCount,
            'file_size' => $fileSize,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    //Получает информацию о файле при загрузке
    public function getFileInfo($filePath)
    {
        $rowCount = $this->getFileDataCount($filePath);
        $columnCount = $this->getFileColumnCount($filePath);

        return [
            'row_count' => $rowCount,
            'column_count' => $columnCount
        ];
    }

	// Экспорт информации о файле в Excel
    public function exportFileInfoToExcel($file, $logs, $exportPath)
    {
        try {
            $spreadsheet = new Spreadsheet();
            
            // Лист с информацией о файле
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Информация о файле');

            // Стили
            $headerStyle = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2c3e50']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
            ];

            $dataStyle = [
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
            ];

            // Заголовок отчета
            $sheet->setCellValue('A1', 'Отчет по файлу: ' . $file['original_name']);
            $sheet->mergeCells('A1:E1');
            $sheet->getStyle('A1')->getFont()->setSize(16)->setBold(true);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Информация о файле
            $sheet->setCellValue('A3', 'Основная информация');
            $sheet->getStyle('A3')->getFont()->setBold(true)->setSize(12);

            $fileInfo = [
                ['Параметр', 'Значение'],
                ['Имя файла', $file['original_name']],
                ['Размер файла', number_format($file['file_size'] / 1024, 2) . ' KB'],
                ['Количество строк', $file['row_count']],
                ['Количество столбцов', $file['column_count']],
                ['Дата загрузки', date('d.m.Y H:i', strtotime($file['uploaded_at']))],
                ['Последнее изменение', date('d.m.Y H:i', strtotime($file['updated_at']))],
                ['Всего действий в журнале', count($logs)]
            ];

            $row = 4;
            foreach ($fileInfo as $info) {
                $sheet->setCellValue('A' . $row, $info[0]);
                $sheet->setCellValue('B' . $row, $info[1]);
                $row++;
            }

            // Применяем стили к информации о файле
            $sheet->getStyle('A4:B' . ($row-1))->applyFromArray($dataStyle);
            $sheet->getStyle('A4:A' . ($row-1))->getFont()->setBold(true);

            // Журнал действий
            $row += 2;
            $sheet->setCellValue('A' . $row, 'Журнал действий');
            $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);

            $row++;
            $logHeaders = ['Дата и время', 'Действие', 'Детали', 'IP адрес'];
            $col = 1;
            foreach ($logHeaders as $header) {
                $sheet->setCellValue([$col, $row], $header);
                $col++;
            }
            $sheet->getStyle('A' . $row . ':D' . $row)->applyFromArray($headerStyle);

            $row++;
            foreach ($logs as $log) {
                $sheet->setCellValue('A' . $row, date('d.m.Y H:i:s', strtotime($log['created_at'])));
                $sheet->setCellValue('B' . $row, $this->getActionLabel($log['action']));
                $sheet->setCellValue('C' . $row, $log['details']);
                $sheet->setCellValue('D' . $row, $log['user_ip']);
                $row++;
            }

            // Применяем стили к журналу
            if ($row > 10) {
                $sheet->getStyle('A10:D' . ($row-1))->applyFromArray($dataStyle);
            }

            // Авто-ширина колонок
            foreach (range('A', 'D') as $column) {
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }

            // Сохраняем файл
            $writer = new Xlsx($spreadsheet);
            $writer->save($exportPath);

            return true;

        } catch (\Exception $e) {
            log_message('error', 'Excel file info export error: ' . $e->getMessage());
            return false;
        }
    }

    // Генерация PDF с информацией о файле
    public function generateFileInfoPdf($file, $logs, $pdfPath)
    {
        try {
            $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            
            $pdf->SetCreator('File Manager');
            $pdf->SetTitle('Отчет по файлу: ' . $file['original_name']);
            
            $pdf->AddPage();
            
            // Заголовок
            $pdf->SetFont('dejavusans', 'B', 16);
            $pdf->Cell(0, 10, 'Отчет по файлу: ' . $file['original_name'], 0, 1, 'C');
            $pdf->Ln(10);

            // Информация о файле
            $pdf->SetFont('dejavusans', 'B', 12);
            $pdf->Cell(0, 8, 'Основная информация:', 0, 1);
            $pdf->SetFont('dejavusans', '', 10);
            
            $fileInfo = [
                'Имя файла: ' . $file['original_name'],
                'Размер файла: ' . number_format($file['file_size'] / 1024, 2) . ' KB',
                'Количество строк: ' . $file['row_count'],
                'Количество столбцов: ' . $file['column_count'],
                'Дата загрузки: ' . date('d.m.Y H:i', strtotime($file['uploaded_at'])),
                'Последнее изменение: ' . date('d.m.Y H:i', strtotime($file['updated_at'])),
                'Всего действий в журнале: ' . count($logs)
            ];

            foreach ($fileInfo as $info) {
                $pdf->Cell(0, 6, $info, 0, 1);
            }

            $pdf->Ln(10);

            // Журнал действий
            $pdf->SetFont('dejavusans', 'B', 12);
            $pdf->Cell(0, 8, 'Журнал действий:', 0, 1);
            $pdf->Ln(5);

            if (empty($logs)) {
                $pdf->SetFont('dejavusans', 'I', 10);
                $pdf->Cell(0, 6, 'Нет записей в журнале', 0, 1);
            } else {
                $pdf->SetFont('dejavusans', 'B', 9);
                $pdf->Cell(40, 6, 'Дата/время', 1, 0, 'C');
                $pdf->Cell(30, 6, 'Действие', 1, 0, 'C');
                $pdf->Cell(80, 6, 'Детали', 1, 0, 'C');
                $pdf->Cell(40, 6, 'IP адрес', 1, 1, 'C');

                $pdf->SetFont('dejavusans', '', 8);
                foreach ($logs as $log) {
                    if ($pdf->GetY() > 250) {
                        $pdf->AddPage();
                        $pdf->SetFont('dejavusans', 'B', 9);
                        $pdf->Cell(40, 6, 'Дата/время', 1, 0, 'C');
                        $pdf->Cell(30, 6, 'Действие', 1, 0, 'C');
                        $pdf->Cell(80, 6, 'Детали', 1, 0, 'C');
                        $pdf->Cell(40, 6, 'IP адрес', 1, 1, 'C');
                        $pdf->SetFont('dejavusans', '', 8);
                    }
                    
                    $pdf->Cell(40, 6, date('d.m.Y H:i', strtotime($log['created_at'])), 1);
                    $pdf->Cell(30, 6, $this->getActionLabel($log['action']), 1);
                    $pdf->Cell(80, 6, $this->truncateText($log['details'], 40), 1);
                    $pdf->Cell(40, 6, $log['user_ip'], 1, 1);
                }
            }

            // Сохраняем PDF
            $pdf->Output($pdfPath, 'F');
            return true;

        } catch (\Exception $e) {
            log_message('error', 'PDF file info generation error: ' . $e->getMessage());
            return false;
        }
    }

    // Получает русское название действия
    private function getActionLabel($action)
    {
        $labels = [
            'upload' => 'Загрузка',
            'add_row' => 'Добавление строки',
            'update_row' => 'Обновление строки',
            'delete_row' => 'Удаление строки',
            'delete_file' => 'Удаление файла',
            'export_excel' => 'Экспорт Excel',
            'export_pdf' => 'Экспорт PDF'
        ];
        
        return $labels[$action] ?? $action;
    }

    // Обрезает текст для PDF
    private function truncateText($text, $length)
    {
        if (strlen($text) > $length) {
            return substr($text, 0, $length - 3) . '...';
        }
        return $text;
    }

}