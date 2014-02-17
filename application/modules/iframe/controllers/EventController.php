<?php

class Iframe_EventController extends Zend_Controller_Action
{
	
  public function init()
  {
    if (!Zend_Auth::getInstance()->hasIdentity()){
        return false;
    }
  }
  
  public function imgUploadFormAction(){
  }
  
  public function imgHandleAction(){
    if($this->getRequest()->isPost()){
      $this->post( );
    }
    if ($this->_request->isGet()) {
      $this->get();
    }
    if ($this->_request->isDelete() || $_SERVER['REQUEST_METHOD'] == 'DELETE') {
      $this->delete();
    }
  }
  
  public function post(){
    $this->_helper->layout->disableLayout();
    $array['files'][]=array(
    'deleteType'=> "DELETE",
'deleteUrl'=> "http://atticho.no-ip.biz/jQuery-File-Upload-9.5.6/server/php/?file=Torrent%20Downloaded%20From%20ExtraTorrent.cc%20%281%29.txt",
'name'=> "Torrent Downloaded From ExtraTorrent.cc (2).txt",
'size'=> 338,
'url'=> "http://atticho.no-ip.biz/jQuery-File-Upload-9.5.6/server/php/files/Torrent%20Downloaded%20From%20ExtraTorrent.cc%20%281%29.txt"
    );
    $this->_helper->json($array);
  }
  
  public function get(){
    $this->_helper->layout->disableLayout();
    $array['files'][]=array(
    'deleteType'=> "DELETE",
'deleteUrl'=> "http://atticho.no-ip.biz/jQuery-File-Upload-9.5.6/server/php/?file=Torrent%20Downloaded%20From%20ExtraTorrent.cc%20%281%29.txt",
'name'=> "Torrent Downloaded From ExtraTorrent.cc (1).txt",
'size'=> 338,
'url'=> "http://atticho.no-ip.biz/jQuery-File-Upload-9.5.6/server/php/files/Torrent%20Downloaded%20From%20ExtraTorrent.cc%20%281%29.txt"
    );
    $this->_helper->json($array);
  }
  public function delete(){
    
  }
}

