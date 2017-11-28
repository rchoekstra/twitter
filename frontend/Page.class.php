<?php
class Page
{
    private $graphids = array();
    private $title = "Pagina titel";
    
    function __construct()
    {
        call_user_func_array([$this,'addGraphs'], func_get_args());
    }
    
    public function addGraphs()
    {
        $this->graphids = array_merge($this->graphids, func_get_args());
    }
    
    public function getNumGraphs()
    {
        return count($this->graphids);
    }
    
    public function getGraphId($i)
    {
        return $this->graphids[$i];
    }
    
    public function setTitle($title)
    {
        $this->title = $title;
    }
    
    public function getTitle()
    {
        return $this->title;
    }
};
?>
