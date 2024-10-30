(function() {
    
function urldecode(url) {
  return decodeURIComponent(url.replace(/\+/g, ' '));
}

function add_css_to_head(css){
    var head = document.getElementsByTagName('head')[0];
    var s = document.createElement('style');
    s.setAttribute('type', 'text/css');
    if (s.styleSheet) {   // IE
        s.styleSheet.cssText = css;
    } else {                // the world
        s.appendChild(document.createTextNode(css));
    }
    head.appendChild(s);
 }
    

    

function unreadCountChanged(newUnreadCount) {
  // Set the app badge, for app icons and links. This has a global and
  // semi-permanent effect, outliving the current document.
  if (navigator.setAppBadge) {
    navigator.setAppBadge(newUnreadCount);
    
    console.log('app badge is supported'); 
    
  } else {
      
     console.log('app badge not supported'); 
      
  }

  // Set the document badge, for the current tab / window icon.
  if (navigator.setClientBadge) {
    navigator.setClientBadge(newUnreadCount);
    
    console.log('client badge is supported'); 
    
  } else {
    // Fall back to setting favicon (or page title).
    // (This is a user-supplied function, not part of the Badge API.)
    //showBadgeOnFavicon(newUnreadCount);
    
    console.log('client badge is not supported'); 
  }
}    

function get_self_url(){
    
if (document.querySelector("link[rel='canonical']") && document.querySelector("link[rel='canonical']").getAttribute("href") ){ 

return document.querySelector("link[rel='canonical']").getAttribute("href") ;

} else {
    
return window.location.href;    
    
}
    
    
    
}

function destroy_element(){
    
    console.log('destroy triggered');
    
    if (document.getElementById("lh_fresh_content-notify")){
        
        console.log('node exists');
    
document.getElementById("lh_fresh_content-notify").remove();    

} 

//else {
    
//this.parentNode.remove();
    
//}
    
}

function reload_document(){
    
    location.reload(true);     
    
}

function add_dynamic_functionality(){
    
var anchors = document.querySelectorAll('#lh_fresh_content-notify a.lh_fresh_content-refresh');    
    
for (i = 0; i < anchors.length; ++i) {
    
    console.log('anchor found');
    
  anchors[i].href = get_self_url();

}    


var dismissers = document.querySelectorAll('#lh_fresh_content-notify .lh_fresh_content-dismiss_button');    


    
for (i = 0; i < dismissers.length; ++i) {
    
    console.log('dismisser found');
    
dismissers[i].addEventListener('click', destroy_element, false);


}  



    
}

function maybe_show_update_div(){
    
            if (!document.getElementById("lh_fresh_content-notify")){
            
        var holder_div = document.createElement('div');
        holder_div.setAttribute("id", "lh_fresh_content-notify");
        holder_div.setAttribute("class", document.getElementById("lh_fresh_content-script").getAttribute("data-refresh_div_classes"));
        
            
        var message = urldecode(document.getElementById("lh_fresh_content-script").getAttribute("data-refresh_message"));
        
        console.log('the message is' + message);
        
        holder_div.innerHTML = message;
        
        document.querySelector(document.getElementById("lh_fresh_content-script").getAttribute("data-refresh_selector")).insertAdjacentElement('afterbegin', holder_div);
        
        var dismiss_button = document.createElement('button');
        dismiss_button.setAttribute("class", "lh_fresh_content-dismiss_button");
        dismiss_button.setAttribute("title", "Dismiss this alert");
        
        var dismiss_span = document.createElement('span');
        dismiss_span.setAttribute("class", "lh_fresh_content-dismiss_span");
        dismiss_span.textContent = 'Dismiss';
        dismiss_button.appendChild(dismiss_span);
        
        document.getElementById("lh_fresh_content-notify").appendChild(dismiss_button);
        
        add_dynamic_functionality();
        
  
        //Tinycon.setBubble('!');
        
        unreadCountChanged(1);
        
        }
    
    
    
}

function do_update(){
    
        if (document.hidden) {
        
        console.log('reload page');
      
      location.reload(true);  
      
      unreadCountChanged(1);
        
    } else {
        
    maybe_show_update_div();
        
    }
    
    
    
    
    
}
    
function maybe_handle_updated_content(text){

var parser = new DOMParser();
var parseddoc = parser.parseFromString(text, "text/html");

  	
  if (parseddoc.querySelector('meta[http-equiv="last-modified"]')){
      
var current_date = Date.parse(document.querySelector('meta[http-equiv="last-modified"]').getAttribute("content"));
      
var new_date = Date.parse(parseddoc.querySelector('meta[http-equiv="last-modified"]').getAttribute("content"));

if (current_date < new_date) {
    
    
    console.log('the current date is less than te new one, we have fresh content');
    
        do_update();
    
} else if (current_date == new_date) {

    console.log('the dates match');
    
        if (document.getElementById("lh_fresh_content-script").getAttribute("data-current_user_id") == parseddoc.querySelector('#lh_fresh_content-script').getAttribute("data-current_user_id")){
        
        console.log('the auth states match, nothinbg to do here');
    } else {
        
        console.log('the auth states do not match, maybe show fresh content');
        
        do_update();
        
    }
    

    

} else {
    
    console.log('up to date content');
    
}
      
  } else {
      
      console.log('tag missing');
      
 }
    
    
}
    
    
function fetch_self(){
    
if (window.fetch) {
    
console.log('running fetch self');

fetch(get_self_url(), {
  credentials: 'same-origin' ,
  cache: 'reload'
}).then(function(response) {
  if(response.ok) {
     
return response.text();
  }



        // continuation
      }).then(function(text) { 
          
          
maybe_handle_updated_content(text);


  	
  	
  }).catch(function(error) {
    // If there is any error you will catch them here
    
    console.log(error);
    

  });   
  
} else {
    
console.log('fetch is not supported');    
    
}
    
    
}
    

function boot(){
    
var styles = urldecode(document.getElementById("lh_fresh_content-script").getAttribute("data-refresh_div_styles"));
add_css_to_head(styles);
        
document.addEventListener("visibilitychange", function() {
  if (document.visibilityState === 'visible') {
    fetch_self();
  } 
});


var refreshers = document.querySelectorAll('.lh_fresh_content-refresh');    

for (i = 0; i < refreshers.length; ++i) {
    
    console.log('refresher found');
    
refreshers[i].addEventListener('click', reload_document, false);


}  


       
setInterval(fetch_self, 120000);

//maybe_show_update_div();
        
    }

    
boot();    
    
    
    
    
})();