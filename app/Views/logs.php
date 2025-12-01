<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Журнал действий</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Журнал действий</h1>
            <a href="/" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> К списку файлов
            </a>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Все действия</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Дата и время</th>
                                <th>Файл</th>
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
                                    <?= esc($log['filename']) ?>
                                </td>
                                <td>
                                    <?php
                                    $actionLabels = [
                                        'upload' => '<span class="badge bg-success">Загрузка</span>',
                                        'add_row' => '<span class="badge bg-primary">Добавление строки</span>',
                                        'update_row' => '<span class="badge bg-warning">Обновление строки</span>',
                                        'delete_row' => '<span class="badge bg-danger">Удаление строки</span>',
                                        'delete_file' => '<span class="badge bg-danger">Удаление файла</span>',
                                        'export_excel' => '<span class="badge bg-info">Экспорт Excel</span>',
                                        'export_pdf' => '<span class="badge bg-info">Экспорт PDF</span>'
                                    ];
                                    echo $actionLabels[$log['action']] ?? '<span class="badge bg-secondary">' . $log['action'] . '</span>';
                                    ?>
                                </td>
                                <td><?= esc($log['details']) ?></td>
                                <td><small><?= esc($log['user_ip']) ?></small></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Пагинация -->
                <?= $pager->links() ?>
            </div>
        </div>
    </div>
</body>
</html>