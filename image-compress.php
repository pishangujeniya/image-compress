<?php
/*

Request : POST or GET

| KEYNAME | TYPE | VALUE |
| ------ | ------ | ------ |
| delete_dirs | string | false |
| secret_key | string | coffee |
| image_link | string | https://www.google.com/logos/doodles/2019/holi-2019-5084538337230848.2-l.png |
| quality | int | 60 |
| debug_mode | string | false |
| want_binary | string | true |


 * ```delete_dirs``` : false = Won't delete dls & compressed directories on server.
 * ```secret_key``` : secret key required to execute.
 * ```image_link``` : image link to compress.
 * ```quality``` : 60 = quality in integer between 1 to 100 (more the number , better the quality and less compression).
 * ```debug_mode``` : false = echo errors & warnings || true = won't echo errors & warnings.
 * ```want_binary``` : false = response json with base64 encoded compressed image || true = response image type with binary image data.

 */
ini_set('memory_limit', '1023M');
$response_array = array();
$response_array["success"] = false;
$response_array["error"] = "";

$DOWNLOAD_PATH = "dls/";
$COMPRESSED_PATH = "compressed/";
$DOMAIN = "http://" . $_SERVER['SERVER_NAME'] . ":" . $_SERVER['SERVER_PORT'];
$validated = false;
$validate_secret_key = "coffee";
$received_secret_key = "";
$image_link = "";
$quality = 60;
$want_binary = false;
$is_post = false;

if (!empty($_POST['debug_mode'])) {
    if ($_POST['debug_mode'] == "true") {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);
        $response_array['debug_mode'] = true;
    } else {
        $response_array['debug_mode'] = false;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $is_post = true;
    // The request is using the POST method
    if (!empty($_POST['want_binary'])) {
        if ($_POST['want_binary'] == "true") {
            $want_binary = true;
        } else {
            $want_binary = false;
        }
    }else{
        $want_binary = false;
    }
    $response_array['want_binary'] = $want_binary;
    if (!empty($_POST['delete_dirs'])) {
        if ($_POST['delete_dirs'] == "true") {
            deleteDir($DOWNLOAD_PATH);
            deleteDir($COMPRESSED_PATH);
            $response_array['delete_dirs'] = true;
            $response_array['delete_dirs_success'] = true;
        }
    }
    if (!empty($_POST['image_link']) && !empty($_POST['secret_key'])) {
        $received_secret_key = trim($_POST['secret_key']);
        if ($received_secret_key == $validate_secret_key) {
            $image_link = trim($_POST['image_link']);
            if (!empty($_POST['quality'])) {
                $quality = (int)$_POST['quality'];
            }
            $validated = true;
        } else {
            $response_array['error'] = $response_array["error"] . "Wrong Secret Key|";
            $validated = false;
        }
    } else {
        $validated = false;
    }

}else if($_SERVER['REQUEST_METHOD']=== 'GET'){
    $is_post = false;
    // The request is using the GET method
    if( $_GET["want_binary"]) {
        if($_GET['want_binary'] == "true"){
            $want_binary = true;
        }else{
            $want_binary = false;
        }
    }else{
        $want_binary = false;
    }
    $response_array['want_binary'] = $want_binary;
    if ($_GET['delete_dirs']) {
        if ($_GET['delete_dirs'] == "true") {
            deleteDir($DOWNLOAD_PATH);
            deleteDir($COMPRESSED_PATH);
            $response_array['delete_dirs'] = true;
            $response_array['delete_dirs_success'] = true;
        }
    }
    if ($_GET['image_link'] || $_GET['secret_key']) {
        $received_secret_key = trim($_GET['secret_key']);
        if ($received_secret_key == $validate_secret_key) {
            $image_link = urldecode(trim($_GET['image_link']));
            if ($_GET['quality']) {
                $quality = (int)$_GET['quality'];
            }
            $validated = true;
        } else {
            $response_array['error'] = $response_array["error"] . "Wrong Secret Key|";
            $validated = false;
        }
    } else {
        $validated = false;
    }
}


if ($validated == false) {
    $response_array["success"] = false;
    $response_array["error"] = $response_array["error"] . "Missing Input Parameters|";
    send_response($response_array);
    return;
}
if ($validated) {
    makeDir($DOWNLOAD_PATH);
    makeDir($COMPRESSED_PATH);
    $downloaded_image_file_name = rand(100, 10000) . ".jpg";
    $response_array['image_link'] = $image_link;
    $response_array['quality'] = $quality;
    $curl = curl_init($image_link);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    $curl_response = curl_exec($curl);
    $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $response_array['image_download_http_code'] = $httpcode;
    if ($httpcode === 200) {
        $fp = fopen($DOWNLOAD_PATH . $downloaded_image_file_name, "w");
        fwrite($fp, $curl_response);
        fclose($fp);
        $response_array['downloaded'] = true;
    }
    curl_close($curl);
    $compressed_file_path = compress_image($DOWNLOAD_PATH . $downloaded_image_file_name, $COMPRESSED_PATH . $downloaded_image_file_name, $quality);
    //Deleting Downloaded File
    unlink($DOWNLOAD_PATH . $downloaded_image_file_name);
    // if binary file requested then echo image using readfile() else send base_64 encoded in json response.
    if ($want_binary) {
        try{
            header("content-type: ".mime_content_type($compressed_file_path));
            header("Content-Disposition: attachment; filename=".$downloaded_image_file_name);
            readfile($compressed_file_path);
            $response_array['ct'] = mime_content_type($compressed_file_path);
            $response_array['cd'] = $downloaded_image_file_name;
        }catch(Exception $e){
            header("content-type: ".custom_mime_content_type($compressed_file_path));
            header("Content-Disposition: attachment; filename=".$downloaded_image_file_name);
            readfile($compressed_file_path);
            $response_array['ct'] = custom_mime_content_type($compressed_file_path);
            $response_array['cd'] = $downloaded_image_file_name;
        }

    } else {
        $base_64 = convert_to_base_64($compressed_file_path);
        $response_array['base_64'] = $base_64;
        $response_array['compressed_image_link'] = $DOMAIN . "/" . $compressed_file_path;
        $response_array["success"] = true;
        send_response($response_array);
    }
    //Deleting compressed_file
    unlink($compressed_file_path);
}

// Functions
function send_response($json_to_send) {
    header('Content-Type: application/json');
    echo json_encode($json_to_send, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
}
function makeDir($path) {
    return is_dir($path) || mkdir($path);
}
function deleteDir($path) {
    if (!empty($path) && is_dir($path)) {
        $dir = new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS); //upper dirs are not included,otherwise DISASTER HAPPENS :)
        $files = new RecursiveIteratorIterator($dir, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $f) {
            if (is_file($f)) {
                unlink($f);
            } else {
                $empty_dirs[] = $f;
            }
        }
        if (!empty($empty_dirs)) {
            foreach ($empty_dirs as $eachDir) {
                rmdir($eachDir);
            }
        }
        rmdir($path);
    }
}
function compress_image($source_url, $destination_url, $quality) {
    $info = getimagesize($source_url);
    if ($info['mime'] == 'image/jpeg') $image = imagecreatefromjpeg($source_url);
    elseif ($info['mime'] == 'image/gif') $image = imagecreatefromgif($source_url);
    elseif ($info['mime'] == 'image/png') $image = imagecreatefrompng($source_url);
    imagejpeg($image, $destination_url, $quality);
    return $destination_url;
}
function convert_to_base_64($path) {
    $type = pathinfo($path, PATHINFO_EXTENSION);
    $data = file_get_contents($path);
    $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
    return $base64;
}
function custom_mime_content_type($filename) {
    $result = new finfo();

    if (is_resource($result) === true) {
        return $result->file($filename, FILEINFO_MIME_TYPE);
    }

    return false;
}
?>