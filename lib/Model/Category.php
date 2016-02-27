<?php

 namespace xepan\commerce;

 class Model_Category extends \xepan\commerce\Model_Document{
 	public $status = ['Active','InActive'];
 	public $actions = [
 						'Active'=>['view','edit','delete','inactive'],
 						'InActive'=>['view','edit','delete','active']
 					];
	var $table_alias = 'category';

	function init(){
		parent::init();

		$cat_j=$this->join('document.document_id');

		$cat_j->hasOne('xepan/commerce/ParentCategory','parent_document_id')->defaultValue('Null');

		$cat_j->addField('name')->type('text');
		$cat_j->addField('display_sequence')->type('int')->hint('chnage the sequence of category, sort by decenting order');
		$cat_j->addField('alt_text')->hint('set alt_text of image tag');
		$cat_j->addField('description')->type('text');//->display(array('form'=>'RichText'));

		$cat_j->addField('meta_title');
		$cat_j->addField('meta_description');
		$cat_j->addField('meta_keywords');

		$cat_j->add('filestore/Field_Image','cat_image_id');
		// $parent_join = $cat_j->leftJoin('xepan\commerce/category','parent_document_id');

		// $this->addExpression('category_name')->set(" 'Category Name: Parent Category Name' ");
		
		// $this->hasMany('xepan\commerce/Category','parent_document_id',null,'SubCategories');
		
	}
}
 
    