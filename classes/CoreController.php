<?php
/**
 * Base controller/router class, from which all controllers inherit
 */

class CoreController {
    protected $core;
    const ENFORCE_LOGIN = true;
    const FALLBACK_TO_INDEX = false;

    /**
     * Controller base constructor.
     * If you subclass CoreController, please call the parent constructor.
     */
    public function __construct() {
        $this->core = Core::get('Core');
    }

    /**
     * Main routing function. Override this if you want to change how actions are called.
     * @throws Core404Exception
     * @param $pieces Pieces of URL
     * @return void
     */
    protected function __routing($pieces) {
    	// do that thing
        if(count($pieces) != 0) {
            $action = array_shift($pieces);
        } else {
            $action = 'index';
        }
        if(method_exists($this, 'action_' . $action)) {
            call_user_func_array(array($this, 'action_' . $action), array($pieces));
        } else {
            if (static::FALLBACK_TO_INDEX && method_exists($this, 'action_index')) {
                call_user_func_array(array($this, 'action_index'), array($pieces));
            } else {
                throw new Core404Exception();
            }
        }
    }

    /**
     * GO! This function simply checks ENFORCE_LOGIN and calls ENFORCE_LOGIN() if necessary.
     * It also calls __routing().
     * @param $pieces Pieces of URL
     * @return void
     */
    public function run($pieces) {
        if (static::ENFORCE_LOGIN) {
            $this->core->enforceLogin();
        }

        $this->__routing($pieces);
    }

    /**
     * Load and display a view, if enabled.
     * @param $viewName Name of view: view/<controllername>/<this bit here>.php
     * @param bool $render Actually render the view?
     * @param array $arguments View arguments.
     * @return void
     */
    public function showView($viewName, $render = true, $arguments = array()) {
        $controllerName = strtolower(str_replace('Controller_', '', get_class($this)));

        try {
            /*$cache_locked_var = 'viewenabled_' . $controllerName . '_' . strtolower($viewName);
            $PageEnabled = Core::get('Cache')->get_value($cache_locked_var);
            if (!$PageEnabled) {
                Core::get('DB')->query("SELECT enabled FROM views WHERE section = ? AND view = ?", array($controllerName, $viewName));
                if (Core::get('DB')->record_count() == 0)
                    $PageEnabled = '1'; // Is Enabled -- Since no db entry
                else
                    list($PageEnabled) = Core::get('DB')->next_record();
                Core::get('Cache')->cache_value($cache_locked_var, $PageEnabled);
            }
            if (!$PageEnabled)
                $this->core->niceError(123);
            */
            $className = 'View_' . $controllerName . '_' . $viewName;
            $Page = new $className($this->core, $arguments);
            if ($render) {
                $this->core->Debug['View'] = "{$className} in views/{$controllerName}/{$viewName}.php";
                list($usec, $sec) = explode(" ", microtime());
                $this->core->renderStarted = ($usec + $sec);
                $Page->render();
            }
        } catch (Exception $e) {
            //Core::get('Error')->halt("View class failed when trying to use $className!", true);
            Core::get('Error')->haltException($e);
        }
    }

    /**
     * Load and run a helper.
     * @param $helperName Name of helper
     * @return void
     */
    public function useHelper($helperName, $arguments = array()) {
        $controllerName = strtolower(str_replace('Controller_', '', get_class($this)));

        try {
            $className = 'Helper_' . ucfirst($controllerName) . '_' . ucfirst($helperName);
            $StartTime = microtime(true);
            $Helper = new $className($this->core, $arguments);
            $Helper->run();
            $EndTime = microtime(true);
			$this->Debug['Helper'] = "$className";
			$this->Debug['HelperRunTime'] = ($EndTime - $StartTime);
        } catch (Exception $e) {
            Core::get('Error')->haltException($e);
        }
    }

    /**
     * Cause a redirect.
     * @param $controllerName Name of controller to redirect to
     * @param array $pieces Arguments to pass: /<controllername>/piece/by/piece/ - array('piece', 'by', 'piece')
     * @return void
     */
    public function redirect($controllerName, $pieces = array()) {
        if (!is_array($pieces))
            $pieces = array($pieces);

        if (is_array($controllerName)) { // if is array, ignore pieces!
            $pieces = $controllerName;
            $controllerName = array_shift($pieces);
        }

        // build URL. this could be expanded!
        $url = CORE_SERVER . "$controllerName/";
        foreach ($pieces as $piece) {
            $url .= $piece . '/';
        }
        header('Location: ' . $url);
    }
}