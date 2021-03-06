<?php

namespace System;

class Loader {

    private $library;
    private $session;


    public function __construct($library, $session) {
        $this->library = $library;
        $this->session = $session;
    }
    

    /**
     * Load a model in the indicated directory
     * @param dir the model directory
     * @return object the model
     */
    public function model(string $dir) {
        //Sanitize directory
        $dir = preg_replace('/[^a-zA-Z0-9_\/]/', '', $dir);
        $file_path = 'app/model/' . $dir . '.php';

        if (!$this->library->modelExists($dir)) {
            error_log("Warning: The model '" . $dir . "' doesn't exists"); 
            return null;
        }

        $dir = str_replace('/', '\\', $dir);
        $class = '\\app\\model\\' . $dir;

        $model = new $class($this, $this->library, $this->session);
        $model->index();

        return $model;
    }


    /**
     * Load a controller in the indicated directory
     * @param string $dir the controller directory
     * @return object the controller
     */
    public function controller(string $dir) {
        //Sanitize directory
        $dir = preg_replace('/[^a-zA-Z0-9_\/]/', '', $dir);

        //load controller default function and return it
        if ($this->library->controllerExists($dir)) {
            $controller = $this->getController($dir);
            $controller->index();
            return $controller;
        }

        //Get a possible function from the url
        $function = substr($dir, strrpos($dir, '/') + 1);
        $dir = substr($dir, 0, strrpos($dir, '/'));

        //load controller indicated function and return it
        if ($this->library->controllerExists($dir)) {
            $controller = $this->getController($dir);
            $controller->$function();
            return $controller;
        }

        return null;
    }


    /**
     * Get a controller with its main variables initialized
     * @param string $dir the controller directory
     * @return object the controller with its main variables initialized
     */
    private function getController(string $dir) {
        $dir = str_replace('/', '\\', $dir);
        $class = '\\app\\controller\\' . $dir;
        
        $controller = new $class($this, $this->library, $this->session);
        return $controller;
    }


    /**
     * Load a language in the indicated directory
     * @param string $dir the language directory
     * @param string $language the language selected
     */
    public function language(string $dir, string $language = LANGUAGE) {
        //Sanitize directory
        $dir = preg_replace('/[^a-zA-Z0-9_\/]/', '', $dir);
        $file_path = 'app/language/' . $language . '/' . $dir . '.php';
        
        if ($this->library->languageExists($dir)) {
            include_once($file_path);
        }

        if (!isset($data)) {
            error_log("Warning: The " . $language . " language for '" . $dir . "' doesn't exists"); 
        } else {
            return $data;
        }

    }


    /**
     * Load a library in the indicated directory
     * @param string $dir the library directory
     */
    public function library(string $dir) {
        $dir = $this->library->sanitizeURL($dir);
        $name = substr($dir, strrpos($dir, '/'));

        if ($name == 'library') {
            error_log("Warning: The library shouldn't be named library"); 
            return null;
        }
        
        if (!$this->library->libraryExists($dir)) {
            error_log("Warning: The library '" . $dir . "' doesn't exists"); 
            return null;
        }

        //Initialize the library for the object which called this function
        $dir = str_replace('/', '\\', $dir);
        $className = '\\App\\Library\\' . $dir;
        return new $className;
    }

    
    /**
     * Load a view in the indicated directory
     * @param string $dir the view directory
     */
    public function view(string $dir, array $data = array()) {
        $dir = preg_replace('/[^a-zA-Z0-9_\/]/', '', $dir);
        echo $this->formatTemplate($dir, $data);
    }


    /**
     * Get a view in the indicated directory
     * @param string $dir the view directory
     * @param array $data the data
     * @return string the view
     */
    public function getView(string $dir, array $data = array()) {
        $dir = preg_replace('/[^a-zA-Z0-9_\/]/', '', $dir);
        return $this->formatTemplate($dir, $data);
    }


    /**
     * Apply the template format over a view and renders it
     * @param string $dir the view directory
     * @param array $data the data array present in the view
     * @return object the view content
     */
    private function formatTemplate(string $dir, array $data) {
        $file_path = 'app/view/' . $dir;

        //Error
        if (file_exists($file_path . '.php')) {
            $content = file_get_contents($file_path . '.php');
        } else if (file_exists($file_path . '.html')) {
            $content = file_get_contents($file_path . '.html');
        } else {
            error_log("Error: View '" . $dir . "' doesn't exists");
            return;
        }

        //Variables in data array
        if (is_array($data)) {
            extract($data);
        }
        
        //Tags
        $search = array('{{', '}}', '{%', '%}');
        $replace = array('<?php echo ', '?>', '<?php ', ' ?>');
        $content = str_replace($search, $replace, $content);
        
        //Cache system
        include_once($this->getCache($dir, $content));
    }

    
    /**
     * Create the cache file if doesn't exists and return it
     * @param string $dir the view directory
     * @param string $content the original file content
     * @return string the cache file path
     */
    private function getCache(string $dir, string $content) {
        $file_path = 'cache/tmp_' . $dir . '.php';

        if (!file_exists('cache')) {
            mkdir('cache', 0777, true);
        }

        if (!file_exists($file_path)) {
            $file = fopen($file_path, 'w');
            fwrite($file, $content);
            fclose($file);
        }

        return $file_path;
    }


    /**
     * Load the 404 view page
     */
    public function redirect404() {
        $controller = $this->controller('_404');
        die();
    }

}