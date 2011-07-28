<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Course extends Controller_Base {
	
	public function action_index() {
		
        if($this->request->param('sort')){
            $sort = $this->request->param('sort');
        } else {
            $sort = 'name';
        }
        
        if($this->request->param('order')){
            $order = $this->request->param('order');
        } else {
            $order = 'DESC';
        }
		
        $course = ORM::factory('course');
        
        if($this->request->param('filter_name')){
            $course->where('courses.name', 'LIKE', '%' . $this->request->param('filter_name') . '%');
        }
        
        if($this->request->param('filter_access_code')){
            $course->where('courses.access_code', 'LIKE', '%' . $this->request->param('filter_access_code') . '%');
        }
        
        if($this->request->param('filter_start_date')){
            $course->where('courses.start_date', '=', '' . $this->request->param('filter_start_date') . '');
        }
        
        if($this->request->param('filter_end_date')){
            $course->where('courses.end_date', '=', '' . $this->request->param('filter_end_date') . '');
        }
        
        $count = $course->count_all();
        
        $pagination = Pagination::factory(array(
            'total_items'    => $count,
            'items_per_page' => 5,
        ));
        
        if($this->request->param('filter_name')){
            $course->where('courses.name', 'LIKE', '%' . $this->request->param('filter_name') . '%');
        }
        
        if($this->request->param('filter_access_code')){
            $course->where('courses.access_code', 'LIKE', '%' . $this->request->param('filter_access_code') . '%');
        }
        
        if($this->request->param('filter_start_date')){
            $course->where('courses.start_date', '=', '' . $this->request->param('filter_start_date') . '');
        }
        
        if($this->request->param('filter_end_date')){
            $course->where('courses.end_date', '=', '' . $this->request->param('filter_end_date') . '');
        }
                
        $course->order_by($sort, $order)
            ->limit($pagination->items_per_page)
            ->offset($pagination->offset)
            ;
        
		$courses = $course->find_all();
		
        $sorting = new Sort(array(
            'Course'        => 'name',
            'Access Code'   => 'access_code',
            'Start Date'    => 'start_date',
            'End Date'      => 'end_date',
            'Actions'       => '',
        ));
        
        $url = ('course/index');
        
        if($this->request->param('filter_name')){
            $url .= '/filter_name/'.$this->request->param('filter_name');
        }
        
        if($this->request->param('filter_access_code')){
            $url .= '/filter_access_code/'.$this->request->param('filter_access_code');
        }
        
        if($this->request->param('filter_start_date')){
            $url .= '/filter_start_date/'.$this->request->param('filter_start_date');
        }
        
        if($this->request->param('filter_end_date')){
            $url .= '/filter_end_date/'.$this->request->param('filter_end_date');
        }
        
        $sorting->set_link($url);
        
        $sorting->set_order($order);
        $sorting->set_sort($sort);
        $heading = $sorting->render();
		
        // Render the pagination links
        $pagination = $pagination->render();
        
        $links = array(
            'add' => Html::anchor('/course/add/', 'Create a course', array('class' => 'createButton l')),
            'delete'      => URL::site('/course/delete/')
        );
        
        $table = array('heading' => $heading, 'data' => $courses);
		
        $filter_name = $this->request->param('filter_name');
        $filter_access_code = $this->request->param('filter_access_code');
        $filter_start_date = $this->request->param('filter_start_date');
        $filter_end_date = $this->request->param('filter_end_date');
        $filter_url = URL::site('course/index');
        
        $view = View::factory('course/list')
		          ->bind('table', $table)
		          ->bind('links', $links)
		          ->bind('pagination', $pagination)
                  ->bind('filter_name', $filter_name)
                  ->bind('filter_access_code', $filter_access_code)
                  ->bind('filter_start_date', $filter_start_date)
                  ->bind('filter_end_date', $filter_end_date)
                  ->bind('filter_url', $filter_url);
                  
		$this->content = $view;
	}
	
	public function action_delete(){
        if($this->request->method() === 'POST' && $this->request->post('selected')){
            foreach($this->request->post('selected') as $course_id){
                ORM::factory('course', $course_id)->delete();
            }
        }
        Request::current()->redirect('course');
	}
	
	public function action_add(){
		
		$submitted = FALSE;
        if($this->request->method() === 'POST' && $this->request->post()){
            if (Arr::get($this->request->post(), 'save') !== null){
                $submitted = true;
                $course = ORM::factory('course');
                $validator = $course->validator($this->request->post());
                $validator->bind(':course', NULL);
                if ($validator->check()) {
                    $course->name = $this->request->post('name');
                    $course->description = $this->request->post('description');
                    $course->access_code = $this->request->post('access_code');
                    $course->start_date = $this->request->post('start_date');
                    $course->end_date = $this->request->post('end_date');
                    $course->save();
                    if($this->request->post('selected')){
                        if($this->request->post('selected')){
                            foreach($this->request->post('selected') as $user_id){
                                $user = ORM::factory('user', $user_id);
                                $course->add('users', $user);
                            }
                        }
                    }
                    Request::current()->redirect('course');
                    exit;
                } else {
                    $this->_errors = $validator->errors('course');
               }
            }
        }
        
		$form = $this->form('course/add', $submitted);
		
        $data = array();
        $cacheimage = CacheImage::factory();
        $user_ids = array();
        $course_id = 0;

        $users = View::factory('course/assign')
                        ->bind('data', $data)
                        ->bind('cacheimage', $cacheimage)
                        ->bind('user_ids', $user_ids);
		
        $batches = ORM::factory('batch')->find_all();
        $view = View::factory('course/form')
                       ->bind('form', $form)
                       ->bind('users', $users)
                       ->bind('batches', $batches)
                       ->bind('course_id', $course_id);
		
		$this->content = $view;
	}
	
	private function form($action, $submitted = false, $saved_data = array()){
		
        $form = new Stickyform($action, array(), ($submitted ? $this->_errors : array()));
        $form->default_data = array(
            'name' => '',
            'description' => '',
            'access_code' => '',
            'start_date'  => date('Y-m-d'),
            'end_date'    => '',
        );
        
        $form->saved_data = $saved_data;
        $form->posted_data = $submitted ? $this->request->post() : array();
        $form->append('Name', 'name', 'text');
        $form->append('Description', 'description', 'textarea', array('attributes' => array('cols' => 50, 'rows' => 5)));
        $form->append('Access code', 'access_code', 'text');
        $form->append('Start Date', 'start_date', 'text', array('attributes' => array('class' => 'date')));
        $form->append('End Date', 'end_date', 'text', array('attributes' => array('class' => 'date')));
        $form->append('Save', 'save', 'submit', array('attributes' => array('class' => 'button r')));
        $form->process();
        return $form;
	}
	
    public function action_edit() {
        $submitted = false;
        
        $id = $this->request->param('id');
        if(!$id)
            Request::current()->redirect('course');
            
        $course = ORM::factory('course', $id);

        if($this->request->method() === 'POST' && $this->request->post()){
            if (Arr::get($this->request->post(), 'save') !== null){
                $submitted = true;
                $validator = $course->validator($this->request->post());
                $validator->bind(':course', $course);
                if ($validator->check()) {
                    $course->name = $this->request->post('name');
                    $course->description = $this->request->post('description');
                    $course->access_code = $this->request->post('access_code');
                    $course->start_date = $this->request->post('start_date');
                    $course->end_date = $this->request->post('end_date');
                    if($this->request->post('selected')){
                    	$course->remove('users');
	                    if($this->request->post('selected')){
	                        foreach($this->request->post('selected') as $user_id){
	                            $user = ORM::factory('user', $user_id);
	                            $course->add('users', $user);
	                        }
	                    }
                    }
                    $course->save();
                    Request::current()->redirect('course');
                    exit;
                } else {
                    $this->_errors = $validator->errors('course');
                }
            }
         }
        
        $form = $this->form('course/edit/id/'.$id ,$submitted, array('name' => $course->name, 'description' => $course->description, 'access_code' => $course->access_code, 'start_date' => $course->start_date, 'end_date' => $course->end_date));
        
        $data = $course->users->find_all();
        $cacheimage = CacheImage::factory();
        $user_ids = $data->as_array(NULL, 'id');

        $users = View::factory('course/assign')
                        ->bind('data', $data)
                        ->bind('cacheimage', $cacheimage)
                        ->bind('user_ids', $user_ids);
        
        
                        
        $batches = ORM::factory('batch')->find_all();
        $view = View::factory('course/form')
                       ->bind('form', $form)
                       ->bind('users', $users)
                       ->bind('batches', $batches)
                       ->bind('course_id', $id);
        
        $this->content = $view;
    }
    
    public function action_summary() {
    	
    	$id = $this->request->param('id');
    	if(!$id)
    	   Request::current()->redirect('course');
    	   
    	$course = ORM::factory('course', $id);
    	
    	$view = View::factory('course/summary')
    	               ->bind('course', $course);
    	
    	$this->content = $view;
    }
    
    public function action_get_users() {
    	
    	
        $course_users = ORM::factory('course', $this->request->post('course_id'))->users->find_all()->as_array(NULL, 'id');
        $batch_users = ORM::factory('batch', $this->request->post('batch_id'))->users->find_all()->as_array(NULL, 'id');
        
        $user_ids = array_unique(array_merge($course_users, $batch_users));
        
        if($user_ids){
            $data = ORM::factory('user')->where('id', 'IN', $user_ids )->find_all();
        } else {
        	$data = ORM::factory('user')->where('id', '=', 0 )->find_all();
        }

        $cacheimage = CacheImage::factory();
        	
        $view = View::factory('course/assign')
                        ->bind('data', $data)
                        ->bind('cacheimage', $cacheimage)
                        ->bind('user_ids', $course_users);

        $response = $this->response->body($view)->body();
    	echo json_encode(array('response' => $response));
    }
    
    public function action_join() {
    	
    	if($this->request->method() === 'POST' && $this->request->post()){
    		$access_code = $this->request->post('access_code');
        
            if($access_code){
            	$course = ORM::factory('course')->where('access_code' , '=' , $access_code)->find();
            	$user = Auth::instance()->get_user();
            	$user->add('courses', $course);
            	$json = array('response' => 'You are joined in ' . $course->name . ' course.');
            	
            } else {
            	$json = array('response' => 'Please enter access code.');
            } 		
            
            echo json_encode($json);
            exit;
    	}
    	
    	$view = View::factory('course/join');
    	
    	$this->content = $view;
    }
    
    public function action_course_detail(){
    	
    	$access_code = $this->request->post('access_code');
    	
    	if($access_code){
    		$course = ORM::factory('course')->where('access_code' , '=' , $access_code)->find();
    		if($course->id !== NULL){
    			
    			$user = Auth::instance()->get_user();

    			if($user->has('courses', $course)){
    				$json = array('response' => 'You are Already in this Course.');
    			} else {
	                $html = '<table class="formcontainer">';
	                $html .= '<tr><td>Course Name:</td><td>' . $course->name . '</td>';
                    $html .= '<tr><td>Start Date:</td><td>' . $course->start_date . '</td>';
                    $html .= '<tr><td>End Date:</td><td>' . $course->end_date . '</td>';
                    $html .= '<tr><td></td><td><a class="button" id="join_course">Join</a>';
                    $json = array('response' => $html);
    			}
    			
    		} else {
	    		$json = array('response' => 'No course found for this access code');
    		}
    		
    	} else {
    		$json = array('response' => 'Please enter access code.');
    	}

    	echo json_encode($json);
    }
    	
    
}

  