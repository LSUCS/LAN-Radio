<?php

/* Based on bTemplate (http://www.massassi.com/bTemplate) */

class CoreTemplate {
    // Configuration variables
    private $base_path = '';
    private $reset_vars = TRUE;
    private $parent;

    // Delimeters for regular tags
    private $ldelim = '{';
    private $rdelim = ' /}';

    // Delimeters for beginnings of loops
    private $BAldelim = '{';
    private $BArdelim = '}';

    // Delimeters for ends of loops
    private $EAldelim = '{/';
    private $EArdelim = '}';

    // Internal variables
    private $scalars = array();
    private $arrays = array();
    private $carrays = array();
    private $ifs = array();
    private $modeldir = array();

    function __construct(&$parent) {
        $this->parent = $parent;
    }

    public function init($templateName, $ignoreFolder = false, $dontSetGlobals = false) {
        // Get path to template file
        if (!$ignoreFolder) {
            $router = CoreRouter::getInstance();
            $file = 'templates/_STYLE_/' . $router->getCalledController() . '/' . $templateName . '.htm';
        } else {
            $file = 'templates/_STYLE_/' . $templateName . '.htm';
        }
        // If the user is logged in, does their theme have a template for this page?
        if ($this->parent->loggedIn() && file_exists(str_replace('_STYLE_', $this->parent->LoggedUser['Theme'], $file)))
            $file = str_replace('_STYLE_', $this->parent->LoggedUser['Theme'], $file);
        else
            $file = str_replace('_STYLE_', DEFAULT_STYLE, $file);

        if (substr_compare($templateName, 'templates/', 0, 10) === 0) {
            $file = $templateName;
        }

        $this->parent->Debug['Templates'][] = $file;
        $this->base_path = INSTALL_PATH . $file;
        $this->reset_vars(true, true, true, true, true);
        // Set a few global vars
        if (!$dontSetGlobals) {
            $this->set('STATIC_SERVER', STATIC_SERVER);
            $this->set('CORE_SERVER', CORE_SERVER);
            $this->set('SHORT_NAME', SHORT_NAME);
            $this->set('DEFAULT_STYLE', DEFAULT_STYLE);
        }
    }

    /*--------------------------------------------------------------*\
        Method: process_model()
        Takes a Model and converts it into a templated form.
    \*--------------------------------------------------------------*/
    public function process_model(&$var, $tag) {
        if (is_array($var)) {
            array_walk_recursive($var, array($this, 'process_model'));
            return;
        }

        if ($var instanceof CoreModel) {
            $var_class = get_class($var);
            $cn = $var_class;
            // Model_Torrent_Episode ==> torrent_episode
            $cn = strtolower(str_ireplace('Model_', '', $cn));

            $model_template_engine = new CoreTemplate($this->parent);
            if (!isset($this->modeldir[$cn])) {
                /*
                 * Look in the following directories in order:
                 * 1) <style>/<current>/
                 * 2) <style>/models/
                 */
                $splitup = explode('_', $cn);
                $baseCheck = array(
                    'templates/_STYLE_/_CURRENT_/_CN_.htm',
                    'templates/_STYLE_/models/_CN_.htm',
                    'templates/' . DEFAULT_STYLE . '/_CURRENT_/_CN_.htm',
                    'templates/' . DEFAULT_STYLE . '/models/_CN_.htm'
                );
                $check = array();
                for ($i = 0; $i < count($splitup); $i++) {
                    $curcn = implode('_', array_slice($splitup, 0, count($splitup)-$i));
                    array_map(function ($val) use (&$curcn, &$check) {
                            $val = str_replace('_CN_', $curcn, $val);
                            $check[] = $val;
                            return $val;
                        }, $baseCheck);
                    ob_flush();
                }
                $found = false;
                $nr = CoreRouter::getInstance();
                $cc = $nr->getCalledController();
                foreach ($check as &$look) {
                    if ($this->parent->loggedIn())
                        $look = str_replace('_STYLE_', $this->parent->LoggedUser['Theme'], $look);
                    else
                        $look = str_replace('_STYLE_', DEFAULT_STYLE, $look);

                    $look = str_replace('_CURRENT_', $cc, $look);

                    if (file_exists($look)) {
                        $found = $look;
                        break;
                    }
                }
                if (!$found)
                    throw new Exception("Couldn't find a model template for " . $cn . " - searched: " . implode('; ', $check));
                $this->modeldir[$cn] = $found;
            }
            $model_template_engine->init($this->modeldir[$cn], true, false);
            if (method_exists($var, 'getTemplateVariables')) {
                foreach ($var->getTemplateVariables() as $global_key => $global_val) {
                    $model_template_engine->set($global_key, $global_val);
                }
            } else {
                foreach ($var as $global_key => $global_val) {
                    $model_template_engine->set($global_key, $global_val);
                }
            }
            $var = $model_template_engine->parse(file_get_contents($this->modeldir[$cn]));
            $this->parent->Debug['Templates'][count($this->parent->Debug['Templates'])-1] .=
                    '<br />(' . $var_class . ' - ' . $cn . ')';
        }
    }

    /*--------------------------------------------------------------*\
         Method: set()
         Sets all types of variables (scalar, loop, hash).
     \*--------------------------------------------------------------*/
    public function set($tag, $var, $if = NULL) {
        $this->process_model($var, $tag);
        if (is_array($var)) {
            $this->arrays[$tag] = $var;
            if ($if) {
                $result = $var ? TRUE : FALSE;
                $this->ifs[] = $tag;
                $this->scalars[$tag] = $result;
            }
        }
        else {
            $this->scalars[$tag] = $var;
            if ($if) $this->ifs[] = $tag;
        }
    }

    /*--------------------------------------------------------------*\
         Method: set_cloop()
         Sets a cloop (case loop).
     \*--------------------------------------------------------------*/
    public function set_cloop($tag, $array, $cases) {
        $this->carrays[$tag] = array(
            'array' => $array,
            'cases' => $cases);
    }

    /*--------------------------------------------------------------*\
         Method: reset_vars()
         Resets the template variables.
     \*--------------------------------------------------------------*/
    public function reset_vars($scalars, $arrays, $carrays, $ifs, $modeldir, $warning = true) {
        $iswarning = false;
        if ($warning) {
            if (count($this->scalars)) $iswarning = true;
            if (count($this->arrays)) $iswarning = true;
            if (count($this->carrays)) $iswarning = true;
            if (count($this->ifs)) $iswarning = true;
            if (count($this->modeldir)) $iswarning = true;
        }
        if ($scalars) $this->scalars = array();
        if ($arrays) $this->arrays = array();
        if ($carrays) $this->carrays = array();
        if ($ifs) $this->ifs = array();
        if ($modeldir) $this->modeldir = array();
        if ($iswarning) Core::get('Core')->registerError(0, "Template engine used init() AFTER variables were set(). These variables were lost!", $this->base_path, "null");
    }

    /*--------------------------------------------------------------*\
         Method: get_tags()
         Formats the tags & returns a two-element array.
     \*--------------------------------------------------------------*/
    public function get_tags($tag, $directive) {
        $tags['b'] = $this->BAldelim . $directive . $tag . $this->BArdelim;
        $tags['e'] = $this->EAldelim . $directive . $tag . $this->EArdelim;
        return $tags;
    }

    /*--------------------------------------------------------------*\
         Method: get_tag()
         Formats a tag for a scalar.
     \*--------------------------------------------------------------*/
    public function get_tag($tag) {
        return $this->ldelim . 'tag:' . $tag . $this->rdelim;
    }

    /*--------------------------------------------------------------*\
         Method: get_statement()
         Extracts a portion of a template.
     \*--------------------------------------------------------------*/
    public function get_statement($t, &$contents) {
        // Locate the statement
        $tag_length = strlen($t['b']);
        $fpos = strpos($contents, $t['b']) + $tag_length;
        $lpos = strpos($contents, $t['e']);
        $length = $lpos - $fpos;

        // Extract & return the statement
        return substr($contents, $fpos, $length);
    }

    /*--------------------------------------------------------------*\
         Method: parse()
         Parses all variables into the template.
     \*--------------------------------------------------------------*/
    public function parse($contents) {
        // Process the ifs
        if (!empty($this->ifs)) {
            foreach ($this->ifs as $value) {
                $contents = $this->parse_if($value, $contents);
            }
        }
        
        // Process the scalars
        foreach ($this->scalars as $key => $value) {
            $contents = str_replace($this->get_tag($key), $value, $contents);
        }

        // Process the arrays
        foreach ($this->arrays as $key => $array) {
            $contents = $this->parse_loop($key, $array, $contents);
        }

        // Process the carrays
        foreach ($this->carrays as $key => $array) {
            $contents = $this->parse_cloop($key, $array, $contents);
        }

        // Reset the arrays
        if ($this->reset_vars) $this->reset_vars(true, true, true, true, true, false);

        // Return the contents
        return $contents;
    }

    /*--------------------------------------------------------------*\
         Method: parse_if()
         Parses an if statement.  There is some weirdness here because
         the <else:tag> tag doesn't conform to convention, so some
         things have to be done manually.
     \*--------------------------------------------------------------*/
    public function parse_if($tag, $contents) {
        // Get the tags
        $t = $this->get_tags($tag, 'if:');

        // Get the entire statement
        $entire_statement = $this->get_statement($t, $contents);

        // Get the else tag
        $tags['b'] = NULL;
        $tags['e'] = $this->BAldelim . 'else:' . $tag . $this->BArdelim;

        // See if there's an else statement
        if (($else = strpos($entire_statement, $tags['e']))) {
            // Get the if statement
            $if = $this->get_statement($tags, $entire_statement);

            // Get the else statement
            $else = substr($entire_statement, $else + strlen($tags['e']));
        }
        else {
            $else = NULL;
            $if = $entire_statement;
        }

        // Process the if statement
        $this->scalars[$tag] ? $replace = $if : $replace = $else;

        // Parse & return the template
        return str_replace($t['b'] . $entire_statement . $t['e'], $replace, $contents);
    }

    /*--------------------------------------------------------------*\
         Method: parse_loop()
         Parses a loop (recursive function).
     \*--------------------------------------------------------------*/
    public function parse_loop($tag, $array, $contents) {
        // Get the tags & loop
        $t = $this->get_tags($tag, 'loop:');
        $loop = $this->get_statement($t, $contents);
        $parsed = NULL;

        // Process the loop
        foreach ($array as $key => $value) {
            if (is_numeric($key) && is_array($value)) {
                $i = $loop;
                foreach ($value as $key2 => $value2) {
                    if (!is_array($value2)) {
                        // Replace associative array tags
                        $i = str_replace($this->get_tag($tag . '[].' . $key2), $value2, $i);
                    }
                    else {
                        // Check to see if it's a nested loop
                        $i = $this->parse_loop($tag . '[].' . $key2, $value2, $i);
                    }
                }
            }
            elseif (is_string($key) && !is_array($value)) {
                $contents = str_replace($this->get_tag($tag . '.' . $key), $value, $contents);
            }
            elseif (!is_array($value)) {
                $i = str_replace($this->get_tag($tag . '[]'), $value, $loop);
            }

            // Add the parsed iteration
            if (isset($i)) $parsed .= rtrim($i);
        }

        // Parse & return the final loop
        return str_replace($t['b'] . $loop . $t['e'], $parsed, $contents);
    }

    /*--------------------------------------------------------------*\
         Method: parse_cloop()
         Parses a cloop (case loop) (recursive function).
     \*--------------------------------------------------------------*/
    public function parse_cloop($tag, $array, $contents) {
        // Get the tags & loop
        $t = $this->get_tags($tag, 'cloop:');
        $loop = $this->get_statement($t, $contents);

        // Set up the cases
        $array['cases'][] = 'default';
        $case_content = array();
        $parsed = NULL;

        // Get the case strings
        foreach ($array['cases'] as $case) {
            $ctags[$case] = $this->get_tags($case, 'case:');
            $case_content[$case] = $this->get_statement($ctags[$case], $loop);
        }

        // Process the loop
        foreach ($array['array'] as $key => $value) {
            if (is_numeric($key) && is_array($value)) {
                // Set up the cases
                if (isset($value['case'])) $current_case = $value['case'];
                else $current_case = 'default';
                unset($value['case']);
                $i = $case_content[$current_case];

                // Loop through each value
                foreach ($value as $key2 => $value2) {
                    $i = str_replace($this->get_tag($tag . '[].' . $key2), $value2, $i);
                }
            }

            // Add the parsed iteration
            $parsed .= rtrim($i);
        }

        // Parse & return the final loop
        return str_replace($t['b'] . $loop . $t['e'], $parsed, $contents);
    }

    /*--------------------------------------------------------------*\
         Method: push()
         Returns the parsed contents of the specified template.
     \*--------------------------------------------------------------*/
    public function push() {
        // Prepare the path
        $file = $this->base_path;
        // Open the file
        $fp = fopen($file, 'rb');
        if (!$fp) return FALSE;

        // Read the file
        $contents = fread($fp, filesize($file));

        // Close the file
        fclose($fp);

        // Check for includes
        while (true) {
            $pos = strpos($contents, '{include:');
            if ($pos === false) break;
            $filename = substr($contents, ($pos + 9), strpos($contents, '}', $pos) - ($pos + 9));
            // Get file content
            $incfile = INSTALL_PATH . 'templates/_STYLE_/common/' . $filename . '.htm';
            if ($this->parent->LoggedIn() && file_exists(str_replace('_STYLE_', $this->parent->LoggedUser['Theme'], $incfile)))
                $incfile = str_replace('_STYLE_', $this->parent->LoggedUser['Theme'], $incfile);
            else
                $incfile = str_replace('_STYLE_', DEFAULT_STYLE, $incfile);
            $fp = fopen($incfile, 'rb');
            $incdata = fread($fp, filesize($incfile));
            fclose($fp);
            $contents = str_replace('{include:' . $filename . '}', $incdata, $contents);
            // Add to debug
            $this->parent->Debug['Templates'][] = substr($file, strlen(INSTALL_PATH));
        }

        // Parse and echo the contents
        echo $this->parse($contents);
    }
}

?>