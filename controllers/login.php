<?php

namespace Core\Controller;

use Core;
use Core\Session;

class Login extends Core\Controller {
    const ENFORCE_LOGIN = false;

    protected function __routing($pieces) {
        if(Session::loggedIn()) {
            if($pieces[0] != 'logout') {
                $this->redirect('index');
            } else {
                $this->__load('logout');
                $this->redirect('login', 'index');
            }
        } else {
            if(isset($pieces[0])) {
                $this->__load($pieces[0], $pieces);
            } else {
                $this->__load('index');
            }
        }
    }

    public function action_logout() {
        unset($_SESSION['logged_user']);
        session_destroy();
        setcookie('session', '', time() - 3600, '/');
        $this->redirect(array('login', 'index'));
    }

    public function action_login() {
        $this->useHelper('processlogin');
    }

    public function action_index() {
        $this->showView('login');
    }
}