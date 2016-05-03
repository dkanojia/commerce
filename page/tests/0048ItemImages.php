<?php

/**
* description: ATK Page
* 
* @author : Gowrav Vishwakarma
* @email : gowravvishwakarma@gmail.com, info@xavoc.com
* @website : http://xepan.org
* 
*/

namespace xepan\commerce;

class page_tests_0048ItemImages extends \xepan\base\Page_Tester {
	
	public $title='Item Images';
	
	public $proper_responses=[
    
    	'test_checkEmptyRows'=>['count'=>0],
        'test_Import_Images'=>['count'=>-1]
        
    ];


    function init(){
        // $this->add('xepan\commerce\page_tests_init')->resetDB();
        $this->pdb = $this->add('DB')->connect('mysql://root:winserver@localhost/prime_gen_1');
        parent::init();
    }

    function test_checkEmptyRows(){
    	$result=[];
    	$result['count'] = $this->app->db->dsql()->table('item_image')->del('fields')->field('count(*)')->getOne();
    	return $result;
    }

    function prepare_Import_Images(){

        $item_mapping = $this->add('xepan\commerce\page_tests_init')
                            ->getMapping('item');

        $this->proper_responses['test_Import_Images']['count'] = $this->pdb->dsql()->table('xshop_item_images')->del('fields')->field('count(*)')->getOne();

        $item_image_sql = "CASE item_id "; 
        foreach ($item_mapping as $old_id => $values) {
            $item_image_sql .= " WHEN $old_id THEN ". $values['new_id'];
        }
        $item_image_sql.=" END";

        $sql="
            INSERT INTO item_image (item_id,customfield_value_id,file_id,alt_text,title) SELECT $item_image_sql ,customefieldvalue_id, item_image_id, alt_text, title FROM xshop_item_images
        ";

        $this->app->db->dsql()->expr($sql)->execute();
    }

    function test_Import_Images(){
        $set_count = $this->app->db->dsql()->table('item_image')->del('fields')->field('count(*)')->getOne();
        return ['count'=>$set_count];
    }

}
