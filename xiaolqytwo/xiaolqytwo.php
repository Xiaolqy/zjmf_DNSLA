<?php
function xiaolqytwo_idcsmartauthorizes()
{
}
function xiaolqytwo_MetaData()
{
    return ["DisplayName" => "DNSLA域名对接模块-XiaoLqy", "APIVersion" => "1.0", "HelpDoc" => "https://idc1.xyz/"];
}
function xiaolqytwo_TestLink($params)
{
    return "因为官方没有给API所以只能自行查看域名是否存在";
}
function xiaolqytwo_ConfigOptions()
{
    return [["type" => "text", "name" => "域名", "description" => "例如：example.com", "key" => "domain"], ["type" => "text", "name" => "域名ID", "description" => "00000000000000000", "key" => "domainid"]];
}
function xiaolqytwo_CreateAccount($params)
{
    $token = base64_encode($params["server_username"] . ":" . $params["server_password"]);
    $domainId = $params["configoptions"]["domainid"];
    $domain = $params["configoptions"]["domain"];
    $type = 1;
    $domaindata = "127.0.0.1";
    $host = $params["customfields"]["主机头"];
    $recordData = ["domainId" => $domainId, "type" => $type, "host" => $host, "data" => $domaindata, "ttl" => 600];
    $jsonData = json_encode($recordData);
    $url = "https://api.dns.la/api/record";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Basic " . $token, "Content-Type: application/json; charset=utf-8"]);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    $result = json_decode($response, true);
    if ($result["code"] == 200) {
        $update["username"] = $result["data"]["id"];
        $update["domain"] = $host . "." . $domain;
        $update["assignedips"] = "A";
        $update["dedicatedip"] = $domaindata;
        think\Db::name("host")->where("id", $params["hostid"])->update($update);
        return "ok";
    }
    return ["status" => "error", "msg" => $result["msg"] . $response];
}
function xiaolqytwo_TerminateAccount($params)
{
    $token = base64_encode($params["server_username"] . ":" . $params["server_password"]);
    $url = "https://api.dns.la/api/record?id=" . $params["username"];
    $options = [CURLOPT_URL => $url, CURLOPT_HTTPHEADER => ["Authorization: Basic " . $token, "Content-Type: application/json"], CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1, CURLOPT_CUSTOMREQUEST => "DELETE"];
    $ch = curl_init();
    curl_setopt_array($ch, $options);
    $response = curl_exec($ch);
    curl_close($ch);
    $result = json_decode($response, true);
    if ($result["code"] == 200) {
        return "ok";
    }
    return ["status" => "error", "msg" => $result["msg"] . $response];
}
function xiaolqytwo_AllowFunction()
{
    return ["client" => ["xiugai"], "admin" => []];
}
function xiaolqytwo_xiugai($params)
{
    $post = input("post.");
    $token = base64_encode($params["server_username"] . ":" . $params["server_password"]);
    if ($post["domain_type"] == "A") {
        $type = 1;
    } else {
        if ($post["domain_type"] == "CNAME") {
            $type = 5;
        }
    }
    $host = $params["customfields"]["主机头"];
    $domainId = $params["username"];
    $record = ["id" => $domainId, "type" => $type, "host" => $host, "data" => $post["domain_value"]];
    $jsonData = json_encode($record);
    $url = "https://api.dns.la/api/record";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Basic " . $token, "Content-Type: application/json; charset=utf-8"]);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $result = json_decode($response, true);
    curl_close($ch);
    if ($result["code"] == 200) {
        $update["dedicatedip"] = $post["domain_value"];
        $update["assignedips"] = $post["domain_type"];
        think\Db::name("host")->where("id", $params["hostid"])->update($update);
        return ["status" => "success", "result" => "success", "msg" => "修改成功"];
    }
    return ["status" => "error", "msg" => $result["msg"] . $response];
}
function xiaolqytwo_ClientArea($params)
{
    return ["msgrmation" => ["name" => "域名管理"]];
}
function xiaolqytwo_ClientAreaOutput($params, $key)
{
    if ($key == "msgrmation") {
        return ["template" => "information.html", "vars" => ["domain_type" => $params["assignedips"] . "记录", "domain_value" => $params["dedicatedip"], "domain" => $params["domain"]]];
    }
}

?>
