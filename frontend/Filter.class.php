<?php
class Filter
{
    private $filters = array();
    
    public function addFilter($key, $value, $operator='=', $id=-1)
    {
        if($id==-1) {
            $this->filters[] = array('key'=>$key, 'operator'=> $operator, 'value'=>$value);
        } else {
            $this->filters[$id] = array('key'=>$key, 'operator'=> $operator, 'value'=>$value);
        }
    }
    
    public function getFilter($alias="")
    {
        $f = "";
        if(count($this->filters)==0) { return "1=1"; }
        foreach($this->filters as $key => $filter)
        {
            if($key >= 1) $f = $f . " and";
            if($alias=="") 
            {
                $f = $f . " " . $filter['key'] . $filter['operator'] . $filter['value'];
            }
            else
            {
                $f = $f . " " . $alias . "." . $filter['key'] . $filter['operator'] . $filter['value'];
            }
        }
        return $f;
    }
    
    public function getFilterCount()
    {
        return count($this->filters);
    }
};
?>