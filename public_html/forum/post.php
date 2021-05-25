<?php
if(!isset($_POST["action"]))
return;
$action=$_POST["action"];
$actionLogged=["newPost","newDisc","newFolder","logout"];
if(!$id && in_array($action,$actionLogged))
    refresh("You must be logged in to perform this action.");
$actionPermission=["lockTopic","unlockTopic","deleteTopic","newCat"];
if(!$perm && in_array($action,$actionPermission))
    refresh("You don't have permission to perform this action.");

// APPEND AND VERIFY POST VARIABLES
$actionVars=[ // "action_name"->["input_name_and_regex_link"=>"optional_regex_link(0_means_none)"],
    "login"=>["username","password"],
    "register"=>["username","password","passwordr"=>0],
    "newPost"=>["topic"=>"uint","content"=>0],
    "newDisc"=>["topic"=>"uint","title","content"=>0],
    "newFolder"=>["topic"=>"uint","title"],
    "newCat"=>["title"],
    "lockTopic"=>["topic"=>"uint"],
];
$post=[];
$errors=[];
if(isset($actionVars[$action])) // if there is $actionVars section linked to the action
    foreach($actionVars[$action] as $key=>$value){
        if(is_int($key)) // if array record is simple (just value specified)
            $key=$value;
        if(!isset($_POST[$key])) // if required post key hasn't been sent
            refresh("?c_form_broken?");
        $post[$key]=$_POST[$key];
        if($value) // if value verification is required
            if(!regex($post[$key],$value)) // if verification is negative
                $errors[]=$key; // append key to the $errors array
        $_POST[$key]=addslashes($_POST[$key]);
    }
if(sizeof($errors))
    refresh("The fields below have been filled incorrectly:<br>".implode(", ",$errors));
// append and verify post variables /

if(isset($_POST["topic"])){
    if(!sizeof($locked=get("select locked from forum_topics where id=$_POST[topic]")))
        refresh("This topic doesn't exist.$_POST[topic]");
    if($locked[0]["locked"] && !$perm)
        refresh("This topic is locked.");
}
$redirect=0;
// ACTIONS RETURN MESSAGES
$msg=[
    "login"=>["This user doesn't exist.","Wrong password.","Logged in."],
    "loginAsAdmin"=>["This user doesn't exist.","Logged in."],
    "loginAsUser"=>["This user doesn't exist.","Logged in."],
    "register"=>["Database error.","New account has been registered.","Passwords do not match.","This username is taken."],
    "logout"=>["Logged off."],
    "newPost"=>["Database error.","Post has been sent."],
    "newFolder"=>["Database error.","New folder has been created."],
    "newDisc"=>["Database error.","New discussion has been created.","Database error."],
    "newCat"=>["Database error.","New category has been created."],
    "lockTopic"=>["Database error.","Topic has been locked."],
    "unlockTopic"=>["Database error.","Topic has been unlocked."],
    "deleteTopic"=>["Database error.","Topic has been deleted."]
];
// action return messages /

function login(){
    $loginData=get("select id,password from forum_users where username='$_POST[username]'");
    if(!sizeof($loginData))
        return 0; // no such user
    if(!password_verify($_POST['password'], $loginData[0]['password']))
        return 1; // wrong password
    $_SESSION["forum_id"]=$loginData[0]["id"];
    return 2; // logged in
}

function loginAsAdmin(){
    $loginData=get("select id from forum_users where username='admin'");
    if(!sizeof($loginData))
        return 0; // no such user
    $_SESSION["forum_id"]=$loginData[0]["id"];
    return 1; // logged in
}

function loginAsUser(){
    $loginData=get("select id from forum_users where username='user'");
    if(!sizeof($loginData))
        return 0; // no such user
    $_SESSION["forum_id"]=$loginData[0]["id"];
    return 1; // logged in
}

function register(){
    if($_POST["password"]!=$_POST["passwordr"])
        return 2; // passwords do not match
    if(sizeof(get("select id from forum_users where username='$_POST[username]'")))
        return 3; // username taken
    $passwordHashed = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $state=set("insert into forum_users(username,password) values('$_POST[username]','$passwordHashed')");
    if(!$state)
        return 0;
    $_SESSION["forum_id"]=insertId();
    return 1;
}

function logout(){
    unset($_SESSION["forum_id"]);
    return 0;
}

function newPost(){
    global $id;
    $_POST['content'] = str_replace("\n", "<br>", $_POST['content']);
    return set("insert into forum_posts(user_id,content,topic)values($id,'$_POST[content]',$_POST[topic])");
}
function newDisc(){
    global $id;
    $topic=set("insert into forum_topics(name,parent,folder)values('$_POST[title]',$_POST[topic],0)");
    if(!$topic)
        return 0;
    $tid=insertId();
    $_POST['content'] = str_replace("\n", "<br>", $_POST['content']);
    $post=set("insert into forum_posts(user_id,content,topic)values($id,'$_POST[content]',$tid)");
    if($post)
        return 1;
    set("delete from forum_topics where id=$tid");
    return 2;
}
function newFolder(){
    return set("insert into forum_topics(name,parent,folder)values('$_POST[title]',$_POST[topic],1)");
}
function newCat(){
    return set("insert into forum_topics(name,parent,folder)values('$_POST[title]',0,1)");
}
function lockTopic(){
    return set("update forum_topics set locked=1 where id=$_POST[topic]");
}
function unlockTopic(){
    return set("update forum_topics set locked=0 where id=$_POST[topic]");
}
function deleteTopic(){
    return set("delete from forum_topics where id in(".implode(",",topics_rooted($_POST["topic"])).")");
}


if(function_exists($action))
    $result=$action();
else
    refresh("This action doesn't exist.");
    
if(@$_SERVER["HTTP_X_REQUESTED_WITH"]!="XMLHttpRequest"){
    if($redirect)
        refresh($msg[$action][$result],$redirect);
    else
        refresh($msg[$action][$result]);
}
else
    echo $msg[$action][$result];
die();
?>