<?php
class NewEventController extends Zend_Controller_Action {

  public function preDispatch() {
    if (!Zend_Auth::getInstance() -> hasIdentity()) {
      $this -> _redirect('/auth/login?redirect=' . urlencode($this -> getRequest() -> REQUEST_URI));
      return false;
    }
  }

  public function init() {
    $this -> view -> headTitle('Tagbees - Create Event');

    if (!isset($_SESSION['tree_json']) || $_SESSION['tree_json'] == '[]') {
      $categoryService = new Service_Tree();
      $category = $categoryService -> get(false);

      $used_cats = array();
      $js = array();
      foreach ($category as &$cat) {
        if (!in_array($cat['category_ids'], $used_cats)) {
          $js[] = $cat['category_ids'];
          $js[] = array($cat['id'], $cat['name']);
          $used_cats[] = $cat['category_ids'];
        } else {
          $key = array_search($cat['category_ids'], $js);
          $js[$key + 1][] = $cat['id'];
          $js[$key + 1][] = $cat['name'];
        }
      }
      $_SESSION['tree_json'] = json_encode($js);
    }
  }

  public function indexAction() {
    Common::getSession() -> nav = array('Home' => '/', 'Create Event' => null);

  }

  public function advancedAction() {
    Common::getSession() -> nav = array('Home' => '/', 'Create Event (Advanced)' => null);

  }

}
