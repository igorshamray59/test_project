<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Загрузка Excel файла</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Загрузка Excel файла</h4>
                    </div>
                    <div class="card-body">
                        <?php if (session()->getFlashdata('error')): ?>
                            <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
                        <?php endif; ?>

                        <form action="/upload" method="post" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="excel_file" class="form-label">Выберите Excel файл</label>
                                <input type="file" class="form-control" id="excel_file" name="excel_file" 
                                       accept=".xls,.xlsx" required>
                                <div class="form-text">Поддерживаются файлы .xls и .xlsx</div>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Загрузить</button>
                                <a href="/" class="btn btn-secondary">Назад к списку</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>