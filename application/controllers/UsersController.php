<?php
class UsersController extends Zend_Controller_Action
{
    public function indexAction()
    {
        Common::getSession()->nav=array(
            'Home' => '/',
            'Find User' => NULL
        );
    }
}
