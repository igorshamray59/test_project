<?php

public function show_files()
{
    $session = session();
    $session_data = $session->get('logged_in');
    $data['inner_view'] = "filters";
    $limit_per_page = 20;
    
    $start_index = ($this->request->getUri()->getSegment(3)) ? $this->request->getUri()->getSegment(3) : 0;
    
    if ($start_index == 0) {
        $per_page = 0;
        $start_index = $limit_per_page;
    } else {
        $per_page = $start_index;
        $start_index = $start_index + $limit_per_page;
    }
    
    $worker_id = $session_data['worker_id'] ?? null;
    
    $total_records = $this->ask2sud_model->getTotalASA($worker_id);
    
    if ($total_records > 0) {
        $data['result'] = $this->ask2sud_model->ShowAskLimit($worker_id, $per_page, $start_index);
        
        $pager = service('pager');
        
        $current_page = ($per_page / $limit_per_page) + 1;
        
        $data['links'] = $pager->makeLinks(
            $current_page,
            $limit_per_page,
            $total_records,
            'default',
            3,
            base_url('index.php/ask/show_files')
        );
    }

    echo view('templates/header', $data);
    echo view('ask/show_file', $data);
    echo view('templates/footer_no', $data);
}