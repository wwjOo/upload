
<?php
// 检查密码是否正确
$publicUserPassword = "1234";
$supperUserPassword = "0000";
$defalutDir = "/home/ubuntu/web";

if ($_SERVER['REQUEST_METHOD'] === 'POST') 
{
    if(isset($_POST['password']) && isset($_POST['userInput']) && isset($_FILES['file'])){
        HandleUpload($publicUserPassword,$supperUserPassword,$defalutDir);
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

function HandleUpload($publicUserPassword,$supperUserPassword,$defalutDir)
{
    // 获取用户输入的密码
    $password = $_POST['password'];
    // 检查密码是否正确
    if ($password !== $publicUserPassword && $password !== $supperUserPassword) 
    {
        //3s后返回上一级
        header("refresh:3;url=" . $_SERVER['HTTP_REFERER']);
        die("密码不正确，请返回重试！");
    }

    // 获取上传路径
    $path = parse_url($_POST['userInput'], PHP_URL_PATH);
    // 确保路径末尾有斜杠
    if (substr($path, -1) !== '/') {
        $path .= '/';
    }
    $targetDir = $defalutDir . $path;
    if (!is_dir($targetDir)) 
    {
        //超级用户可创建目录
        if($password === $supperUserPassword)
        {
            mkdir($targetDir, 0755, true);
            echo "创建新目录: " . $targetDir;
        }
        else
        {
            echo $targetDir . "<br>";
            die("目录不存在或无法访问!!!");
        }
    }

    // 上传
    $targetFile = $targetDir . basename($_FILES['file']['name']);
    if (move_uploaded_file($_FILES['file']['tmp_name'], $targetFile)) 
    {
        echo "<h2>上传成功!</h2>";

        echo date('Y-m-d  H:i:s') . "<br>";
        echo "上传文件名: " . $_FILES["file"]["name"] . "<br>";
        echo "文件类型: " . $_FILES["file"]["type"] . "<br>";
        echo "文件大小: " . ($_FILES["file"]["size"] / 1024) . " kB<br>";
        echo "上传位置: " . $targetDir;

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

?>