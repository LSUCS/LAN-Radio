<?php

class View_Login_Login extends CorePage{
	
	public function render(){
		$this->bodyStyle = "overflow: hidden";
		$this->showHeader('Login');

        $template = Core::get('Template');
        $template->init('login');

        if ($_GET['return'][0] == '/')
            $template->set('RETURN_URL', substr($_GET['return'],1));
        else
            $template->set('RETURN_URL', 'index/');

		$template->push();
		
		$this->showFooter();
	}
	
}

?>
