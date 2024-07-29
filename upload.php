
<?php
// 检查密码是否正确
$publicUserPassword = "1234";
$supperUserPassword = "0000";
$defalutDir = "/home/ubuntu/files";
$linkHeadName = "header.html";
$linkReadmeName = "upload.html";

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') 
{
    //密码校验
    if(isset($_POST['password']))
    {
        $password = $_POST['password'];
        
        // 检查密码是否正确
        if ($password !== $publicUserPassword && $password !== $supperUserPassword) {
            $response = array('status' => 'failed', 'message' => '权限验证失败');
            echo json_encode($response);
        }
        // 密码正确后检查路径是否正确
        else if(isset($_POST['filepath'])) {
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
                //超级用户可创建目录和软链接
                if($password === $supperUserPassword) {
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
                else {
                    $message .= "目录不存在或无法访问:" . $filepath . "\n";
                    $status = "failed";
                }
            }
            $response = array('status' => $status, 'message' => $message, 'targetDir' => $targetDir);
            echo json_encode($response);
        }
        //执行删除文件操作
        else if(isset($_POST['rmfile']))
        {
            if($password === $supperUserPassword) {
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
            else{
                $response = array('status' => 'success', 'message' => '您没有删除权限');
                echo json_encode($response);
            }
        }
        // 仅需验证密码且正确
        else{
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