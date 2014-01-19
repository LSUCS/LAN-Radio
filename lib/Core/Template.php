<?php

namespace Core;

/* Based on bTemplate (http://www.massassi.com/bTemplate) */

class Template {
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

        //Specify file manually
        if (substr_compare($templateName, 'templates/', 0, 10) === 0) {
            $file = $templateName;
        } else {
            // Get path to template file
            if (!$ignoreFolder) {
                $router = Router::getInstance();
                $file = array('templates', Config::DEFAULT_STYLE, $router->getCalledController(), $templateName . '.htm');
            } else {
                $file = array('templates', Config::DEFAULT_STYLE, $templateName . '.htm');
            }
            $file = implode(DIRECTORY_SEPARATOR, $file);
        }
        $this->parent->Debug['Templates'][] = $file;
        $this->base_path = Config::INSTALL_PATH . DIRECTORY_SEPARATOR . $file;
        $this->reset_vars(true, true, true, true, true);
        // Set a few global vars
        if (!$dontSetGlobals) {
            $this->set('STATIC_SERVER', Config::STATIC_SERVER);
            $this->set('CORE_SERVER', Config::CORE_SERVER);
            $this->set('SHORT_NAME', Config::SHORT_NAME);
            $this->set('DEFAULT_STYLE', Config::DEFAULT_STYLE);
        }
    }

    /*--------------------------------------------------------------*\
         Method: set()
         Sets all types of variables (scalar, loop, hash).
     \*--------------------------------------------------------------*/
    public function set($tag, $var, $if = NULL) {
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
    public function set_cloop($tag, $casename, $array, $cases) {
        $this->carrays[$tag] = array(
            'array' => $array,
            'cases' => $cases,
            'casename' => $casename
        );
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
            if (is_array($value) || is_object($value)) {
                $i = $loop;
                foreach ($value as $key2 => $value2) {
                    if (!is_array($value2) && !is_object($value2)) {
                        // Replace associative array tags
                        $i = str_replace($this->get_tag($tag . '[].' . $key2), $value2, $i);
                    }
                    else {
                        // Check to see if it's a nested loop
                        $i = $this->parse_loop($tag . '[].' . $key2, $value2, $i);
                    }
                }
            } elseif (is_string($key) && !is_array($value)) {
                $contents = str_replace($this->get_tag($tag . '.' . $key), $value, $contents);
            } elseif (!is_array($value)) {
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
        $array['cases'][] = 'DEFAULT';
        $case_content = array();
        $parsed = NULL;
        
        $casename = $array['casename'];
        unset($array['casename']);

        // Get the case strings
        foreach ($array['cases'] as $case) {
            $ctags[$case] = $this->get_tags($case, $casename . ':');
            $case_content[$case] = $this->get_statement($ctags[$case], $loop);
        }

        // Process the loop
        foreach ($array['array'] as $value) {
            if (is_array($value) || is_object($value)) {
                // Set up the cases
                if (is_object($value) && isset($value->$casename)) {
                    $current_case = $value->$casename;
                    unset($value->$casename);
                } elseif(is_array($value) && isset($value[$casename])) {
                    $current_case = $value[$casename];
                    unset($value[$casename]);
                } else {
                    $current_case = 'DEFAULT';
                }
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
        if(!$fp) throw new TemplateNotFound("Could not find " . $file);

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
            $incfile = Config::INSTALL_PATH . 'templates/_STYLE_/common/' . $filename . '.htm';
            if ($this->parent->LoggedIn() && file_exists(str_replace('_STYLE_', $this->parent->LoggedUser['Theme'], $incfile)))
                $incfile = str_replace('_STYLE_', $this->parent->LoggedUser['Theme'], $incfile);
            else
                $incfile = str_replace('_STYLE_', Config::DEFAULT_STYLE, $incfile);
            $fp = fopen($incfile, 'rb');
            $incdata = fread($fp, filesize($incfile));
            fclose($fp);
            $contents = str_replace('{include:' . $filename . '}', $incdata, $contents);
            // Add to debug
            $this->parent->Debug['Templates'][] = substr($file, strlen(Config::INSTALL_PATH));
        }

        // Parse and echo the contents
        echo $this->parse($contents);
    }
}
class TemplateNotFound extends \Exception {}