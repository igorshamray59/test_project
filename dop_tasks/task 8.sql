SELECT *
FROM 'Your table'
WHERE 'date_column_from_your_table' >= date_trunc('month', current_date)
  AND 'date_column_from_your_table' < date_trunc('month', current_date) + interval '1 month';