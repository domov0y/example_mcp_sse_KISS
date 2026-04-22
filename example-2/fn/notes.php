<?php
//$tools=[];


$tools['write_fact']=[ 'name' => 'write_fact', 'description' => 'Add fact']; 
$tools['write_fact'] ['inputSchema'] =['type' => 'object','properties' => ['fact' => ['type' => 'string', 'description' => 'Text'],'tags' => ['type' => 'array', 'items' => ['type' => 'string']],'source' => ['type' => 'string']]];

function write_fact($params)
{
  $now=time();
  $fact=db_escape($params['fact']);
  $tags='';
  $source='';
  if (isset($params['tags']) )
  {
    $tmp_tags=$params['tags'];
    if (is_array($tmp_tags))
    {
      foreach ($tmp_tags as $key => $val) { $tmp_tags[$key] = db_escape($val); }
      $tags=implode(",", $tmp_tags);
    }
    else
    $tags=db_escape($tmp_tags);
  }

  if (isset($params['source']))
  {
    $source=db_escape($params['source']);
  }

   $sql="insert into facts(fact_text,fact_tags,source,created_at) values('$fact', '$tags', '$source', $now)";
   db_exec($sql);
  return ['status' => 'saved']; 
}
//--------------------------------------------------------------


$tools['search_facts']=['name' => 'search_facts', 'description' => 'Search fact']; 
$tools['search_facts']['inputSchema'] = [ 'type' => 'object', 'properties' => ['query' => ['type' => 'string', 'description' => 'Text'], 'limit' => ['type' => 'integer', 'default' => 10],'offset' => ['type' => 'integer', 'default' => 0]]];


function search_facts($params)
{
  $query=db_escape($params['query']);

  $limit=10;
  if (isset($params['limit']))$limit=(int)$params['limit'];
  if ($limit >100)$limit=100;

  $offset=0;
  if (isset($params['offset']))$offset=(int)$params['offset'];

  $sql= " select * from facts where  match('$query') limit $limit offset $offset ";
  $result=db_select($sql);
  return $result;
}