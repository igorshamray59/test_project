<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Upload extends BaseConfig
{
    public $fileExcel = [
        'upload_path'   => WRITEPATH . 'uploads/excel/',
        'allowed_types' => 'xls|xlsx|csv',
        'max_size'      => 5120, // 5MB
        'encrypt_name'  => true,
    ];
}