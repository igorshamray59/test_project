<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Журнал действий - <?= esc($file['original_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Журнал действий</h1>
            <div>
                <a href="/view/<?= $file['id'] ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Назад к файлу
                </a>
                <a href="/logs" class="btn btn-outline-info ms-2">
                    <i class="fas fa-history"></i> Общий журнал
                </a>
            </div>
        </div>

        <!-- Информация о файле -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Файл: <?= esc($file['original_name']) ?></h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <p><strong>Загружен:</strong> <?= date('d.m.Y H:i', strtotime($file['uploaded_at'])) ?></p>
                        <p><strong>Размер:</strong> <?= number_format($file['file_size'] / 1024, 2) ?> KB</p>
                    </div>
                    <div class="col-md-4">
                        <p><strong>Строк:</strong> <?= $file['row_count'] ?></p>
                        <p><strong>Столбцов:</strong> <?= $file['column_count'] ?></p>
                    </div>
                    <div class="col-md-4">
                        <p><strong>Обновлен:</strong> <?= date('d.m.Y H:i', strtotime($file['updated_at'])) ?></p>
                        <p><strong>Всего действий:</strong> <?= count($logs) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Действия с файлом</h5>
            </div>
            <div class="card-body">
                <?php if (empty($logs)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-history fa-3x mb-3"></i>
                        <p>Нет записей в журнале для этого файла</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Дата и время</th>
                                    <th>Действие</th>
                                    <th>Детали</th>
                                    <th>IP адрес</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?= date('d.m.Y H:i:s', strtotime($log['created_at'])) ?></td>
                                    <td>
                                        <?php
                                        $actionLabels = [
                                            'upload' => '<span class="badge bg-success"><i class="fas fa-upload"></i> Загрузка</span>',
                                            'add_row' => '<span class="badge bg-primary"><i class="fas fa-plus"></i> Добавление строки</span>',
                                            'update_row' => '<span class="badge bg-warning"><i class="fas fa-edit"></i> Обновление строки</span>',
                                            'delete_row' => '<span class="badge bg-danger"><i class="fas fa-trash"></i> Удаление строки</span>',
                                            'delete_file' => '<span class="badge bg-danger"><i class="fas fa-trash"></i> Удаление файла</span>',
                                            'export_excel' => '<span class="badge bg-success"><i class="fas fa-file-excel"></i> Экспорт Excel</span>',
                                            'export_pdf' => '<span class="badge bg-danger"><i class="fas fa-file-pdf"></i> Экспорт PDF</span>'
                                        ];
                                        echo $actionLabels[$log['action']] ?? '<span class="badge bg-secondary">' . $log['action'] . '</span>';
                                        ?>
                                    </td>
                                    <td><?= esc($log['details']) ?></td>
                                    <td><small class="text-muted"><?= esc($log['user_ip']) ?></small></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Пагинация -->
                    <?= $pager->links() ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>