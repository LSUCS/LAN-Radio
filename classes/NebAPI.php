<?php

/**
 * Nebula API ONE TWO
 */

class NebAPI
{
    private $parent;
    private static $_apiMap = array(
        'General' => 'General',
        'Users' => 'Users',
        'Permissions' => 'Permissions'
    );

    function __construct(&$parent)
    {
        $this->parent = $parent;
    }

    public function call($apicall, $arguments = array())
    {
        try {
            // okay, split $apicall into the apicall:
            // class.method
            if (strpos($apicall, '.') === false)
                throw new APICallInvalidException("Method should be in the format Module.Method");

            $apicall = explode('.', $apicall, 2);
            if (strpos($apicall[1], '.') !== false)
                throw new APICallInvalidException("Only one . expected in request");

            if (!isset(self::$_apiMap[$apicall[0]]))
                throw new APIModuleInvalidException("Module does not exist");

            $apicall_sane = array(
                0 => 'NebAPI' . self::$_apiMap[$apicall[0]],
                1 => $apicall[1]
            );

            if (!method_exists($apicall_sane[0], $apicall_sane[1]))
                throw new APICallInvalidException("Method does not exist in module");

            $outp = call_user_func_array($apicall_sane, $arguments);
            $outp = array('status' => 'ok', 'result' => $outp);
        } catch (Exception $exc) {
            $outp = array('status' => 'err', 'error' => $exc->getMessage(), 'errno' => $exc->getCode());
        }
        exit(json_encode($outp));
    }

    public static function getMethods()
    {
        $methods = array();
        foreach (self::$_apiMap as $classSuffix) {
            $className = 'NebAPI' . $classSuffix;
            $classRefl = new ReflectionClass($className);
            $classMeths = $classRefl->getMethods(ReflectionMethod::IS_STATIC);
            foreach ($classMeths as $classMeth) {
                /** @var $classMeth ReflectionMethod */
                if (!$classMeth->isPublic()) continue; // haxx

                $dat = array(
                    'name' => $classSuffix . '.' . $classMeth->name,
                    'param_num' => array(
                        'required' => $classMeth->getNumberOfRequiredParameters(),
                        'possible' => $classMeth->getNumberOfParameters()
                    ),
                    'params' => array(),
                    'note' => 'Not documented!'
                );

                foreach ($classMeth->getParameters() as $classMethParam) {
                    /** @var $classMethParam ReflectionParameter */
                    array_push($dat['params'], array(
                                                    'name' => $classMethParam->getName(),
                                                    'optional' => $classMethParam->isOptional()
                                               ));
                }

                $docCom = explode("\n", $classMeth->getDocComment());
                $dat['note'] = substr(trim($docCom[1]), 2);

                if (!isset($methods[$classSuffix]))
                    $methods[$classSuffix] = array();

                array_push($methods[$classSuffix], $dat);
            }
        }
        return $methods;
    }
}

class APIException extends Exception
{
    const MESSAGE = 'An unknown API exception occurred';
    const CODE = 1001;

    public function __construct($message = null, $code = null, Exception $previous = null)
    {
        if (is_null($message))
            $message = static::MESSAGE;
        if (is_null($code))
            $code = static::CODE;
        parent::construct($message, $code, $previous);
    }
}

class APICallInvalidException extends APIException
{
    const MESSAGE = 'Invalid API call';
    const CODE = 1002;
}

class APIModuleInvalidException extends APIException
{
    const MESSAGE = 'Invalid API module';
    const CODE = 1003;
}

class APIArgumentsInvalidException extends APIException
{
    const MESSAGE = 'Invalid API arguments';
    const CODE = 1004;
}

class APIProtectedCallException extends APIException
{
    const MESSAGE = 'Access denied';
    const CODE = 1005;
}

abstract class NebAPIGeneral
{

    /**
     * Check that none of the elements are ''
     * @static
     * @param $somearray The input array
     * @return bool Whether no elements were empty
     */
    public static function check_args($somearray)
    {
        foreach ($somearray as $arrayitem)
            if ($arrayitem == '')
                return false;
        return true;
    }

    /**
     * Wrapper around NebAPI::getMethods
     * @see NebAPI::getMethods()
     * @static
     * @return array of arrays
     */
    public static function get_methods()
    {
        return NebAPI::getMethods();
    }

}

abstract class NebAPIUsers
{

    /**
     * Add a new user
     * @static
     * @param string $user Username
     * @param string $password Password
     * @param string $email Email
     * @param int $classnumber Class ID from database
     * @param boolean $staff
     * @return void
     */
    public static function adduser($user, $password, $email, $classnumber, $staff)
    {
        if (!NebAPIGeneral::check_args(array($user, $password, $email, $classnumber, $staff))) {
            throw new APIArgumentsInvalidException();
        }
        $passwordHasher = new NebPasswordHasher();
        $pw = $passwordHasher->hashPassword($password);
        Nebula::get('DB')->query("INSERT INTO users (`Username`, `IP`, `Class`, `Password`, `Email`, `DateSignedUp`, `Theme`, `IsStaff`, `ShowClassSymbols`) VALUES(?, '0.0.0.0', ?, ?, ?, NOW(), 'nebula', ?, '1')", array($user, $classnumber, $pw, $email, $staff));
        return true;
    }

    /**
     * Change a user's password
     * @static
     * @param string $user Username
     * @param string $newpassword New password
     * @return void
     */
    public static function changepassword($user, $newpassword)
    {
        if (!NebAPIGeneral::check_args(array($user, $newpassword))) {
            throw new APIArgumentsInvalidException();
        }
        $passwordHasher = new NebPasswordHasher();
        $pw = $passwordHasher->hashPassword($newpassword);
        Nebula::get('DB')->query("UPDATE users SET Password = ? WHERE Username = ?", array($pw, $user));
        return true;
    }

    /**
     * Delete a user
     * @static
     * @param $user Username
     * @return void
     */
    public static function deleteuser($user)
    {
        if (!NebAPIGeneral::check_args(array($user))) {
            throw new APIArgumentsInvalidException();
        }
        Nebula::get('DB')->query("DELETE FROM users WHERE Username = ?", array($user));
        return true;
    }

    public static function reloadme()
    {
        unset($_SESSION['logged_user']);
        session_destroy();
        return true;
    }

}

abstract class NebAPIPermissions
{

    /**
     * Add a new permission $permname to the group denoted by $groupname
     * @static
     * @throws APIArgumentsInvalidException|APIException
     * @param $groupname Name of group
     * @param $permname Name of permission to grant
     * @return string Permissions
     */
    public static function addperm($groupname, $permname)
    {
        if (!NebAPIGeneral::check_args(array($groupname, $permname))) {
            throw new APIArgumentsInvalidException();
        }
        Nebula::get('DB')->query("SELECT Permissions From permissiongroups WHERE GroupName = ?", array($groupname));
        if (!Nebula::get('DB')->record_count()) {
            throw new APIException("No group by that name found");
        }
        $perms = Nebula::get('DB')->next_record();
        $perms = unserialize($perms);
        array_push($perms, $permname);
        Nebula::get('DB')->query("UPDATE permissiongroups SET Permissions = ? Where GroupName = ?", array(serialize($perms), $groupname));
        return $perms;
    }

    /**
     * Disable access to a view
     * @static
     * @throws APIArgumentsInvalidException
     * @param $section Controller containing view
     * @param $view Name of view
     * @return string insert/update
     */
    public static function disableview($section, $view)
    {
        if (!NebAPIGeneral::check_args(array($section, $view))) {
            throw new APIArgumentsInvalidException();
        }
        Nebula::get('DB')->query("SELECT enabled FROM views WHERE section = ? AND view = ?", array($section, $view));
        if (Nebula::get('DB')->record_count() == 0) {
            Nebula::get('DB')->query("INSERT INTO views VALUES(?, ?, '0')", array($section, $view));
            $addtype = 'insert';
        } else {
            Nebula::get('DB')->query("UPDATE views SET enabled = '0' WHERE section = ? AND view = ?", array($section, $view));
            $addtype = 'update';
        }
        Nebula::get('Cache')->delete('viewenabled_' . strtolower($section) . '_' . strtolower($view));
        return $addtype;
    }

    /**
     * Enable access to a view
     * @static
     * @throws APIArgumentsInvalidException
     * @param $section Controller containing view
     * @param $view Name of view
     * @return string insert/update
     */
    public static function enableview($section, $view)
    {
        if (!NebAPIGeneral::check_args(array($section, $view))) {
            throw new APIArgumentsInvalidException();
        }
        Nebula::get('DB')->query("SELECT enabled FROM views WHERE section = ? AND view = ?", array($section, $view));
        if (Nebula::get('DB')->record_count() == 0) {
            Nebula::get('DB')->query("INSERT INTO views VALUES(?, ?, '1')", array($section, $view));
            $addtype = 'insert';
        } else {
            Nebula::get('DB')->query("UPDATE views SET enabled = '1' WHERE section = ? AND view = ?", array($section, $view));
            $addtype = 'update';
        }
        Nebula::get('Cache')->delete('viewenabled_' . strtolower($section) . '_' . strtolower($view));
        return $addtype;
    }

    /**
     * Get list of permissions
     * @static
     * @return array
     */
    public static function getmyperms()
    {
        $arr = array();
        array_push($arr, Nebula::get('Nebula')->LoggedUser['_PSource']); // String
        array_push($arr, Nebula::get('Nebula')->LoggedUser['_PName']); // String
        array_push($arr, Nebula::get('Nebula')->LoggedUser['_P']); // Array
        return $arr;
    }

    /**
     * Get a list of groups
     * @static
     * @return array
     */
    public static function listgroups()
    {
        Nebula::get('DB')->query("SELECT GroupName From permissiongroups");
        $arr = array();
        while (list($GroupName) = Nebula::get('DB')->next_record()) {
            array_push($arr, $GroupName);
        }
        return $arr;
    }

    /**
     * Get a list of permissions owned by this group
     * @static
     * @throws APIArgumentsInvalidException|APIException
     * @param $groupname Name of group
     * @return mixed
     */
    public static function listgroupperms($groupname)
    {
        if (!NebAPIGeneral::check_args(array($groupname))) {
            throw new APIArgumentsInvalidException();
        }
        Nebula::get('DB')->query("SELECT Permissions From permissiongroups WHERE GroupName = ?", array($groupname));
        if (!Nebula::get('DB')->record_count()) {
            throw new APIException("No group by that name found");
        }
        list($nr) = Nebula::get('DB')->next_record();
        $nr = unserialize($nr);
        return $nr;
    }

    /**
     * Remove a permission from a group
     * @static
     * @throws APIArgumentsInvalidException|APIException
     * @param $groupname Name of group
     * @param $permname Permission name to remove
     * @return array Permissions left
     */
    public static function removeperm($groupname, $permname)
    {
        if (!NebAPIGeneral::check_args(array($groupname, $permname))) {
            throw new APIArgumentsInvalidException();
        }
        Nebula::get('DB')->query("SELECT Permissions From permissiongroups WHERE GroupName = ?", array($groupname));
        if (!Nebula::get('DB')->record_count()) {
            throw new APIException("No group by that name found");
        }
        $perms = Nebula::get('DB')->next_record();
        $perms = unserialize($perms);
        $perms = array_diff($perms, array($permname));
        Nebula::get('DB')->query("UPDATE permissiongroups SET Permissions = ? Where GroupName = ?", array(serialize($perms), $groupname));
        return $perms;
    }
}

?>