<?php

//接続
$dsn = "mysql:host=localhost; dbname=todo_db; charset=utf8";
$user = "testuser";
$pass = "testpass";

//データ受け取り
$origin =[];

if(isset($_POST)){
    $origin += $_POST;
}

foreach($origin as $key => $value){
    $mb_code = mb_detect_encoding($value);
    $value = mb_convert_encoding($value, "UTF-8", $mb_code);

    $value = htmlentities($value, ENT_QUOTES, "UTF-8");

    $input[$key] = $value;
}

try{
    $dbh = new PDO($dsn, $user, $pass);
    if(isset($input["mode"]) && $input["mode"] === "register"){
        register();
    }elseif(isset($input["mode"]) && $input["mode"] === "delete"){
        delete();
    }
    display();
}catch (PDOException $e){
    echo " 接続失敗m9(^Д^)ﾌﾟｷﾞｬｰ<br>";
    echo " エラー内容：" . $e->getMessage();
}

//////////////////////////////////////////////////////

//関数

function display(){
    global $dbh;
    global $input;

    $sql = <<<sql
    select * from todo where flag = 1;
sql;
    $stmt = $dbh -> prepare($sql);
    $stmt -> execute();

    $block = "";

    //テンプレート
    $tmp = <<<tmp
    <div class = "task_box">
            <p class = "task_ttl"><span class = "sp">!title!</span></p>
            <div class = "task_txt_box">
                <p><span class = "task_txt bold">締め切り</span>：<span class = "txt_deco">!deadline!</span></p>
                <p><span class = "task_txt bold">登録日</span>：<span class = "txt_deco">!indate!</span></p>
                <p><span class = "task_txt bold">コメント</span><br><span class = "txt_deco cmt">!comment!</span></p>
            </div>
            
            <form action = "todo.php" method = "post">
            <input type = "submit" class = "del_btn" onclick="removeExample(this)" value = "完了">
            <input type = "hidden" name = "mode" value = "delete">
            <input type = "hidden" name = "id" value = "!id!">
            </form>
        </div>
tmp;

    $i = 0;

    //これで何行でも出力できる。$blockにぶち込み続ける
    while($row = $stmt -> fetch()){
        //テンプレの初期化
        $insert = $tmp;

        //DBの値を変数の値へ
        $id = $row["id"];
        $title = $row["title"];
        $deadline = $row["deadline"];
        $indate = $row["indate"];
        $comment = $row["comment"];

        //空白対策
        if($indate === "0000-00-00"){
            $indate = date("Y-m-d");
        }if($comment === ""){
            $comment = "特になし";
        }

        $insert = str_replace("!id!",$id ,$insert);
        $insert = str_replace("!title!",$title ,$insert);
        $insert = str_replace("!deadline!",$deadline ,$insert);
        $insert = str_replace("!indate!",$indate ,$insert);
        $insert = str_replace("!comment!",$comment ,$insert);
        
        $block .= $insert;

        //上位表示
        $i++;
        if($i > 2){
            $block = "<p class = 'warning'>3件以上タスクがあります</p>" . $block;
            break;
        }
    }

    $ih = fopen("todo.html" , "r+");
    $is = filesize("todo.html");
    $top = fread($ih,$is);

    $top = str_replace("<p>まだ何も登録されていません</p>", $block, $top);

    echo $top;
}

function register(){
    global $dbh;
    global $input;
    $sql = <<<sql
    insert into todo (title,deadline,indate,comment) values(?,?,?,?);
sql;
    $stmt = $dbh -> prepare($sql);//実行を一時停止中
    $stmt -> bindParam(1,$input["title"]);//紐づけ
    $stmt -> bindParam(2,$input["deadline"]);
    $stmt -> bindParam(3,$input["indate"]);
    $stmt -> bindParam(4,$input["comment"]);
    $stmt -> execute();
}

function delete(){
    global $dbh;
    global $input;

    $sql = <<<sql
    update todo set flag = 0 where id = ?;
sql;
    $stmt = $dbh -> prepare($sql);
    $stmt -> bindParam(1,$input["id"]);
    $stmt -> execute();
}