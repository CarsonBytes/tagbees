<?php

class Ajax_ItemController extends Zend_Controller_Action
{
	protected $imageService;
	private $params;

	public function init()
    {
		$this->params=$this->_request->getParams();
	    $this->db = Zend_Db_Table::getDefaultAdapter();
    }

	public function getSelectedTagsByIdsAction(){
		$select = $this->db->select()
			->from(array('i'=>'item'),array('id','type','name','slug_name','score'))
			->joinLeft(array('ui'=>'user_bookmark'),
                    'ui.item_id = i.id',
					array(
		            	'count' => 'COUNT(ui.item_id)'
					)
			)
			->where('i.id in (?)', $this->params['ids'])
			->where('i.status = 1')
			->group('i.id');
			
		$result= $this->db->fetchAll($select);
		
		$array=array();
		foreach ($result as $value){
			$array[]=array(
				'id'=>$value['id'],
				'name'=>$value['name'],
				'slug_name'=>$value['slug_name'],
				'type'=>$value['type'],
				'count'=>$value['count'],
				'score'=>$value['score']
			);
		}

		$this->_helper->json($array);
		
	}
   public function fetchAction()
    {
		$array=array();

		$where='i.status = 1';

		$where.=' AND ';

    	/*if (Zend_Auth::getInstance()->hasIdentity()){
    		$identity=Zend_Auth::getInstance()->getIdentity();
			//$where.=$this->db->quoteInto('slug_name != ?',$identity->username);
			//$where.=' AND ';
    	}*/

		$select = $this->db->select()
			->from(array('i'=>'item'),array('id','type','name','slug_name','score'))
			->where('i.type not like ?','%category%')
			->joinLeft(array('ui'=>'user_bookmark'),
                    'ui.item_id = i.id',
					array(
		            	'count' => 'COUNT(ui.item_id)'
					)
			)
			->group('i.id')
			->order(array('i.score DESC', 'count DESC'))
			->limit(30);


		$terms_select=$this->db->select()
			->where('i.name like ?','%'.$this->params['term'].'%')
			->where('i.slug_name like ?','%'.$this->params['term'].'%');

		$select->where(join(" ",$terms_select->getPart(Zend_Db_Select::WHERE)));

		$result= $this->db->fetchAll($select);

		foreach ($result as $value){
			$array[]=array(
				'id'=>$value['id'],
				'name'=>$value['name'],
				'slug_name'=>$value['slug_name'],
				'type'=>$value['type'],
				'count'=>$value['count'],
				'score'=>$value['score']
			);
		}

		$this->_helper->json($array);

		/*if (!empty($array))
			$this->_helper->json($this->subval_sort($array,'count'));
		else $this->_helper->json($array);*/
    }

    public function addAction(){
    	$values = $_POST;
        foreach($_POST as $key => $value){
			if ($value=='') unset($values[$key]);
        }

        $postService=new Service_Post();
        $slug_name=$postService->add($values);
        
        $this->_helper->FlashMessenger(array('success'=>'Your event \"'.$_POST['name'].'\" has been created.'));
		$this->_helper->json(array('result'=>1,'slug_name'=>$slug_name));
    }

    private function subval_sort($a,$subkey) {
		foreach($a as $k=>$v) {
			$b[$k] = strtolower($v[$subkey]);
		}
		arsort($b);
		foreach($b as $key=>$val) {
			$c[] = $a[$key];
		}
		return $c;
	}

}

