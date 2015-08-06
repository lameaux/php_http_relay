var system = require('system');
var args = system.args;
if (args.length === 1) {
  console.log('url parameter is missing');
}

var url = args[1];

var page = require('webpage').create();
page.settings.userAgent = 'Mozilla/5.0 (iPad; CPU OS 6_0 like Mac OS X) AppleWebKit/536.26 (KHTML, like Gecko) Version/6.0 Mobile/10A406 Safari/8536.25';
page.settings.resourceTimeout = 5000; // 5 seconds
page.settings.loadImages=false;


var headers = '';

page.onResourceTimeout = function(e) {
  console.log('timeout');   
  phantom.exit();
};

page.onResourceReceived = function(response) {
    headers = JSON.stringify(response);
};

page.open(url, function(status) {
  if(status === "success") {
    console.log(headers + "\r\n" + page.content);
  }
  phantom.exit();
});
