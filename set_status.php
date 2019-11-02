<?php
/**
 * Created by PhpStorm.
 * User: danielshen
 * Date: 2019/1/17
 * Time: 21:39
 */

$status = $_REQUEST['status'];

file_put_contents('config.txt',$status);

