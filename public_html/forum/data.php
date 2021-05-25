<?php
$msg=@$_SESSION["forum_msg"]."";
unset($_SESSION["forum_msg"]);

if(isset($_GET["page"]))
    $page=$_GET["page"];
else
    $page="main";

if(isset($_GET["topic"]))
    $topic=$_GET["topic"];
else
    $topic=0;

$html_title="Forum";

$url="$_SERVER[REQUEST_SCHEME]://$_SERVER[HTTP_HOST]".substr($_SERVER['PHP_SELF'],0,strrpos($_SERVER['PHP_SELF'],'/'));
if($id){
    $username=get("select username,permission from forum_users where id=$id")[0];
    $user_privileges=$username["permission"];
    $username=$username["username"];
}
else{
    $username="";
    $user_privileges=0;
}

$datetime5=date('Y-m-d H:i:s', time()-5*60);

$data=[];
$data["c"]=[
    "msg"=>$msg,
    "user_id"=>$id,
    "user_name"=>$username,
    "user_privileges"=>$user_privileges,
    
    "datetime"=>date('Y-m-d H:i:s', time()),
    
    "posts_total"=>get("select count(id) as c from forum_posts;")[0]["c"],
    "topics_total"=>get("select count(id) as c from forum_topics where parent!=0;")[0]["c"],
    "users_total"=>get("select count(id) as c from forum_users;")[0]["c"],
    "users_online"=>get("select count(id) as c from forum_users where date_online>'$datetime5';")[0]["c"],
    
    "html_title"=>$html_title,
    "url"=>$url,
    
    "mainpage"=>"Main",
    "users"=>"Users",
    
    "form_broken"=>"Form is broken.",
    "form_username"=>"username",
    "form_password"=>"password",
    "form_passwordr"=>"repeat password",
    "form_login"=>"login",
    "form_logout"=>"logout",
    "form_register"=>"register",
    "form_category"=>"category",
    "form_create"=>"create",
    "cancel"=>"cancel",
    
    "form_login_as_admin"=>"admin",
    "form_login_as_user"=>"normal user",
];

function topic_tools($tid,$locked){
    global $perm;
    global $url;
    if(!$perm)
        return "";
    if($locked)
        return "<form method='post' class='inline topicTools'><input type='hidden' name='topic' value='$tid'><input type='submit' name='action' value='unlockTopic' style='background:url($url/unlock.png);width:16px;height:21px;background-size:16px 21px;font-size:0;'><input type='submit' name='action' value='deleteTopic' style='background:url($url/delete.png);width:16px;height:21px;background-size:16px 21px;font-size:0;'></form>";
    else 
        return "<form method='post' class='inline topicTools'><input type='hidden' name='topic' value='$tid'><input type='submit' name='action' value='lockTopic' style='background:url($url/lock.png);width:16px;height:21px;background-size:16px 21px;font-size:0;'><input type='submit' name='action' value='deleteTopic' style='background:url($url/delete.png);width:16px;height:21px;background-size:16px 21px;font-size:0;'></form>";
}

if($perm)
$s_cat=
    "%s<div class='category %s'>
        <div class='header'>
            <span><span>%s</span></span>
            <form method='post' class='inline topicTools'><input type='hidden' name='topic' value='%s'><input type='submit' name='action' value='%slockTopic' style='background:url(%slock.png);width:16px;height:21px;background-size:16px 21px;font-size:0;'><input type='submit' name='action' value='deleteTopic' style='background:url(delete.png);width:16px;height:21px;background-size:16px 21px;font-size:0;'></form>
            <span class='var'>last post</span>
            <span class='var'>total posts</span>
        </div>
        %s
    </div>";
else
$s_cat=
    "%s<div class='category %s'>
        <div class='header'>
            <span><span>%s</span></span>
            <span class='var'>last post</span>
            <span class='var'>total posts</span>
        </div>
        %s
    </div>";

$s_top=
        "<div>
            <div class='icon-%s'></div>
            <a href='?c_url?/t/%s'>%s</a>%s
            <span class='var'>%s</span>
            <span class='var'>%s</span>
        </div>";

$content="";
switch($page){
    case "main": // MAIN
        $html_title="?d_mainpage? - $html_title";
        $categories=get("select * from forum_topics where parent=0;");
        $cats="";
        foreach($categories as $category){
            $tops="";
            if($category["locked"])
                $locked="un";
            else
                $locked="";
            if(!$category["locked"] && $id || $perm)
                $newTop="<div style='text-align:right;margin-bottom:8px;'><a href='?c_url?/t/$category[id]/new' class='button'>New topic</a></div>";
            else
                $newTop="";
            $topics=get("select * from forum_topics where parent=$category[id];");
            foreach($topics as $topic){
                $type=["discussion","folder"][$topic["folder"]];
                $count=topic_posts($topic["id"]);
                $last=topic_last($topic["id"]);
                $tops.=sprintf($s_top,
                               $type.[0=>"",1=>" locked"][$topic["locked"]],
                               $topic["id"],$topic["name"],
                               topic_tools($topic["id"],$topic["locked"]),
                               $last,$count);
            }
            if($perm)
            $cats.=sprintf($s_cat,$newTop,"big",$category["name"],$category["id"],$locked,$locked,$tops);
            else
                $cats.=sprintf($s_cat,$newTop,"big",$category["name"],$tops);
        }
        if($perm) $cats="?s_new_category?".$cats;
        $content=$cats;
        break; // MAIN /
        
    case "topic": // TOPIC
        $content="";
        $topic_name=topic_path($topic);
        if(!$topic_name)
            return $content="<div class='category'>
                                <div class='header'><span>This topic doesn't exist</span></div>
                                <div><span>Click <a href='?c_url?'>here</a> to return to main page.</span></div>
                             </div>";
        $topic_name=$topic_name["path"];
        $html_title="$topic_name - $html_title";
        $locked=get("select locked from forum_topics where id=$topic;")[0]["locked"];
        // folder
        if(get("select * from forum_topics where id=$topic;")[0]["folder"]){
            $html_title="?d_mainpage? - $html_title";
            $topics=get("select forum_topics.id,forum_topics.name,forum_topics.folder,forum_topics.locked from forum_topics where parent=$topic;");
            if($id && !$locked || $perm)
            $content.=
                "<div style='text-align:right;margin-bottom:8px;'><a href='?c_url?/t/$topic/new' class='button'>New topic</a></div>";
            $content.="
            <div class='category big'><div class='header'><span><span>$topic_name</span></span><span class='var'>last post</span><span class='var'>total posts</span></div>";
            foreach($topics as $topic){
                $count=topic_posts($topic["id"]);
                $last=topic_last($topic["id"]);
                $type=["discussion","folder"][$topic["folder"]];
                if($tlocked=$topic["locked"])
                    $lockedStr=" locked";
                else
                    $lockedStr="";
                $content.="<div><div class='icon-$type$lockedStr'></div><a href='$url/t/$topic[id]'>$topic[name]</a>".topic_tools($topic["id"],$topic["locked"])."<span class='var'>$last</span><span class='var'>$count</span></div>";
            }
            if(!sizeof($topics)){
                $content.="<div><span>There are no topics yet.";
                if($id && !$locked || $perm)
                    $content.=" Click <a href='?c_url?/t/$topic/new'>here</a> to create one.";
                $content.="</span></div>";
            }
            
            $content.="</div>";
        
        } // folder /
        else{ // discussion
            $posts=get("select forum_posts.id as postid,content,user_id,date_posted,forum_users.id as userid,username from forum_posts,forum_users where topic=$topic and forum_users.id=user_id;");
            $topicName=get("select name from forum_topics where id=$topic")[0]["name"];
            $content="
            <div class='category'><div class='header'><span><span>$topic_name</span></span></div>";
            foreach($posts as $post){
                $dateRel=date_ago($post["date_posted"]);
                $content.="<div id='$post[postid]'>
                <a href='$url/u/$post[userid]'>$post[username]</a>
                <span title='$post[date_posted]' class='date_posted'>$dateRel</span>
                <a class='postid' href='#$post[postid]'>#$post[postid]</a>
                <span class='content'>$post[content]</span>
                </div>";
            }$content.="</div>";
            if($id && !$locked || $perm)
                $content.=
                "<div class='center' style='margin-bottom:8px;'>
                    <div class='category newPost'>
                        <div class='header'><span>Add new post</span></div>
                        <form method='post'>
                            <input type='hidden' name='action' value='newPost'>
                            <input type='hidden' name='topic' value='$topic'>
                            <textarea name='content' placeholder='Write your post here...'></textarea>
                            <input type='submit' value='send'>
                        </form>
                    </div>
                </div>";
        } // discussion /
        if($locked)
                $content.=
                "<div class='center'>
                    <div class='category newPost'>
                        <div class='header'><span>This topic is closed.</span></div>
                    </div>
                </div>";
        break; // MAIN /
    
    case "newtopic": // NEW TOPIC
        $topic_name=topic_path($topic,"newTopic")["path"];
           $content="<div class='category big'><div class='header'><span><span>$topic_name</span></span></div>";
        $content.="<label class='button' for='hide1'>Discussion</label><label for='hide2' class='button'>Folder</label>
        <input type='radio' name='hide' id='hide1' class='hide'>
        <form method='post' class='hide block'>
            <input type='hidden' name='action' value='newDisc'>
            <input type='hidden' name='topic' value='$topic'>
            <input name='title' placeholder='Discussion title'>
            <textarea name='content' placeholder='Content'></textarea>
            <input type='submit'>
        </form>
        <input type='radio' name='hide' id='hide2' class='hide'>
        <form method='post' class='hide block'>
            <input type='hidden' name='action' value='newFolder'>
            <input type='hidden' name='topic' value='$topic'>
            <input name='title' placeholder='Folder name'>
            <input type='submit'>
        </form>";
        break; // new topic /
        
    case "user": // USER
        $content="<div class='category'><div class='header'><span>Users</span><span class='var'>registered</span><span class='var'>last online</span><span class='var'>posts</span></div>";
        $users = get("select forum_users.*,count(forum_posts.id) as posts from forum_users,forum_posts where forum_users.id=forum_posts.user_id group by forum_users.id union select forum_users.*,0 as forum_posts from forum_users where forum_users.id not in(select user_id from forum_posts)");
        foreach($users as $user){
            $content.="<div><a href='?c_url?/u/$user[id]'>$user[username]</a><span title='$user[date_registered]' class='var'>".date_ago($user["date_registered"])."</span><span title='$user[date_online]' class='var'>".date_ago($user["date_online"])."</span><span class='var'>$user[posts]</span></div>";
        }
        $content.="</div>";
        break; // user /
        
    case "login":
        $content=
        "<div class='category' style='min-width:400px;max-width:400px;'>
            <div class='header'>
                <span>Login</span>
            </div>
            <form method='post' class='block logreg'>
                <input type='hidden' name='action' value='login'>
                <input name='username' placeholder='?c_form_username?'>
                <input type='password' name='password' placeholder='?c_form_password?'>
                <input type='submit' value='?c_form_login?'>
            </form>
        </div>
        <div class='category' style='min-width:400px;max-width:400px;'>
            <div class='header'>
                <span>Login as an example account</span>
            </div>
            <form method='post' class='block logreg'>
                <input type='hidden' name='action' value='loginAsAdmin'>
                <input type='submit' value='?c_form_login_as_admin?'>
            </form>
            <form method='post' class='block logreg'>
                <input type='hidden' name='action' value='loginAsUser'>
                <input type='submit' value='?c_form_login_as_user?'>
            </form>
        </div>";
        break;
        
    case "register":
        $content=
        "<div class='category' style='min-width:400px;max-width:400px;'>
            <div class='header'>
                <span>Register</span>
            </div>
            <form method='post' class='block logreg'>
                <input type='hidden' name='action' value='register'>
                <input name='username' name='username' placeholder='?c_form_username?'>
                <input type='password' name='password' placeholder='?c_form_password?'>
                <input type='password' name='passwordr' placeholder='?c_form_passwordr?'>
                <input type='submit' value='?c_form_register?'>
            </form>
        </div>";
        break;        
        
    case "error404":
        http_response_code(404);
        $content="<div><span style='font-size:20px'>Error 404</span></div>
        <div><span>The requested page doesn't exist.</span></div>";
        break;
        
}



?>