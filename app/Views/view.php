<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Просмотр файла - <?= esc($file['original_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .table-responsive {
            max-height: 70vh;
            overflow: auto;
        }
        .fixed-header {
            position: sticky;
            top: 0;
            background: #2c3e50;
            z-index: 10;
        }
        .actions-column {
            position: sticky;
            right: 0;
            background: white;
            z-index: 5;
        }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Просмотр файла: <?= esc($file['original_name']) ?></h1>
            <div>
                <a href="/" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Назад к списку
                </a>
            </div>
        </div>

        <!-- Информация о файле -->
        <div class="card mb-4">
			<div class="card-header d-flex justify-content-between align-items-center">
				<h5 class="mb-0">Информация о файле</h5>
				<div>
					<a href="/file-logs/<?= $file['id'] ?>" class="btn btn-secondary btn-sm me-1">
						<i class="fas fa-history"></i> Журнал
					</a>
					 <a href="/export-excel/<?= $file['id'] ?>" class="btn btn-success btn-sm me-1">
						<i class="fas fa-file-excel"></i> Отчет Excel
					</a>
					<a href="/generate-pdf/<?= $file['id'] ?>" class="btn btn-danger btn-sm me-2">
						<i class="fas fa-file-pdf"></i> Отчет PDF
					</a>
					<button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addRowModal">
						<i class="fas fa-plus"></i> Добавить строку
					</button>
				</div>
			</div>
			<div class="card-body">
				<div class="row">
					<div class="col-md-6">
						<p><strong>Оригинальное имя:</strong> <?= esc($file['original_name']) ?></p>
						<p><strong>Дата загрузки:</strong> <?= date('d.m.Y H:i', strtotime($file['uploaded_at'])) ?></p>
					</div>
					<div class="col-md-6">
						<p><strong>Количество строк:</strong> <?= $totalRows ?></p>
						<p><strong>Размер файла:</strong> <?= number_format($file['file_size'] / 1024, 2) ?> KB</p>
					</div>
				</div>
			</div>
		</div>

        <!-- Таблица данных -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Данные из файла</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover mb-0">
                        <thead class="table-dark fixed-header">
                            <tr>
                                <th width="60">#</th>
                                <?php for ($colIndex = 0; $colIndex < $columnCount; $colIndex++): ?>
                                    <th>Столбец <?= $colIndex + 1 ?></th>
                                <?php endfor; ?>
                                <th width="120" class="actions-column">Действия</th>
                            </tr>
                        </thead>
                        <tbody id="dataTableBody">
                            <?php foreach ($fileData as $index => $row): ?>
                            <tr id="row-<?= $index ?>">
                                <td><?= (($currentPage - 1) * $perPage) + $index + 1 ?></td>
                                <?php for ($colIndex = 0; $colIndex < $columnCount; $colIndex++): ?>
                                    <td><?= esc($row['col_' . $colIndex] ?? '') ?></td>
                                <?php endfor; ?>
                                <td class="actions-column">
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary edit-row" 
                                                data-row-index="<?= $index ?>"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editRowModal">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-outline-danger delete-row" 
                                                data-row-index="<?= $index ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Пагинация -->
                <?php if ($totalRows > $perPage): ?>
                <div class="p-3 border-top">
                    <nav>
                        <ul class="pagination justify-content-center mb-0">
                            <?php if ($currentPage > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="/view/<?= $file['id'] ?>?page=<?= $currentPage - 1 ?>">
                                    <i class="fas fa-chevron-left"></i> Назад
                                </a>
                            </li>
                            <?php endif; ?>

                            <li class="page-item disabled">
                                <span class="page-link">
                                    Страница <?= $currentPage ?> из <?= ceil($totalRows / $perPage) ?>
                                </span>
                            </li>

                            <?php if ($currentPage * $perPage < $totalRows): ?>
                            <li class="page-item">
                                <a class="page-link" href="/view/<?= $file['id'] ?>?page=<?= $currentPage + 1 ?>">
                                    Вперед <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Модальное окно добавления строки -->
    <div class="modal fade" id="addRowModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Добавить новую строку</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addRowForm">
                    <div class="modal-body">
                        <?php for ($colIndex = 0; $colIndex < $columnCount; $colIndex++): ?>
                        <div class="mb-3">
                            <label for="add_col_<?= $colIndex ?>" class="form-label">Столбец <?= $colIndex + 1 ?></label>
                            <input type="text" class="form-control" 
                                   id="add_col_<?= $colIndex ?>" 
                                   name="col_<?= $colIndex ?>" 
                                   placeholder="Введите значение">
                        </div>
                        <?php endfor; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-primary">Добавить</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Модальное окно редактирования строки -->
    <div class="modal fade" id="editRowModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Редактировать строку</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editRowForm">
                    <input type="hidden" id="editRowIndex" name="row_index">
                    <div class="modal-body" id="editRowFields">
                        <!-- Поля будут добавлены динамически через JavaScript -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-primary">Сохранить</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        $(document).ready(function() {
            const fileId = <?= $file['id'] ?>;
            const columnCount = <?= $columnCount ?>;

            // Функция показа уведомлений
            function showAlert(message, type = 'success') {
                const alert = $('#ajaxAlert');
                alert.removeClass('alert-success alert-danger')
                     .addClass(`alert alert-${type}`)
                     .html(message)
                     .show()
                     .delay(3000)
                     .fadeOut();
            }

            // Добавление строки
            $('#addRowForm').on('submit', function(e) {
                e.preventDefault();
                
                $.ajax({
                    url: `/add-row/${fileId}`,
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        if (response.success) {
                            $('#addRowModal').modal('hide');
                            $('#addRowForm')[0].reset();
                            showAlert(response.message);
                            // Перезагружаем страницу для отображения изменений
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            showAlert(response.message, 'danger');
                        }
                    },
                    error: function() {
                        showAlert('Ошибка при добавлении строки', 'danger');
                    }
                });
            });

            // Редактирование строки - открытие модального окна
            $(document).on('click', '.edit-row', function() {
                const rowIndex = $(this).data('row-index');
                const row = $(`#row-${rowIndex}`);
                
                $('#editRowIndex').val(rowIndex);
                
                // Динамически создаем поля для редактирования
                let fieldsHtml = '';
                for (let colIndex = 0; colIndex < columnCount; colIndex++) {
                    const value = row.find(`td:eq(${colIndex + 1})`).text().trim();
                    fieldsHtml += `
                        <div class="mb-3">
                            <label for="edit_col_${colIndex}" class="form-label">Столбец ${colIndex + 1}</label>
                            <input type="text" class="form-control" 
                                   id="edit_col_${colIndex}" 
                                   name="col_${colIndex}" 
                                   value="${value}" 
                                   required>
                        </div>
                    `;
                }
                
                $('#editRowFields').html(fieldsHtml);
            });

            // Сохранение изменений строки
            $('#editRowForm').on('submit', function(e) {
                e.preventDefault();
                const rowIndex = $('#editRowIndex').val();
                
                $.ajax({
                    url: `/edit-row/${fileId}`,
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        if (response.success) {
                            $('#editRowModal').modal('hide');
                            showAlert(response.message);
                            // Перезагружаем страницу для отображения изменений
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            showAlert(response.message, 'danger');
                        }
                    },
                    error: function() {
                        showAlert('Ошибка при обновлении строки', 'danger');
                    }
                });
            });

            // Удаление строки
            $(document).on('click', '.delete-row', function() {
                const rowIndex = $(this).data('row-index');
                
                if (confirm('Вы уверены, что хотите удалить эту строку?')) {
                    $.ajax({
                        url: `/delete-row/${fileId}`,
                        type: 'POST',
                        data: { row_index: rowIndex },
                        success: function(response) {
                            if (response.success) {
                                showAlert(response.message);
                                // Перезагружаем страницу для отображения изменений
                                setTimeout(() => location.reload(), 1000);
                            } else {
                                showAlert(response.message, 'danger');
                            }
                        },
                        error: function() {
                            showAlert('Ошибка при удалении строки', 'danger');
                        }
                    });
                }
            });
        });
    </script>
</body>
</html>