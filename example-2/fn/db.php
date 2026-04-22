<?php
function db_con()
{
  global $connection;
  $connection = mysqli_connect("localhost:9306", "manticore", "", "") or die("imposible connect to server");
}

function db_escape( $text )
{
  global $connection;
  $res=mysqli_real_escape_string( $connection, $text );
  return $res;
}


function db_exec( $sql )
{
  global $connection;
 // echo "$sql\n";
  $res=mysqli_query( $connection, $sql );
  return $res;
}

function db_select( $sql )
{
  $result=[];
  global $connection;
  $res=mysqli_query( $connection, $sql );
  if (mysqli_num_rows($res))
  {
    $result[]=mysqli_fetch_assoc($res);
  }
  return $result;
}
