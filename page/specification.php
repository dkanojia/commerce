<?php 
 namespace xepan\commerce;
 class page_specification extends \xepan\commerce\page_configurationsidebar{

	public $title='Specifications';

	function init(){
		parent::init();

		$specification = $this->add('xepan\commerce\Model_Item_Specification');

		$crud=$this->add('xepan\hr\CRUD','null',null,['view/item/specification']);
		if($crud->isEditing('add')){
			if($emp_id=$this->app->employee->id)
				$specification->addCondition('created_by_id',$emp_id);
		}
		$crud->setModel($specification);
		$crud->grid->addPaginator(25);
		$frm=$crud->grid->addQuickSearch(['name']);
		
		$frm_drop=$frm->addField('DropDown','display_type')->setValueList(['Line'=>'Line','DropDown'=>'DropDown','Color'=>'Color'])->setEmptyText('display_type');
		$frm_drop->js('change',$frm->js()->submit());

		$frm->addHook('applyFilter',function($frm,$m){
			
			if($frm['specification_id'])
				$m->addCondition('specification_id',$frm['specification_id']);
		});
	}

}