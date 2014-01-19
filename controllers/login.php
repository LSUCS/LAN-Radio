<?php

namespace Core\Controller;

use Core;

class Login extends Core\Controller {
    const ENFORCE_LOGIN = false;

    protected function __routing($pieces) {
        if (Core::loggedIn()) { // logged in
            if ($pieces[0] != 'logout') {
                $this->redirect('index');
                exit();
            } else {
                $this->action_logout();
                $this->redirect('login', 'index');
                exit();
            }
        } else { // logged out
            if ($pieces[0] == 'login') {
                exit($this->action_login());
            } else {
                exit($this->action_index());
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