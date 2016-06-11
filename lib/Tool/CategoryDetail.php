<?php

namespace xepan\commerce;

class Tool_CategoryDetail extends \xepan\cms\View_Tool{
	public $options = [
				'layout'=>'categorydetail',
				'show_image'=>true,
				'show_price'=>true,
				'show_description'=>true,
				'show_item_count' =>true,
				'include_child_category'=>true,
				'redirect_page'=>'index',
				'custom_template'=>''
			];

	function init(){
		parent::init();

		$category = $this->add('xepan\commerce\Model_Category')->addCondition('id',$_GET['xsnb_category_id']);
		$category->tryLoadAny();

		if(!$category->loaded()){
			$this->add('View_Error')->set('Category not found');
			$this->template->del('counts');
			return;
		}

		$message = $this->validateRequiredOption();
		if($message){
			$this->template->del('root_wrapper');
			$this->add('View_Warning')->set($message);
			return;
		}

		$this->add('xepan\cms\Controller_Tool_Optionhelper',['model'=>$category]);
		$this->setModel($category);

		$url = $category['custom_link']?$category['custom_link']:$this->options['redirect_page'];
		$url = $this->app->url($url,['xsnb_category_id'=>$this->model->id]);
		
		$description = $this->model['description'];
		$description = str_replace("{{url}}", $url, $description);
		$description = str_replace("{{category_id}}", $category->id, $description);
		$this->template->setHtml('category_description',$description);
	}

	function defaultTemplate(){
		$template_name =  $this->options['layout'];

		if($this->options['custom_template']){
			$path = getcwd()."/websites/".$this->app->current_website_name."/www/view/tool/".$this->options['custom_template'].".html";					
			if(file_exists($path)){				
				$template_name = $this->options['custom_template'];
			}
		}		
		return ["view/tool/".$template_name];
	}

	function validateRequiredOption(){

		if($this->options['custom_template']){
			$path = getcwd()."/websites/".$this->app->current_website_name."/www/view/tool/".$this->options['custom_template'].".html";
			if(!file_exists($path)){
				return "custom template not found";
			}
		}

		return 0;
	}
}