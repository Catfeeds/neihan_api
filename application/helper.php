<?php

function curl_post($url, $data='', $headers=[], $timeout=60, $agent='', $cookie='')
{
    $fn = curl_init();
    curl_setopt($fn, CURLOPT_URL, $url);
    curl_setopt($fn, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($fn, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($fn, CURLOPT_REFERER, $url);
    curl_setopt($fn, CURLOPT_HEADER, 0);
    curl_setopt($fn, CURLOPT_POST, TRUE);
    curl_setopt($fn, CURLOPT_POSTFIELDS, $data);
    if($headers) {
        curl_setopt($fn, CURLOPT_HEADER, 1);
        curl_setopt($fn, CURLOPT_HTTPHEADER, $headers ); 
    }
    if ($agent) {
        curl_setopt($fn, CURLOPT_USERAGENT, $agent);    
    }
    if ($cookie) {
       curl_setopt($fn,CURLOPT_COOKIE,$cookie);
    }
    $fm = curl_exec($fn);
    curl_close($fn);
    return $fm;
}

function curl_get($url, $timeout=60, $agent='', $cookie='')
{
    $fn = curl_init();
    curl_setopt($fn, CURLOPT_URL, $url);
    curl_setopt($fn, CURLOPT_TIMEOUT, 60);
    curl_setopt($fn, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($fn, CURLOPT_REFERER, $url);
    curl_setopt($fn, CURLOPT_HEADER, 0);
    if ($agent) {
        curl_setopt($fn, CURLOPT_USERAGENT, $agent);    
    }
    if ($cookie) {
       curl_setopt($fn,CURLOPT_COOKIE,$cookie);
    }
    $fm = curl_exec($fn);
    curl_close($fn);
    return $fm;
}


/**
  * XML编码
  * @param mixed $data 数据
  * @param string $root 根节点名
  * @param string $item 数字索引的子节点名
  * @param string $attr 根节点属性
  * @param string $id   数字索引子节点key转换的属性名
  * @param string $encoding 数据编码
  * @return string
  */
function xml_encode($data, $root='think', $item='item', $attr='', $id='id', $encoding='utf-8')
{
    if(is_array($attr)){
        $_attr = array();
        foreach ($attr as $key => $value) {
            $_attr[] = "{$key}=\"{$value}\"";
        }
        $attr = implode(' ', $_attr);
    }
    $attr   = trim($attr);
    $attr   = empty($attr) ? '' : " {$attr}";
    $xml    = "<?xml version=\"1.0\" encoding=\"{$encoding}\"?>";
    $xml   .= "<{$root}{$attr}>";
    $xml   .= data_to_xml($data, $item, $id);
    $xml   .= "</{$root}>";
    return $xml;
}
 
/**
  * 数据XML编码
  * @param mixed  $data 数据
  * @param string $item 数字索引时的节点名称
  * @param string $id   数字索引key转换为的属性名
  * @return string
  */
function data_to_xml($data, $item='item', $id='id')
{
    $xml = $attr = '';
    foreach ($data as $key => $val) {
        if(is_numeric($key)){
            $id && $attr = " {$id}=\"{$key}\"";
            $key  = $item;
        }
        $xml    .=  "<{$key}{$attr}>";
        // xml 转义特殊字符 如& 以<![CDATA[标记开始，以]]>标记结束,必须是最终文本值才能加上这个<![CDATA[xxx]]>
        $xml    .=  (is_array($val) || is_object($val)) ? data_to_xml($val, $item, $id) : '<![CDATA['.$val.']]>';
        $xml    .=  "</{$key}>";
    }
    return $xml;
}

function xml_to_data($xml)
{
    $obj  = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
    $json = json_encode($obj);
    $data  = json_decode($json, true);
    return $data;
}

function timestamp_url($url)
{
    return str_replace('timestamp', strval(time()).'.'.strval(rand(10, 60)), $url);
}

function generate_str( $length = 8 )
{
    // 密码字符集，可任意添加你需要的字符
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

    $password = '';
    for ( $i = 0; $i < $length; $i++ ) {
        // 这里提供两种字符获取方式
        // 第一种是使用 substr 截取$chars中的任意一位字符；
        // 第二种是取字符数组 $chars 的任意元素
        // $password .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        $password .= $chars[ mt_rand(0, strlen($chars) - 1) ];
    }

    return $password;
}

function generate_sign($data, $sign_key)
{
    if(empty($data)){
        return None;
    }
    ksort($data);

    $signature = urldecode(http_build_query($data));
    $signature = strtoupper(md5($signature.'&key='.$sign_key));

    return $signature;
}