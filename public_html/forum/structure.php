<?php

$data["s"]=[
    "menu"=>["<div id='menu'>
        <a href='?c_url?'>?c_mainpage?</a>
        <a href='?c_url?/u'>?c_users?</a>?s_panel?
    </div>"],
    
    "panel"=>[
        "switch"=>"?c_user_id?",
        "default"=>"<form method='post'>
                        <a href='?c_url?/u/?c_user_id?'>?c_user_name?</a>
                        <input type='hidden' name='action' value='logout'>
                        <input type='submit' value='?c_form_logout?'>
                    </form>",
        0=>         "<a href='?c_url?/login' style='margin-left:16px;'>?c_form_login?</a>
                    <a href='?c_url?/register'>?c_form_register?</a>"
    ],
    
    "new_category"=>[
        "switch"=>"?c_user_privileges?",
        "default"=>"",
        1=>"<form method='post' style='text-align:right;margin-bottom:16px;'><input type='hidden' name='action' value='newCat'><input name='title' placeholder='?c_form_category?'><input type='submit' value='?c_form_create?'></form>"
    ],
    
    "footer"=>["<div id='footer'>
        <span>?c_posts_total? posts in ?c_topics_total? topics</span>
        <span>?c_users_total? registered users</span>
        <span>?c_users_online? users online in the last 5 minutes</span>
    </div>"],
    
    "content"=>[
        "switch"=>"page",
        "default"=>"?s_category?"
    ],
    
    "category"=>"<div class='category %s'>
                    <div class='header'>
            <span>?c_categories?</span>
            <span class='var'>last post</span>
            <span class='var'>total posts</span>
        </div>
        ?s_topic?
    </div>",
    
    "topic"=>"<div>
            <div class='icon-%s'></div>
            <a href='?c_url?/t/%s'>?c_topics?</a>
            <span class='var'>%s</span>
            <span class='var'>%s posts by %s users</span>
        </div>"
];

$html=
"<!doctype html>
<html>
<head>
    <meta charset='utf8'>
    <link rel='stylesheet' href='?c_url?/pc.css'>
    <title>?c_html_title?</title>
</head>
<body>
    <div id='header'>
        <a href='?c_url?'>Forum Title</a>
    </div>
        ?s_menu?
    <div id='msg'><span>?c_msg?</span></div>
    <div id='content'>
        $content
    </div>
    ?s_footer?
    <script src='?c_url?/script.js'></script>
</body>
</html>";

$html=insert_data($html);
$html=str_replace("&#63;","?",$html);
echo $html;
?>