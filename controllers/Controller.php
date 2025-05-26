<?php
abstract class Controller
{
    protected $model;
    protected $view;

    public function __construct()
    {
        $className = get_class($this);
        if ($className !== 'ControllerAuth') {
            $numeModel = str_replace("Controller", "Model", $className);
            if (class_exists($numeModel)) {
                $this->model = new $numeModel;
            }

            $numeView = str_replace("Controller", "View", $className);
            if (class_exists($numeView)) {
                $this->view = new $numeView;
            }
        }
    }
}