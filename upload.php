
<?php

$defalutDir = "/home/ubuntu/files"; //网页根目录
$linkHeadName = "header.html";      //网页头部装饰
$linkReadmeName = "upload.html";    //网页尾部装饰

$supperUserPassword = "12344321n";  //超级密码 - 最高权限
$VisitPassword = "1234";            //访问密码 - 仅可访问下载
$UserPasswords = array(             //用户密码 
    "wwj", 
    "cpf", 
    "lcx", 
    "lxq",
    "yyds"
);


if ($_SERVER['REQUEST_METHOD'] === 'POST') 
{
    //保存文件
    if (isset($_POST['content']) && isset($_POST['filepath'])) {
        $content = $_POST['content'];
        $filepath = $defalutDir . "/" . $_POST['filepath'];
    
        // 将内容写入文件
        if (file_put_contents($filepath, $content) !== false) {
            $response = array('status' => 'failed', 'message' => '保存成功!');
            echo json_encode($response);
        } else {
            $response = array('status' => 'failed', 'message' => '保存文件时出现错误');
            echo json_encode($response);
        }


    }

    //密码校验
    if(isset($_POST['password']) && $_POST['passwordinfo'])
    {
        $password = $_POST['password'];
        $passwordinfo = $_POST['passwordinfo'];
        
        // 无效密码
        if ($password !== $VisitPassword && $password !== $supperUserPassword && !in_array($password, $UserPasswords)) {
            $response = array('status' => 'failed', 'message' => '权限验证失败');
            echo json_encode($response);
            exit(0);
        }

        //上传文件验证(超级用户 + 普通用户)
        if($passwordinfo === "upload" && isset($_POST['filepath'])) 
        {
            if($password === $supperUserPassword || in_array($password, $UserPasswords))
            {
                $filepath = parse_url($_POST['filepath'], PHP_URL_PATH); //解析为相对ip地址的路径
                
                $message = "权限验证通过\n";
                $status = "success";

                // 确保路径末尾有斜杠
                if (substr($filepath, -1) !== '/') {
                    $filepath .= '/';
                }
                // 检查目录是否存在
                $targetDir = $defalutDir . $filepath;
                if (!is_dir($targetDir))
                {
                    // TODO递归创建和链接
                    $message .= "创建新目录:" . $filepath . "\n";
                    mkdir($targetDir, 0755, true);

                    $sourceFile = $defalutDir . '/' . $linkHeadName;
                    $targetFile = $targetDir . '/' . $linkHeadName;
                    if (symlink($sourceFile, $targetFile)){
                        // $message .= "创建header软链接成功\n";
                    } 
                    else{
                        $message .= "无法创建header软链接\n";
                        $status = "failed";
                    }
                    
                    $sourceFile = $defalutDir . '/' . $linkReadmeName;
                    $targetFile = $targetDir . '/' . $linkReadmeName;
                    if (symlink($sourceFile, $targetFile)){
                        // $message .= "创建Readme软链接成功\n";
                    } 
                    else{
                        $message .= "无法创建Readme软链接\n";
                        $status = "failed";
                    }
                }
                $response = array('status' => $status, 'message' => $message, 'targetDir' => $targetDir);
                echo json_encode($response);
            }
            else
            {
                $response = array('status' => 'failed', 'message' => '您没有上传权限');
                echo json_encode($response);
            }
        }

        //删除文件(夹)验证(超级用户 + 普通用户)
        else if($passwordinfo === "delete" && isset($_POST['rmfile']))
        {
            if($password === $supperUserPassword || in_array($password, $UserPasswords)) 
            {
                $name = $defalutDir . $_POST['rmfile'];
                $message = "";
                $status = "success";
    
                //文件夹
                if (is_dir($name)) {
                    if(deleteDirectory($name)){
                        $message .= "删除文件夹成功";
                    }
                    else{
                        $message .= "删除文件夹失败";
                        $status = "failed";
                    }
                }
                //文件
                else{
                    if (file_exists($name)) {
                        if (unlink($name)) {
                            $message .= "文件删除成功";
                        } else {
                            $message .= "无法删除文件";
                            $status = "failed";
                        }
                    } else {
                        $message .= "文件不存在";
                        $status = "failed";
                    }
                }

                $response = array('status' => $status, 'message' => $message);
                echo json_encode($response);
            }
            else
            {
                $response = array('status' => 'false', 'message' => '您没有删除权限');
                echo json_encode($response);
            }
        }

        //访问用户文件夹(超级用户 + 普通用户)
        else if($passwordinfo === "personal file")
        {
            if($password === $supperUserPassword || in_array($password, $UserPasswords)) 
            {
                $response = array('status' => 'success', 'message' => '权限验证通过');
                echo json_encode($response);
            }
            else{
                $response = array('status' => 'false', 'message' => '您没有访问权限');
                echo json_encode($response);
            }
        }
        
        // 访问用户(超级用户 + 普通用户 + 访客)
        else if($passwordinfo === "signin")
        {
            $response = array('status' => 'success', 'message' => '权限验证通过');
            echo json_encode($response);
        }
    }

    //文件上传
    if(isset($_FILES['file']) && isset($_POST['dir']))
    {
        //dir + filename
        $targetFile = $_POST['dir'] . basename($_FILES['file']['name']);
        // 上传
        if (move_uploaded_file($_FILES['file']['tmp_name'], $targetFile)) 
        {
            echo "<h2>上传成功!</h2><h4>(5s后将自动跳转至首页)</h4>";

            echo date('Y-m-d  H:i:s') . "<br>";
            echo "上传文件名: " . $_FILES["file"]["name"] . "<br>";
            echo "文件类型: " . $_FILES["file"]["type"] . "<br>";
            echo "文件大小: " . ($_FILES["file"]["size"] / 1024) . " kB<br>";
            echo "上传位置: " . $path;

            header("refresh:5;url=$path");
        } 
        else 
        {
            if($_FILES["file"]["error"] == "1")
            {
                echo "上传的文件超过了设定的大小限制" . "<br>";

                //这些参数在/etc/php/version/apache2/php.ini中修改
                $uploadMaxFilesize = ini_get('upload_max_filesize');
                $postMaxSize = ini_get('post_max_size');
                echo "最大上传文件大小设置为: $uploadMaxFilesize<br>";
                if($postMaxSize != "0")
                    echo "POST 请求最大大小设置为: $postMaxSize<br>";
            }
            else
            {
                echo "上传文件时发生错误 code:" . $_FILES["file"]["error"] . "<br>";
            }

            header("refresh:5;url=" . $_SERVER['HTTP_REFERER']);
        }
    }

    // elseif(isset($_POST['userInput'])){ // 检查是否接收到名为 "userInput" 的表单字段
    //     HandleTest();
    // } 
    // elseif(isset($_POST['select_directory'])){
    //     HandleContent();
    // }
    // else{
    //     echo "<p>get file</p>";
    // }
}

//递归创建文件夹和链接操作
function createDirectory($sourceDir, $targetDir, $defalutDir, $linkHeadName, $linkReadmeName, &$message, &$status) {
    // 创建目标目录
    if (!is_dir($targetDir)) 
    {
        if (!mkdir($targetDir, 0755, true)) 
        {
            $message .= "无法创建新目录:" . $targetDir . "\n";
            $status = "failed";
            return;
        } else 
        {
            $message .= "创建新目录:" . $targetDir . "\n";
        }
    }

    // 扫描源目录
    $files = scandir($sourceDir);
    foreach ($files as $file) {
        if ($file == '.' || $file == '..') {
            continue;
        }
        $sourceFile = $sourceDir . '/' . $file;
        $targetFile = $targetDir . '/' . $file;

        if (is_dir($sourceFile)) {
            // 递归处理子目录
            createDirectory($sourceFile, $targetFile, $defalutDir, $linkHeadName, $linkReadmeName, $message, $status);
        } else {
            // 创建软链接
            if (symlink($sourceFile, $targetFile)) {
                $message .= "创建软链接成功: $sourceFile -> $targetFile\n";
            } else {
                $message .= "无法创建软链接: $sourceFile -> $targetFile\n";
                $status = "failed";
            }
        }
    }
}

//删除文件夹函数
function deleteDirectory($dir_path) {
    if (!is_dir($dir_path)) {
        return false;
    }
    $files = array_diff(scandir($dir_path), array('.', '..'));
    foreach ($files as $file) {
        $path = $dir_path . '/' . $file;

        if (is_dir($path)) {
            deleteDirectory($path);
        } else {
            unlink($path);
        }
    }

    return rmdir($dir_path);
}


// function HandleContent()
// {
//     // 指定要读取的服务器目录路径，检查目录是否存在并可读
//     $serverPath = '/home/ubuntu/web';
//     if (!is_dir($serverPath)) {
//         die("目录不存在或无法访问。");
//     }

//     // 创建选择框
//     echo "<select name='directories'>";
//     echo "<option value=''>选择一个目录</option>";
//     // 读取目录中的文件夹和子目录
//     getdir($serverPath);
//     // 结束选择框
//     echo "</select>";

//     function getdir($serverPath)
//     {
//         // 打开目录
//         $handle = opendir($serverPath);
//         // 读取目录中的文件夹和循环读取子目录
//         while (false !== ($entry = readdir($handle))) {
//             if (!preg_match('/^\./', $entry)) { //过滤以.开头的文件夹
//                 $fullPath = $serverPath . '/' . $entry;
//                 if (is_dir($fullPath)) {
//                     // 输出当前子目录作为选项
//                     echo "<option value='$entry'>$entry</option>";
                    
//                     // 递归调用自身，显示子目录的子目录
//                     // getdir($fullPath);
//                 }
//             }
//         }
//         // 关闭目录句柄
//         closedir($handle);
//     }
// }

// function HandleTest()
// {
//     // 获取用户输入
//     $userInput = $_POST['userInput'];
//     // 显示用户输入
//     echo "<p>您输入的文本是： $userInput </p>";
//     echo "hello world";
// }

?>