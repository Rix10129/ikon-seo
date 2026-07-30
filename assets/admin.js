document.addEventListener('click', function (event) {
	var confirmButton = event.target.closest('[data-confirm]');
	if (confirmButton && !window.confirm(confirmButton.getAttribute('data-confirm'))) {
		event.preventDefault();
		return;
	}

	var button = event.target.closest('[data-copy-target]');
	if (!button) {
		return;
	}

	var target = document.querySelector(button.getAttribute('data-copy-target'));
	if (!target || !navigator.clipboard) {
		return;
	}

	navigator.clipboard.writeText(target.textContent.trim()).then(function () {
		var original = button.textContent;
		button.textContent = 'Copied';
		window.setTimeout(function () {
			button.textContent = original;
		}, 1400);
	});
});

document.addEventListener('DOMContentLoaded', function () {
	var industry = document.querySelector('[data-ikon-industry]');
	var entity = document.querySelector('[data-ikon-entity]');
	if (!industry || !entity) {
		return;
	}

	function refreshEntities(useRecommended) {
		var selectedIndustry = industry.value;
		var current = entity.value;
		var available = [];

		Array.prototype.forEach.call(entity.options, function (option) {
			var industries = (option.getAttribute('data-industries') || '').split(',');
			var allowed = industries.indexOf(selectedIndustry) !== -1;
			option.hidden = !allowed;
			option.disabled = !allowed;
			if (allowed) {
				available.push(option.value);
			}
		});

		if (useRecommended || available.indexOf(current) === -1) {
			var selected = industry.options[industry.selectedIndex];
			var recommended = selected ? selected.getAttribute('data-recommended') : '';
			entity.value = available.indexOf(recommended) !== -1 ? recommended : available[0];
		}
	}

	industry.addEventListener('change', function () {
		refreshEntities(true);
	});
	refreshEntities(false);
});

document.addEventListener('DOMContentLoaded', function () {
	var countdown = document.querySelector('[data-ikon-pairing-expires]');
	if (!countdown) {
		return;
	}

	var expiresAt = parseInt(countdown.getAttribute('data-ikon-pairing-expires'), 10) * 1000;
	if (!expiresAt) {
		return;
	}

	function updateCountdown() {
		var remaining = Math.max(0, Math.ceil((expiresAt - Date.now()) / 1000));
		if (!remaining) {
			countdown.textContent = 'Pairing code expired. Create a new code.';
			return;
		}
		var minutes = Math.floor(remaining / 60);
		var seconds = remaining % 60;
		countdown.textContent = 'Expires in ' + minutes + ':' + String(seconds).padStart(2, '0');
		window.setTimeout(updateCountdown, 1000);
	}

	updateCountdown();
});
