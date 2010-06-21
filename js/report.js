
function getNextSibling(elem)
{
	do {
		elem = elem.nextSibling;
	} while (elem && elem.nodeType != 1);

	return elem;
}

function initQunitReport() {
	$('strong').each(function(i){
		var next = getNextSibling(this);
		if (next)
			next.style.display = "none";
	});
	$('strong').click(function(){
		var next = getNextSibling(this);
		if (next) {
			display = next.style.display;
			next.style.display = display === "none" ? "block" : "none";
		}
	});
}
