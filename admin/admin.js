
function getData(params, callback, options)
{
	options = options || {};
	options.type = options.type || "get";
	var startTime = new Date();

	function updateTimer() {
		var stopTime = new Date();
		var timeTaken = (stopTime-startTime)/1000
		if (!$("#querySpeed").data("timeStore"))
			$("#querySpeed").data("timeStore",[]);
		var timeStore = $("#querySpeed").data("timeStore");
		timeStore.push(timeTaken);
		$("#querySpeed").text(Math.round(timeTaken*1000)/1000).attr("title",timeStore.length + " queries total. Avg run time: " + Math.round(average(timeStore)*1000)/1000);
	}

	selfRepeatCall = function() { getData(params, callback, options); }

	if (arguments.length > 3 && arguments.length < 2)
		return alert("Invalid call for getData, you need 2 params, you gave me " + arguments.length + "... Crashing!");

	if (!params.action)
		return alert("Can't get data without an action!");

	$.ajax({"cache":"false", "type":options.type, "data":params, "dataType":"json", "url":"admin.php"
	, "success": function(response)
	{
		updateTimer();
		if (!response)
		{
			alert('Network Error');
		}
		else if (response.error && response.error == "true")
		{
			alert('Unknown error occured.. Please try again. <hr>' + response.msg);
		}
		else
		{
			callback(response);
		}
	}
	, "error":function(XMLHttpRequest, textStatus, errorThrown)
	{
		updateTimer();
		if (textStatus == "parsererror")
			alert("Not a valid JSON object. " + XMLHttpRequest.responseText);
		else
			alert('Network Error');
	}

	});
}

function authenticate(username, password, callback)
{
	getData({"action":"login","username":username,"password":password}, function(response){
		if (response.loginError == "false") {
			callback();
		}
		else {

		}
	}, {"type":"post"});
}

function logout()
{
	getData({"action":"logout"}, function(response){
		$("#admin-tabs").hide();
		$("#auth-status").hide();
		loadStatus();
	}, {"type":"post"});
}

function loadStatus()
{
	getData({"action":"status"}, function(response) {
		if (response.authenticated == "true") {
			$("#admin-tabs").tabs({
				ajaxOptions: {
					error: function(xhr, status, index, anchor) {
						$(anchor.hash).html("Error loading tab!");
					}
				},
				cookie: {
					expires: 30
				}
			});
			$("#admin-tabs").show();
			$("#auth-status").show();
		}
		else {
			$("#admin-login-dialog").dialog({
				show:"puff",
				hide:"puff",
				modal: true,
				resizable:false,
				buttons: {
					Ok: function(){
						var modalWindow = $("#admin-login-dialog");
						authenticate(modalWindow.find(".username").val(), modalWindow.find(".password").val(), function(){
							modalWindow.dialog('close');
							modalWindow.find(".password").val("");
						});
						loadStatus();
					}
				}
			});
		}
	}, {"type":"post"});
}

function average(items)
{
	var sum = 0;
	for (i = 0; i < items.length; i++)
		sum += items[i];
	return (sum / items.length);
}

$(function() {

	loadStatus();

});
