<?php
class Posts extends Trongate {

    private $default_limit = 20;
    private $per_page_options = array(10, 20, 50, 100);  

    function fetch_new_tutorial($input){

        $primary_object = $input['params']['primary_object'];

        //send the topic to openAi to get a tutorial
        $tutorialOpt = $this->make_new_tutorial($primary_object);
        if ($tutorialOpt->status !== 200) {
            http_response_code($tutorialOpt->status);
            echo $tutorialOpt->text;
            die();
        }

        $input['params']['tutorial'] =$tutorialOpt->text;
        $input['params']['pic'] ='pichere';
        return $input;
    }  

    function init_gen_image(){
        //get some posted data
        $params = file_get_contents('php://input'); //json str
        $posted_param = json_decode($params);

        $update_id = $posted_param->updateId ?? 0;
        $tutorialTopic = $posted_param->actiontext ?? '';

        if($tutorialTopic == ''){
            http_response_code(400);
            echo 'no topic';
            die();
        }

        //generate an image using openAI

        $output_img = $this->generate_image($tutorialTopic);

        if($output_img->status !== 200){
            http_response_code($output_img->status);
            echo $output_img->text;//error msg
            die();
        }

        //updated the 'pic' column on the database
        $data['pic']=$output_img->text;
        $update_id=(int)$update_id;
        $this->model->update($update_id, $data, 'posts');

        $pic_path = $output_img->text;
        http_response_code(200);
        echo $pic_path;



    }



    function make_new_tutorial($object) {


        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, "https://api.openai.com/v1/completions");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $prompt = 'Create a news tutorial that teach a robot pretend to do something like a human without arousing any suspicions. We are the robots. The detailed tutorial is about how to '.$object;

        curl_setopt($ch, CURLOPT_POSTFIELDS, "{
            \"model\": \"text-davinci-002\",
            \"prompt\": \"" . $prompt . "\",
            \"max_tokens\": 999,
            \"top_p\": 1,
            \"stop\": \"\"
        }");
        curl_setopt($ch, CURLOPT_POST, 1);

        $headers = array();
        $headers[] = "Content-Type: application/json";
        $headers[] = "Authorization: Bearer ".API_KEY;
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);

        // Get the HTTP status code
        $info = curl_getinfo($ch);
        $httpCode = $info['http_code'];

        if (curl_error($ch)) {
            // There was an error
            $response_text = curl_error($ch);
        } else {
            // The request was successful
            $response_obj = json_decode($result);
            $response_text = $response_obj->choices[0]->text ?? '';
        }

        curl_close ($ch);

        // Create the output object
        $output = new stdClass();
        $output->text = trim($response_text);
        $output->status = $httpCode;

        return $output;
    }

        function generate_image($target_subject) {

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, "https://api.openai.com/v1/images/generations");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "{
            \"model\": \"image-alpha-001\",
            \"prompt\": \"robot do the following action: $target_subject'\",
            \"num_images\":1,
            \"size\":\"512x512\",
            \"response_format\":\"url\"
        }");
        curl_setopt($ch, CURLOPT_POST, 1);

        $headers = array();
        $headers[] = "Content-Type: application/json";
        $headers[] = "Authorization: Bearer ".API_KEY;
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);

        // Get the HTTP status code
        $info = curl_getinfo($ch);
        $httpCode = $info['http_code'];

        if (curl_error($ch)) {
            // There was an error
            $response_text = curl_error($ch);
        } else {
            // The request was successful
            $response_obj = json_decode($result);
            $pic_path = $response_obj->data[0]->url ?? '';

            //remove unwanted whitespace
            $pic_path = trim($pic_path);
       
            //does the pic path look look?
            $str_start = substr($pic_path, 0, 4);

            if($str_start !== 'http') {
                $httpCode = 500; //Server Error
                $response_text = 'We could not generate an image on this occasion.';
            } else {
                $response_text = $pic_path;
            }

        }

        curl_close ($ch);

        // Create the output object
        $output = new stdClass();
        $output->text = $response_text;
        $output->status = $httpCode;

        return $output;
    }

    function create() {
        $this->module('trongate_security');
        $this->trongate_security->_make_sure_allowed();

        $update_id = (int) segment(3);
        $submit = post('submit');

        if (($submit == '') && ($update_id>0)) {
            $data = $this->_get_data_from_db($update_id);
        } else {
            $data = $this->_get_data_from_post();
        }

        if ($update_id>0) {
            $data['headline'] = 'Update Post Record';
            $data['cancel_url'] = BASE_URL.'posts/show/'.$update_id;
        } else {
            $data['headline'] = 'Create New Post Record';
            $data['cancel_url'] = BASE_URL.'posts/manage';
        }

        $data['form_location'] = BASE_URL.'posts/submit/'.$update_id;
        $data['view_file'] = 'create';
        $this->template('admin', $data);
    }

    function manage() {
        $this->module('trongate_security');
        $this->trongate_security->_make_sure_allowed();

        if (segment(4) !== '') {
            $data['headline'] = 'Search Results';
            $searchphrase = trim($_GET['searchphrase']);
            $params['primary_object'] = '%'.$searchphrase.'%';
            $params['secondary_object'] = '%'.$searchphrase.'%';
            $sql = 'select * from posts
            WHERE primary_object LIKE :primary_object
            OR secondary_object LIKE :secondary_object
            ORDER BY id';
            $all_rows = $this->model->query_bind($sql, $params, 'object');
        } else {
            $data['headline'] = 'Manage Posts';
            $all_rows = $this->model->get('id');
        }

        $pagination_data['total_rows'] = count($all_rows);
        $pagination_data['page_num_segment'] = 3;
        $pagination_data['limit'] = $this->_get_limit();
        $pagination_data['pagination_root'] = 'posts/manage';
        $pagination_data['record_name_plural'] = 'posts';
        $pagination_data['include_showing_statement'] = true;
        $data['pagination_data'] = $pagination_data;

        $data['rows'] = $this->_reduce_rows($all_rows);
        $data['selected_per_page'] = $this->_get_selected_per_page();
        $data['per_page_options'] = $this->per_page_options;
        $data['view_module'] = 'posts';
        $data['view_file'] = 'manage';
        $this->template('admin', $data);
    }

    function show() {
        $this->module('trongate_security');
        $token = $this->trongate_security->_make_sure_allowed();
        $update_id = (int) segment(3);

        if ($update_id == 0) {
            redirect('posts/manage');
        }

        $data = $this->_get_data_from_db($update_id);
        $data['token'] = $token;

        if ($data == false) {
            redirect('posts/manage');
        } else {
            $data['update_id'] = $update_id;
            $data['headline'] = 'Post Information';
            $data['view_file'] = 'show';
            $this->template('admin', $data);
        }
    }
    
    function _reduce_rows($all_rows) {
        $rows = [];
        $start_index = $this->_get_offset();
        $limit = $this->_get_limit();
        $end_index = $start_index + $limit;

        $count = -1;
        foreach ($all_rows as $row) {
            $count++;
            if (($count>=$start_index) && ($count<$end_index)) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    function submit() {
        $this->module('trongate_security');
        $this->trongate_security->_make_sure_allowed();

        $submit = post('submit', true);

        if ($submit == 'Submit') {

            $this->validation_helper->set_rules('primary_object', 'Primary Object', 'required|min_length[2]|max_length[255]');
            $this->validation_helper->set_rules('secondary_object', 'Secondary Object', 'min_length[2]|max_length[255]');
            $this->validation_helper->set_rules('tutorial', 'Tutorial', 'min_length[2]');
            $this->validation_helper->set_rules('pic', 'Pic', 'min_length[2]');

            $result = $this->validation_helper->run();

            if ($result == true) {

                $update_id = (int) segment(3);
                $data = $this->_get_data_from_post();
                
                if ($update_id>0) {
                    //update an existing record
                    $this->model->update($update_id, $data, 'posts');
                    $flash_msg = 'The record was successfully updated';
                } else {
                    //insert the new record
                    $update_id = $this->model->insert($data, 'posts');
                    $flash_msg = 'The record was successfully created';
                }

                set_flashdata($flash_msg);
                redirect('posts/show/'.$update_id);

            } else {
                //form submission error
                $this->create();
            }

        }

    }

    function submit_delete() {
        $this->module('trongate_security');
        $this->trongate_security->_make_sure_allowed();

        $submit = post('submit');
        $params['update_id'] = (int) segment(3);

        if (($submit == 'Yes - Delete Now') && ($params['update_id']>0)) {
            //delete all of the comments associated with this record
            $sql = 'delete from trongate_comments where target_table = :module and update_id = :update_id';
            $params['module'] = 'posts';
            $this->model->query_bind($sql, $params);

            //delete the record
            $this->model->delete($params['update_id'], 'posts');

            //set the flashdata
            $flash_msg = 'The record was successfully deleted';
            set_flashdata($flash_msg);

            //redirect to the manage page
            redirect('posts/manage');
        }
    }

    function _get_limit() {
        if (isset($_SESSION['selected_per_page'])) {
            $limit = $this->per_page_options[$_SESSION['selected_per_page']];
        } else {
            $limit = $this->default_limit;
        }

        return $limit;
    }

    function _get_offset() {
        $page_num = (int) segment(3);

        if ($page_num>1) {
            $offset = ($page_num-1)*$this->_get_limit();
        } else {
            $offset = 0;
        }

        return $offset;
    }

    function _get_selected_per_page() {
        $selected_per_page = (isset($_SESSION['selected_per_page'])) ? $_SESSION['selected_per_page'] : 1;
        return $selected_per_page;
    }

    function set_per_page($selected_index) {
        $this->module('trongate_security');
        $this->trongate_security->_make_sure_allowed();

        if (!is_numeric($selected_index)) {
            $selected_index = $this->per_page_options[1];
        }

        $_SESSION['selected_per_page'] = $selected_index;
        redirect('posts/manage');
    }

    function _get_data_from_db($update_id) {
        $record_obj = $this->model->get_where($update_id, 'posts');

        if ($record_obj == false) {
            $this->template('error_404');
            die();
        } else {
            $data = (array) $record_obj;
            return $data;        
        }
    }

    function _get_data_from_post() {
        $data['primary_object'] = post('primary_object', true);
        $data['secondary_object'] = post('secondary_object', true);
        $data['tutorial'] = post('tutorial', true);
        $data['pic'] = post('pic', true);        
        return $data;
    }

}