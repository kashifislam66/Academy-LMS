<?php
/*
** Abstract Model
** All other models will be using this Abstract Model
*/
class Abstract_model extends CI_Model 
{
    protected $table_name = "admins";

    function __construct() 
	{
        parent::__construct();
    }

    public function get_where($select, $where = '', $return_type = true, $order_by = '', $limit = '', $groupby = '')
    {
        $this->db->select ($select);
        $this->db->from ($this->table_name);
        ($where) ? $this->db->where ($where) : '';
        if ($groupby != '')
            $this->db->group_by ($groupby);
        if ($order_by != '')
            $this->db->order_by ($order_by);

        if ($limit != '')
            $this->db->limit ($limit);

        if ($return_type)
        {
            $result = $this->db->get ()->result ('array');
        }
        else
        {
            $result = $this->db->get ()->result ();
        }
        return $result;
    }

    public function get_by($column, $value, $where_col = '', $where_val = '', $order_by_column = '', $order_by_value = '')
	{
        $where = array();
        if ($where_col != "") 
		{
            $where = array($column => $value, $where_col => $where_val);
        } 
		else 
		{
            $where = array($column => $value);
        }
        if ($order_by_column != '' && $order_by_value != '')
		{
            $this->db->order_by($order_by_column, $order_by_value);
        }
        
		$query = $this->db->get_where($this->table_name, $where);
        return $query->result_array();
    }
    
    public function get_by_where($select = '' ,$where = '', $order_by_column = '', $order_by_value = '', $where_in_check = 0, $where_in_key = '', $where_in_value = '' , $or_where = '' , $limit = 0, $offset = 0) 
	{
        if($select != '')
        {
            $this->db->select($select);
        }
        
        if($limit != 0)
        {
            $this->db->limit($limit, $offset);
        }
        
        if ($order_by_column != '' && $order_by_value != '') {
            $this->db->order_by($order_by_column, $order_by_value);
        }

        if ($where_in_check && $where_in_key != '' && $where_in_value != '') {
            $this->db->where_in($where_in_key, $where_in_value);
        }
        
        if((count($where) > 0 && is_array($where)) || (!is_array($where) && $where != '' ) )
        {
            $this->db->where($where); 
        }
        
        if((count($or_where) > 0 && is_array($or_where)) || (!is_array($or_where) && $or_where != '' ) )
        {
            $this->db->or_where($or_where);
        }
        
        $query = $this->db->get($this->table_name);
		return $query->result_array();
    }

    public function get_all($where_column = '', $where_value = '', $order_by_column = '', $order_by_value = '' , $limit = 0, $offset = 0) 
	{
        if ($where_column != '' && $where_value != '') 
		{
            $this->db->where($where_column, $where_value);
        }
        if ($order_by_column != '' && $order_by_value != '') 
		{
            $this->db->order_by($order_by_column, $order_by_value);
        }
		if($limit != 0)
        {
            $this->db->limit($limit, $offset);
        }
        $query = $this->db->get($this->table_name);
        return $query->result_array();
    }

    public function save($data) 
	{
        $this->db->insert($this->table_name, $data);
        return $this->db->insert_id();
    }
	
	public function save_batch($data) 
	{
        $this->db->insert_batch($this->table_name, $data);
        return true;
    }

    public function update($row_id, $data) 
	{
        $this->db->where('id', $row_id);
        return $this->db->update($this->table_name, $data);
    }

    public function update_by($column, $row_id, $data) 
	{
        $this->db->where($column, $row_id);
        return $this->db->update($this->table_name, $data);
    }
    
    public function update_by_where($data, $where) 
	{
        $this->db->where($where);
        $this->db->update($this->table_name, $data);
    }

    public function delete($id) 
	{
        $this->db->delete($this->table_name, array('id' => $id));
    }
	
	public function delete_where($array) 
	{
        $this->db->delete($this->table_name, $array);
    }

    public function delete_by($column, $id) 
	{
        return $this->db->delete($this->table_name, array($column => $id));
    }
	
	public function or_where($data)
	{
		$this->db->select('*');
		$this->db->or_where_in('role', $data);
		$query = $this->db->get($this->table_name);
        $query->result_array();
	}

    public function get_limited_rows($column, $value, $where_col = '', $where_val = '', $offset = 0, $limit = 0, $order_by_column = '', $order_by_value = '')
	{
        $where = array();
        if ($where_col != '' && $where_val != '') 
		{
            $where = array($column => $value, $where_col => $where_val);
        }
		else 
		{
            $where = array($column => $value);
        }
        if ($order_by_column != '' && $order_by_value != '') 
		{
            $this->db->order_by($order_by_column, $order_by_value);
        }
        
		$query = $this->db->get_where($this->table_name, $where, $limit, $offset);
        $query->result_array();
    }

    public function get_by_join($select = '' , $from_table = '' , $join_array = array() ,$where = '', $order_by_column = '', $order_by_value = '', $where_in_check = 0, $where_in_key = '', $where_in_value = '' , $or_where = '' , $limit = 0, $offset = 0, $formatting=true) 
	{
        if($select != '')
        {
            $this->db->select($select, $formatting);
        }
        
        if($from_table != '')
        {
            $this->db->from($from_table);
        }
        else
        {
            $this->db->from($this->table_name);
        }
        if(count($join_array) > 0)
        {
            foreach($join_array as $key => $v)
            {
				if(isset($v['join_type']) && !empty($v['join_type']))
                	$this->db->join($v['table_name'], $v['join_on'], $v['join_type']);
				else
					$this->db->join($v['table_name'], $v['join_on']);
            }
        }
        
        if($limit != 0)
        {
            $this->db->limit($limit, $offset);
        }
        
        if ($order_by_column != '' && $order_by_value != '') {
            $this->db->order_by($order_by_column, $order_by_value);
        }

        if ($where_in_check && $where_in_key != '' && $where_in_value != '') {
            $this->db->where_in($where_in_key, $where_in_value);
        }

        if(( is_array($where) && count($where) > 0) || (!is_array($where) && $where != '' ))
        {
            $this->db->where($where); 
        }
        
        if(( is_array($or_where) &&  count($or_where) > 0) || (!is_array($or_where) && $or_where != '' ) )
        {
            $this->db->or_where($or_where);
        }
        
        $query = $this->db->get();
// $this->db->last_query();exit();

        return $query->result_array();
    }

    public function get_by_join_new($select = '' , $from_table = '' , $join_array = array() ,$where = '', $order_by_column = '', $order_by_value = '', $where_in_check = 0, $where_in_key = '', $where_in_value = '' , $or_where = '' , $limit = 0, $offset = 0, $formatting=true ,$groupby) 
    {
        if($select != '')
        {
            $this->db->select($select, $formatting);
        }
        
        if($from_table != '')
        {
            $this->db->from($from_table);
        }
        else
        {
            $this->db->from($this->table_name);
        }
        if(count($join_array) > 0)
        {
            foreach($join_array as $key => $v)
            {
                if(isset($v['join_type']) && !empty($v['join_type']))
                    $this->db->join($v['table_name'], $v['join_on'], $v['join_type']);
                else
                    $this->db->join($v['table_name'], $v['join_on']);
            }
        }
        
        if($limit != 0)
        {
            $this->db->limit($limit, $offset);
        }
        
        if ($order_by_column != '' && $order_by_value != '') {
            $this->db->order_by($order_by_column, $order_by_value);
        }

        if ($where_in_check && $where_in_key != '' && $where_in_value != '') {
            $this->db->where_in($where_in_key, $where_in_value);
        }

        if( is_array($where) && (count($where) > 0 ) || (!is_array($where) && $where != '' ) )
        {
            $this->db->where($where); 
        }

        if ($groupby != '') {
            $this->db->group_by ($groupby);
        }
        
        if(( is_array($or_where) && count($or_where) > 0 ) || (!is_array($or_where) && $or_where != '' ) )
        {
            $this->db->or_where($or_where);
        }
        
        $query = $this->db->get();
        return $query->result_array();
    }

    public function get_by_join_total_rows($select = '' , $from_table = '' , $join_array = array() ,$where = '', $order_by_column = '', $order_by_value = '', $where_in_check = 0, $where_in_key = '', $where_in_value = '' , $or_where = '' , $limit = 0, $offset = 0)
	{
        if($select != '')
        {
            $this->db->select($select);
        }

        if($from_table != '')
        {
            $this->db->from($from_table);
        }
        else
        {
            $this->db->from($this->table_name);
        }
        if(count($join_array) > 0)
        {
            foreach($join_array as $key => $v)
            {
				if(isset($v['join_type']) && !empty($v['join_type']))
                	$this->db->join($v['table_name'], $v['join_on'], $v['join_type']);
				else
					$this->db->join($v['table_name'], $v['join_on']);
            }
        }

        if($limit != 0)
        {
            $this->db->limit($limit, $offset);
        }

        if ($order_by_column != '' && $order_by_value != '') {
            $this->db->order_by($order_by_column, $order_by_value);
        }

        if ($where_in_check && $where_in_key != '' && $where_in_value != '') {
            $this->db->where_in($where_in_key, $where_in_value);
        }

        if((count($where) > 0 && is_array($where)) || (!is_array($where) && $where != '' ) )
        {
            $this->db->where($where);
        }

        if((count($or_where) > 0 && is_array($or_where)) || (!is_array($or_where) && $or_where != '' ) )
        {
            $this->db->or_where($or_where);
        }

        $query = $this->db->get();
        return $query->num_rows();
    }

    public function get_column($select ,$where = '')
    {
        if((count($where) > 0 && is_array($where)) || (!is_array($where) && $where != '' ) )
        {
            $this->db->where($where); 
        }
        
        if($select == 'count')
        {
            return $this->db->count_all_results($this->table_name);   
        }
        else
        {
            if($select != '')
            {
                $this->db->select($select);
            }
            return $this->db->get($this->table_name)->row_array();
        }
    }

    public function get_specific_column($select ,$where = '')
    {
        if((count($where) > 0 && is_array($where)) || (!is_array($where) && $where != '' ) )
        {
            $this->db->where($where); 
        }
        
        if($select == 'count')
        {
            return $this->db->count_all_results($this->table_name);   
        }
        else
        {
            if($select != '')
            {
                $this->db->select($select);
            }
            $result = $this->db->get($this->table_name)->row_array();
			if(!empty($result))
				return $result[$select];
			else
				return '';
        }
    }

    public function count_rows($where=array())
	{
		$this->db->select('*')->from($this->table_name);
		if(!empty($where))
		{
			$this->db->where($where);
		}
		return $this->db->get()->num_rows();
	}

    public function get_max($column_name, $rename = '')
	{
		if($rename != '')
			$this->db->select_max($column_name, $rename);
		else
			$this->db->select_max($column_name);
		$query = $this->db->get($this->table_name);
		$row = $query->row_array();
		if($row[$column_name])
			return $row[$column_name];
		else
			return 1;
	}

    public function search_by_like($column, $value, $where_col='', $where_val='', $like = 'both', $order_by_column = '', $order_by_value = '', $limit = 0, $offset = 0){
		$where = array();
		
		$this->db->like($column, $value, $like);
		
		if($where_col != ""){
			$where = array($where_col => $where_val);
		}
		if($limit != 0)
        {
			$this->db->limit($limit, $offset);
        }
		$query = $this->db->get_where($this->table_name, $where);
		
		//echo $this->db->last_query();
		return $query->result('array');
	}

    public function search_by_like_where($column, $value, $where='', $like = 'both', $order_by_column = '', $order_by_value = '', $limit = 0, $offset = 0){
		
		$this->db->select('*');
		$this->db->from($this->table_name);
  if($where != ""){
   $this->db->where($where);
		}
	if($value!=''){
		$this->db->like($column, $value, $like);
  }
		if($limit != 0)
        {
			$this->db->limit($limit, $offset);
        }
		//$query = $this->db->get_where($this->table_name, $where);
		$query = $this->db->get();
		
		//echo $this->db->last_query();
		return $query->result('array');
	}

    public function get_sum($where){
        $this->db->select_sum('package_amount');
        $this->db->from('user_package_historys');
        $this->db->where($where);
        $query = $this->db->get();
        return $query->result('array');
    }

    public function fetch_amount_sum($where){
        
    }
}
