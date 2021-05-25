window.onload=start;
regex={
    username:"1-16 alphanumeric",
    password:"8-32 characters",
    title:"1-32 alphanumeric",
    content:"1-1000 characters"
};
function start(){
    msg=document.getElementById("msg").children[0];
    inputs=Array.prototype.slice.call(document.getElementsByTagName("input"));
    textareas=Array.prototype.slice.call(document.getElementsByTagName("textarea"));
    inputs=inputs.concat(textareas);
    inputs.forEach(function(input){
        if(typeof regex[input.getAttribute("name")] == "undefined")
            return;
        input.onfocus=function(){msg.innerHTML=regex[input.getAttribute("name")]};
        input.onblur=function(){msg.innerHTML=null};
    });
}

