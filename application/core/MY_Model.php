<?php
/************************************************************
** @Description: 基础model
** @Author: haodaquan
** @Date:   2016-06-03 12:21:01
** @Last Modified by:   haodaquan
** @Last Modified time: 2017-02-14 09:45:08
*************************************************************/
if (!defined('BASEPATH')) exit('No direct script access allowed');
class MY_Model extends CI_Model
{
	
	/**
	* [__construct 初始化方法]
	*/
	public function __construct()
	{
	  	parent::__construct();
	  	$database = ENVIRONMENT=='production' ? 'pipi_cms' : 'pipi_cms';
	  	$this->db_pp = $this->load->database($database,true);
	}

	/**
	* [get_array_result sql方式查询获取数组]
	* @Date   2016-06-27
	* @param  [type]     $query [query]
	* @return [type]           [数组]
	*/
	public function get_array_result($query)
	{
		$data = [];
		if(isset($query->num_rows) && $query->num_rows>0) $data = $query->result_array();
		return $data;
	}


	/**
	* [queryList 单表查询数据，子类一般需要重写]
	* @Author haodaquan
	* @Date   2016-04-06
	* @param  [type]     $param [wehre,sort,limit]
	* @return [type]          [description]
	*/
	public function queryList($param)
	{
		$map = $this->queryParam($param);
		$totalCount = $this->getCount($map['where']);

		$sql   = "SELECT * FROM ". $this->_table.' '.$map['where']. 
				$map['orderby'].' LIMIT '.($map['current_page']-1)*$map['page_size'].','.$map['page_size'];
		$query = $this->db_pp->query($sql);
		$items = $query->result_array();
		//dump($sql);
		return $this->returnData(200,'success',$items,$totalCount);
	}

	/**
	* [returnData 组成返回数据]
	* @Author haodaquan
	* @Date   2016-04-06
	* @param  [type]     $status     [状态码，200，300，500]
	* @param  [type]     $info       [状态信息]
	* @param  [type]     $data       [返回信息]
	* @param  [type]     $totalCount [查询时返回条数]
	* @return [type]                 [返回数组]
	*/
	protected function returnData($status,$info,$items,$totalCount='')
	{
		$data = [];
		$data['status']  = $status;
		$data['message'] = $info;
		if($totalCount==='')
		{
			$data['data'] = $items;
		}else{
		$data['data'] = array(
		  'totalCount'=>$totalCount,
		  'items'=>$items
		  );
		}
		return $data;
	}

	/**
	* [queryParam mmgrid处理查询数据 TODO安全过滤]
	* @Author haodaquan
	* @Date   2016-11-17
	* @param  [type]     $param [查询参数]
	* @return [type]            [description]
	*/
	protected function queryParam($param)
	{
		$where = ' WHERE 1=1 ';
		//查询分页
		$limit = isset($param['limit']) ? $param['limit'] : 10;
		$page  = isset($param['page']) ? $param['page'] : 1;
		unset($param['page']);
		unset($param['limit']);

		#排序
		$orderby = '';
		if(isset($param['sort']))
		{
			$sortArr = explode('.',$param['sort']);
			$orderby = ' ORDER BY '.$sortArr[0].' '.$sortArr[1];
		}


		$allowedQuery = ['>','>=','<','<=','in','like','=','<>'];#允许的搜索条件 默认全是and关系
		#搜索情况下
		foreach ($param as $key => $value) {

			$keyArr = explode('|',$key);

			if(!isset($keyArr[1])) continue;
			if(!in_array($keyArr[1],$allowedQuery)) continue;
			if($value==='') continue;
			if($value==-9) continue;

			switch ($keyArr[1]) {
				case 'like':
					$where .= ' AND '.$keyArr[0].' like "%'.$value.'%" ';
					break;
				case 'in':
					$where .= ' AND '.$keyArr[0].' in ('.$value.') ';
					break;
				default:
					$where .= ' AND '.$keyArr[0].$keyArr[1].'"'.$value.'"';
					break;
			}
		}
		//dump(['where'=>$where,'orderby'=>$orderby,'page_size'=>$limit,'current_page'=>$page]);
		return ['where'=>$where,'orderby'=>$orderby,'page_size'=>$limit,'current_page'=>$page];

	}
	/**
	 * [getCount 获取数据条数]
	 * @Author haodaquan
	 * @Date   2016-04-06
	 * @param  string      $where [ WHERE 查询条件]
	 * @return [type]            [description]
	 */
	public function getCount($where='')
	{
		$total_sql = "SELECT count(*) as count FROM ". $this->_table .' '. $where;
		$_total = $this->db_pp->query($total_sql)->result_array();
		return isset($_total[0]['count']) ? $_total[0]['count'] : 0;
	}


	########################
	#
	# 常用增删改查 基础类方法 START
	#
	########################
	
	/**
	 * [getConditionData 有条件]
	 * @param  string $field [获取字段]
	 * @param  string $where [条件]
	 * @param  string $order [id desc]
	 * @param  string $where [1,10]
	 * 
	 * @return [type]        [description]
	 */
	public function getConditionData($field='*',$where='1=1',$order='',$limit='')
	{
		$sql   = "SELECT ".$field
				." FROM ".$this->_table
				.' WHERE '.$where;
		$sql .= $order ? ' ORDER BY '.$order : '';
		$sql .= $limit ? ' limit '.$limit : '';
		//dump($sql);
		return $this->db_pp->query($sql)->result_array();
	}

	/**
	 * [addData 新增数据]
	 * @param array $data [数据]
	 * @param array $add_time [是否自动增加add_time字段]
	 * @return int id
	 */
	public function addData($data=[],$add_time=1)
	{
		if (empty($data)) return false;
		if($add_time==1) $data['add_time'] = time();
		$this->db_pp->insert($this->_table,$data);
		//return $this->db_pp->last_query();
		return $this->db_pp->insert_id();
	}

	/**
	 * [editData 修改]
	 * @param  array  $data  [数组]
	 * @param  string $where [字符串条件]
	 * @return [type]        [false,or int 0,1]
	 */
	public function editData($data=[],$where='')
	{
		if(!$where || empty($data)) return false;
		$data['edit_time'] = time();
		$this->db_pp->where($where); 
		$this->db_pp->update($this->_table,$data);
		//return $this->db_pp->last_query();
		return $this->db_pp->affected_rows();
	}

	/**
	 * [saveData 更新或新增商品]
	 * @param  [type] $data  [一维数组]
	 * @param  string $where [条件]
	 * @return [type]        [description]
	 */
	public function saveData($data=[],$where='')
	{
		$res = $this->getConditionData('*',$where);	
		return $res ? $this->editData($data,$where) : $this->addData($data,$where);	
	}

	/**
	 * [delData 删除 慎用，一般采用eidt修改状态实现]
	 * @param  string $where [description]
	 * @return [type]        [description]
	 */
	public function delData($where=[])
	{
		if (empty($where)) return false; 
		return  $this->db->delete($this->_table, $where);
	}


	/**
	 * [fieldIncrease 某个字段自增]
	 * @param  [type]  $where [description]
	 * @param  [type]  $field [description]
	 * @param  integer $step  [description]
	 * @return [type]         [description]
	 */
	public function fieldIncrease($where,$field,$step=1)
	{
		if (empty($where)) return false; 
		$sql = "update ".$this->_table."  set ".$field."=".$field."+1 where ".$where;
		return $this->db_pp->query($sql);
	}

	########################
	#
	# 常用增删改查 基础类方法 END
	#
	########################


}