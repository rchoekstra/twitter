<?php
class Query
{
    private $q;
    private $columns = array();
    private $table   = array();
    private $title;
    private $subtitle;
    private $charttype = "ColumnChart";
    private $event;
    private $options = array();
    
    public function setQuery($q)
    {
        $this->q = $q;
    }
    
    public function getQuery()
    {
        return $this->q;
    }
    
    public function runQuery(&$db)
    {
        // Execute query
        if($rs = $db->query($this->q)) {
            // Create columns
            $table['cols'] = array();
            foreach($this->columns as $col)
            {
                $_col = array('id' => $col['id'], 'label' => $col['label'], "type" => $col['type']);
                
                if($col['role'] != null) $_col['role'] = $col['role'];
                /*if($col['role'] == 'tooltip') $_col['p'] = array('html'=>true);*/
                $table['cols'][] = $_col; 
            }
            
            // Create rows
            $table['rows'] =array();
            while($r = $rs->fetch_assoc()) {
                    $row = array();
                    foreach($this->columns as $col)
                    {
                        $_row  = array( 'v' => (string)$r[$col['id']]);
                        if($col['formatted_value'] != null) {
                            $_row['f'] = (string)$r[$col['formatted_value']];
                        }
                        $row[] = $_row;
                    }
                    
                    $table['rows'][] = array('c' => $row);
            }
            
            $this->table = $table;
        }
    }
    
    public function addColumn($id, $label, $type,$role=null, $formatted_value=null)
    {
        $this->columns[] = array('id'              => $id
                                ,'label'           => $label
                                ,'type'            => $type
                                ,'role'            => $role
                                ,'formatted_value' => $formatted_value
                                );
    }
    
    public function setChartType($charttype)
    {
        $this->charttype = $charttype;
    }
    
    public function returnJSON()
    {
        $var_to_return = array();
        $var_to_return['metadata'] = array('title'     => $this->title,
                                           'subtitle'  => $this->subtitle,
                                           'charttype' => $this->charttype,
                                           'event'     => $this->event,
                                           'options'   => $this->options
                                           );
                                           
        $var_to_return['table'] = $this->table;
        return json_encode($var_to_return, JSON_PRETTY_PRINT);
    }
    
    public function setTitle($title)
    {
        $this->title = $title;
    }
    
    public function setSubTitle($subtitle)
    {
        $this->subtitle = $subtitle;
    }
    
    public function setEvent($event)
    {
        $this->event = $event;
    }
    
    public function setOption($key, $value)
    {
        $this->options[$key] = $value;
    }
};
