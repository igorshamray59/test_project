<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Список файлов</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Список загруженных файлов</h1>
			<div>
				<a href="/logs" class="btn btn-info me-2">
					<i class="fas fa-history"></i> Журнал действий
				</a>
				<a href="/upload" class="btn btn-primary">Загрузить файл</a>
			</div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Имя файла</th>
                                <th>Дата загрузки</th>
                                <th>Дата изменения</th>
                                <th>Количество строк</th>
                                <th>Размер</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($files as $file): ?>
                            <tr>
                                <td><?= esc($file['original_name']) ?></td>
                                <td><?= date('d.m.Y H:i', strtotime($file['uploaded_at'])) ?></td>
                                <td><?= date('d.m.Y H:i', strtotime($file['updated_at'])) ?></td>
                                <td><?= $file['row_count'] ?></td>
                                <td><?= number_format($file['file_size'] / 1024, 2) ?> KB</td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="/view/<?= $file['id'] ?>" class="btn btn-outline-primary">Просмотр</a>
                                        <a href="/download/<?= $file['id'] ?>" class="btn btn-outline-success">Скачать</a>
                                        <a href="/delete/<?= $file['id'] ?>" class="btn btn-outline-danger" 
                                           onclick="return confirm('Вы уверены что хотите удалить этот файл?')">Удалить</a>
                                    </div>
                                </td>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>