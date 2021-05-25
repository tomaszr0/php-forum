<?php
// DB FUNCTIONS
function set($q){
    global $db;
    return $db->query($q);
}
function get($q){
    global $db;
    $q=$db->query($q);
    $result=[];
    foreach($q as $r){
        foreach($r as $index=>$r2)
            $r[$index]=str_replace("?","&#63;",$r2);
        $result[]=$r;
    }
    return $result;
}
function insertId(){
    global $db;
    return $db->insert_id;
}
function createDb(){
    global $db;
    $db->query("create table forum_users(
                id int auto_increment,
                username text not null,
                password text not null,
                date_registered timestamp not null default current_timestamp,
                date_online timestamp not null default current_timestamp on update current_timestamp,
                permission boolean default 0,
                primary key(id));");
    $db->query("create table forum_topics(
                id int auto_increment,
                name text,
                parent int,
                folder boolean,
                locked boolean default 0,
                primary key(id));");
    $db->query("create table forum_posts(
                id int auto_increment,
                content text charset utf8,
                user_id int,
                topic int,
                date_posted timestamp default current_timestamp,
                primary key(id));");
}// DB FUNCTIONS /////////////////////

// UNIVERSAL FUNCTIONS
function refresh($msg=null,$redirect=null){
    global $url;
    $_SESSION["forum_msg"]=$msg;
    if($redirect)
        $redirect=$url.$redirect;
    header("location:$redirect");
    die();
}
function date_ago($date){
    $date=strtotime($date);
    $date_now=time();
    $d=$date_now-$date;
    $r=$d." second";
    if($d>60)
        $r=floor($d/60)." minute";
    if($d>3600)
        $r=floor($d/3600)." hour";
    if($d>86400)
        $r=floor($d/86400)." day";
    if($d>2592000)
        $r=floor($d/2628000)." month";
    if($d>31536000)
        $r=floor($d/31536000)." year";
    if(substr($r,0,strpos($r," "))>1)
        $r.="s";
    return $r." ago";
}
function insert_data($str,$index=[]){
    global $struct;
    global $data;
    preg_match_all("/\?[a-zA-Z0-9_\[\]]*\?/",$str,$matches);
    if($matches[0])
    foreach($matches[0] as $match){
        if(substr($match,-8,-1)=="[index]"){
            $indexB=1;
            $matchStr=str_replace("[index]","",$match);
        }
        else{
            $indexB=0;
            $matchStr=$match;
        }
        $array=substr($match,1,1);
        $matchStr=substr($matchStr,3,-1);
        $record=@$data[$array][$matchStr];
        if(isset($record)){ // IF DATA EXISTS
                while(isset($record["switch"])){ // SOLVE CONDITIONS
                    $case=insert_data($record["switch"]);
                    if(isset($record[$case]))
                        $record=$record[$case];
                    else
                        $record=$record["default"];
                } // solve conditions /
            if(gettype($record)!="array")
                $record=[$record];
            if(gettype($record[0])=="array")
                $record=$record[1];
            $tmp="";
            foreach($record as $ind=>$rec){
                    $rec=insert_data($rec,$index);
                    $tmp.=str_replace($match,$rec,$str);
            }
            $str=$tmp;
        } // if data exists /
        else // IF DATA CANNOT BE FOUND
            $str=str_replace($match,"404[".substr($match,1,-1)."]",$str);
    }
    return $str;
}
// UNIVERSAL FUNCTIONS ////////////////////////

// UNIQUE FUNCTIONS
function topic_path($topic,$type=""){
    $tid=$topic;
    $path="";
    $lvl=-1;
    do{
        $t=get("select id,name,parent from forum_topics where id=$tid");
        if(!sizeof($t))
            return 0;
        $t=$t[0];
        if($t["parent"]==0)
            $href="?c_url?";
        else
            $href="?c_url?/t/$t[id]";
        if($tid==$topic && !$type)
            $class=" class='b'";
        else
            $class="";
        $path="<a href='$href'$class>$t[name]</a> >> $path";
        $tid=$t["parent"];
        $lvl++;
    }while($tid);
    $path=substr($path,0,-4);
    if($type=="newTopic")
        $path.=" >> <span class='b'>New topic</span>";
    return ["path"=>$path,"lvl"=>$lvl];
}
function topic_posts($topic,$original=1){
    if(get("select folder from forum_topics where id=$topic")[0]["folder"]){
        $children=get("select id from forum_topics where parent=$topic");
        $countp=0;
        $countu=[];
        foreach($children as $child){
            $count=topic_posts($child["id"],0);
            $countp+=$count["posts"];
            $countu+=array_merge($countu,$count["users"]);
        }
    }
    else{
        $countp=get("select count(id) as p from forum_posts where topic=$topic")[0]["p"];
        $count=get("select distinct user_id as u from forum_posts where topic=$topic");
        $countu=[];
        foreach($count as $c)
            $countu[]=$c["u"];
    }
    $countu=array_unique($countu);
    if(!$original)
        return ["posts"=>$countp,"users"=>$countu];
    if(!$countu=sizeof($countu))
        return "no posts";
    return "$countp posts by $countu users";
    
}
function topic_last($topic,$original=1){
    if($folder=get("select folder from forum_topics where id=$topic")[0]["folder"]){ // FOLDER
        $children=get("select id from forum_topics where parent=$topic");
        if(!sizeof($children)){
            if(!$original)
                return 0;
            else
                return "no posts";
        }
        $last=["pid"=>-1];
        foreach($children as $child){
            $tmp=topic_last($child["id"],0);
            if($tmp)
            if($tmp["pid"]>$last["pid"])
                    $last=$tmp;
        }
    }
    else{ // DISCUSSION
        $last=get("select forum_posts.id as pid,topic,username,forum_users.id as userid,forum_topics.name as tname,date_posted as date from forum_posts,forum_users,forum_topics where forum_topics.id=forum_posts.topic and forum_users.id=user_id and topic=$topic order by date_posted desc limit 1");
        if(!sizeof($last)){
            if(!$original)
                return 0;
            else
                return "no posts";
        }
        $last=$last[0];
        $last["dateRel"]=date_ago($last["date"]);
    }
    if(!$original) // if rooted return array with data
        return $last;
    if($last["pid"]==-1) // if no posts found return string
        return "no posts";
    if($folder)
        return "in <a href='?c_url?/t/$last[topic]'>$last[tname]</a><br>by <a href='?c_url?/u/$last[userid]'>$last[username]</a> <span title='$last[date]'>$last[dateRel]</span>";
    else
        return "by <a href='?c_url?/u/$last[userid]'>$last[username]</a> <span title='$last[date]'>$last[dateRel]</span>";
}
function topics_rooted($t){
    $folders=[$t];
    $r=get("select id,folder from forum_topics where parent=$t");
    foreach($r as $a)
        if($a["folder"])
            $folders=array_merge($folders,topics_rooted($a["id"]));
        else
            $folders[]=$a["id"];
    return array_unique($folders);
}
// UNIQUE FUNCTIONS ////////////////////////

// REGEX
function regex($string,$format){
    $formats=[
        "uint"=>"/^[0-9]{1,}$/",
        "bool"=>"/^(0|1)$/",
        
        "username"=>"/^[a-zA-Z0-9]{1,16}$/",
        "password"=>"/^.{8,32}$/",
        
        "title"=>"/^[a-zA-Z0-9 ]{1,32}$/",
        "content"=>"/^.{1,1000}$/"
        ];
    return preg_match($formats[$format],$string);
}
// REGEX /////////////////////////////////
?>