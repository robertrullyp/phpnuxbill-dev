// radio checked - hotspot plan
$(document).ready(function () {
	$('input[type=radio]').change(function () {

		if ($('#Time_Limit').is(':checked')) {
			$('#DataLimit').hide();
			$('#TimeLimit').show();
		}
		if ($('#Data_Limit').is(':checked')) {
			$('#TimeLimit').hide();
			$('#DataLimit').show();
		}
		if ($('#Both_Limit').is(':checked')) {
			$('#TimeLimit').show();
			$('#DataLimit').show();
		}

		if ($('#Unlimited').is(':checked')) {
			$('#Type').hide();
			$('#TimeLimit').hide();
			$('#DataLimit').hide();
		} else {
			$('#Type').show();
		}

		if ($('#Hotspot').is(':checked')) {
			$('#p').hide();
			$('#h').show();
		}
		if ($('#PPPOE').is(':checked')) {
			$('#p').show();
			$('#h').hide();
		}

	});
});
$("#Hotspot").prop("checked", true).change();


function checkIP(f, id) {
	if (f.value.length > 6) {
		$.get(appUrl + '/?_route=autoload/pppoe_ip_used&ip=' + f.value + '&id=' + id, function (data) {
			$("#warning_ip").html(data)
		});
	}
}

function checkUsername(f, id) {
	if (f.value.length > 1) {
		$.get(appUrl + '/?_route=autoload/pppoe_username_used&u=' + f.value + '&id=' + id, function (data) {
			$("#warning_username").html(data)
		});
	}
}

//auto load pool - pppoe plan
var htmlobjek;
function loadPppoeServiceOptions(routers, selectedValue) {
	if (!$('#pppoe_service').length) {
		return;
	}
	if (!routers) {
		$('#pppoe_service').html('<option value=\"\">Select PPPoE Service</option>');
		return;
	}
	var query = "routers=" + encodeURIComponent(routers);
	if (selectedValue) {
		query += "&selected=" + encodeURIComponent(selectedValue);
	}
	$.ajax({
		url: appUrl + "/?_route=autoload/pppoe_service",
		data: query,
		cache: false,
		success: function (msg) {
			$("#pppoe_service").html(msg);
		}
	});
}

$(document).ready(function () {
	$("#routers").change(function () {
		var routers = $("#routers").val();
		$.ajax({
			url: appUrl + "/?_route=autoload/pool",
			data: "routers=" + routers,
			cache: false,
			success: function (msg) {
				$("#pool_name").html(msg);
			}
		});
		loadPppoeServiceOptions(routers, '');
	});

	if ($('#pppoe_service').length) {
		var initialRouter = $('#routers').val();
		var selectedService = $('#pppoe_service').data('selected') || $('#pppoe_service').val() || '';
		loadPppoeServiceOptions(initialRouter, selectedService);
	}
});

//auto load plans data - recharge user
$(function () {
	$('input[type=radio]').change(function () {
		if ($('#Hot').is(':checked')) {
			$.ajax({
				type: "POST",
				dataType: "html",
				url: appUrl + "/?_route=autoload/server",
				success: function (msg) {
					$("#server").html(msg);
				}
			});

			$("#server").change(getAjaxAlamat);
			function getAjaxAlamat() {
				var server = $("#server").val();
				$.ajax({
					type: "POST",
					dataType: "html",
					url: appUrl + "/?_route=autoload/plan",
					data: "jenis=Hotspot&server=" + server,
					success: function (msg) {
						$("#plan").html(msg);
					}
				});
			};

		} else if ($('#POE').is(':checked')) {
			$.ajax({
				type: "POST",
				dataType: "html",
				url: appUrl + "/?_route=autoload/server",
				success: function (msg) {
					$("#server").html(msg);
				}
			});
			$("#server").change(function () {
				var server = $("#server").val();
				$.ajax({
					type: "POST",
					dataType: "html",
					url: appUrl + "/?_route=autoload/plan",
					data: "jenis=PPPOE&server=" + server,
					success: function (msg) {
						$("#plan").html(msg);
					}
				});
			});
		} else {
			$.ajax({
				type: "POST",
				dataType: "html",
				url: appUrl + "/?_route=autoload/server",
				success: function (msg) {
					$("#server").html(msg);
				}
			});
			$("#server").change(function () {
				var server = $("#server").val();
				$.ajax({
					type: "POST",
					dataType: "html",
					url: appUrl + "/?_route=autoload/plan",
					data: "jenis=VPN&server=" + server,
					success: function (msg) {
						$("#plan").html(msg);
					}
				});
			});
		}
	});
});

// auto load plan data - refund user (active plan only)
$(function () {
	if (!$('#refund-form').length) {
		return;
	}

	function loadRefundServers() {
		$.ajax({
			type: "POST",
			dataType: "html",
			url: appUrl + "/?_route=autoload/server",
			success: function (msg) {
				$("#refund_server").html(msg);
			}
		});
	}

	function loadRefundPlans() {
		var server = $("#refund_server").val();
		var customerId = $("#refund_customer").val() || $("#personSelect").val();
		var type = $('input[name=refund_type]:checked').val() || '';
		if (!server || !customerId || !type) {
			$("#refund_plan").html('<option value="">Select Plans</option>');
			return;
		}

		$.ajax({
			type: "POST",
			dataType: "html",
			url: appUrl + "/?_route=autoload/plan",
			data: "jenis=" + encodeURIComponent(type) +
				"&server=" + encodeURIComponent(server) +
				"&customer_id=" + encodeURIComponent(customerId) +
				"&active_only=1",
			success: function (msg) {
				$("#refund_plan").html(msg);
			}
		});
	}

	$('input[name=refund_type]').change(function () {
		loadRefundServers();
		$("#refund_plan").html('<option value="">Select Plans</option>');
	});
	$("#refund_server").change(loadRefundPlans);
	$("#refund_customer, #personSelect").change(loadRefundPlans);

	// default to PPPoE in refund form if available, otherwise first checked radio.
	if ($('input[name=refund_type][value="PPPOE"]').length) {
		$('input[name=refund_type][value="PPPOE"]').prop('checked', true).trigger('change');
	} else {
		$('input[name=refund_type]').first().prop('checked', true).trigger('change');
	}
});


function showPrivacy() {
	$('#HTMLModal_title').html('Privacy Policy');
	$('#HTMLModal_konten').html('<center><img src="ui/ui/images/loading.gif"></center>');
	$('#HTMLModal').modal({
		'show': true,
		'backdrop': false,
	});
	$.get('pages/Privacy_Policy.html?' + (new Date()), function (data) {
		$('#HTMLModal_konten').html(data);
	});
}

function showTaC() {
	$('#HTMLModal_title').html('Terms and Conditions');
	$('#HTMLModal_konten').html('<center><img src="ui/ui/images/loading.gif"></center>');
	$('#HTMLModal').modal({
		'show': true,
		'backdrop': false,
	});
	$.get('pages/Terms_and_Conditions.html?' + (new Date()), function (data) {
		$('#HTMLModal_konten').html(data);
		$('#HTMLModal').modal('handleUpdate')
	});
}
