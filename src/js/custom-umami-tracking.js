document.addEventListener("DOMContentLoaded", function () {
	var buttons = document.querySelectorAll("button");
	var anchors = document.querySelectorAll("a");

	buttons.forEach(function (button) {
		button.addEventListener("click", function () {
			umami.track("button_click", { content: button.textContent });
		});
	});

	anchors.forEach(function (anchor) {
		anchor.addEventListener("click", function () {
			if (anchor.href) {
				umami.track("anchor_click", {
					content: anchor.textContent,
					href: anchor.href,
				});
			} else {
				umami.track("button_click", { content: anchor.textContent });
			}
		});
	});
});
