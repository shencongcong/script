<?php
/**
 * Created by PhpStorm.
 * User: danielshen
 * Date: 2019/1/17
 * Time: 21:45
 */

$status = file_get_contents('config.txt');
$res = [
  'code'=>0,
  'res' => $status,
];

echo json_encode($res);