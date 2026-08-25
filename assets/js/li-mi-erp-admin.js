/* ============================================================
   Micro ERP Admin JS
   ============================================================ */
(function ($) {
	'use strict';

	function formatMoney(n) {
		return parseFloat(n || 0).toFixed(2);
	}

	/* ---------- Journal lines ---------- */
	var $journal = $('#journal-lines');
	if ($journal.length) {
		function journalTotals() {
			var d = 0,
				c = 0;
			$journal.find('.j-debit').each(function () {
				d += parseFloat(this.value) || 0;
			});
			$journal.find('.j-credit').each(function () {
				c += parseFloat(this.value) || 0;
			});
			$('.j-total-debit').text(formatMoney(d));
			$('.j-total-credit').text(formatMoney(c));

			var note = $('.j-balance-note');
			if (Math.abs(d - c) > 0.005) {
				note.css('color', '#d63638').text(
					'Debit (' + formatMoney(d) + ') does not match Credit (' + formatMoney(c) + ').'
				);
			} else {
				note.css('color', '#00a32a').text('Balanced: Debit = Credit = ' + formatMoney(d));
			}
		}

		$('.j-add-line').on('click', function () {
			var first = $journal.find('tbody tr').first();
			var clone = first.clone();
			clone.find('select').val('');
			clone.find('input[type=text]').val('');
			clone.find('input[type=number]').val('');
			$journal.find('tbody').append(clone);
			journalTotals();
		});

		$journal.on('click', '.j-remove', function () {
			if ($journal.find('tbody tr').length > 1) {
				$(this).closest('tr').remove();
				journalTotals();
			} else {
				alert('At least one journal line is required.');
			}
		});

		$journal.on('input', '.j-debit, .j-credit', function () {
			$(this).closest('tr').find('.j-debit, .j-credit').each(function () {
				if (parseFloat(this.value) > 0) {
					var other = $(this).hasClass('j-debit') ? 'j-credit' : 'j-debit';
					$(this).closest('tr').find('.' + other).val('');
				}
			});
			journalTotals();
		});

		journalTotals();
	}

	/* ---------- Items table (quotations & sales) ---------- */
	var $items = $('#items-table');
	if ($items.length) {
		function itemsTotals() {
			var subtotal = 0,
				tax = 0;
			$items.find('tbody tr').each(function () {
				var qty = parseFloat($(this).find('.i-qty').val()) || 0;
				var price = parseFloat($(this).find('.i-price').val()) || 0;
				var taxRate = parseFloat($(this).find('.i-tax').val()) || 0;
				var lineTotal = qty * price;
				subtotal += lineTotal;
				tax += lineTotal * (taxRate / 100);
				$(this).find('.i-line-total').text(formatMoney(lineTotal));
			});
			var discount = parseFloat($('.t-discount-input').val()) || 0;
			var grand = subtotal + tax - discount;
			$('.t-subtotal').text(formatMoney(subtotal));
			$('.t-tax').text(formatMoney(tax));
			$('.t-grand').text(formatMoney(grand));
		}

		$('.i-add').on('click', function () {
			var first = $items.find('tbody tr').first();
			var clone = first.clone();
			clone.find('input[type=text]').val('');
			clone.find('.i-price').val('');
			clone.find('.i-tax').val($('.i-tax').first().val());
			clone.find('.i-qty').val('1');
			clone.find('.i-line-total').text('—');
			$items.find('tbody').append(clone);
			itemsTotals();
		});

		$items.on('click', '.i-remove', function () {
			if ($items.find('tbody tr').length > 1) {
				$(this).closest('tr').remove();
				itemsTotals();
			} else {
				alert('At least one item is required.');
			}
		});

		$items.on('input', '.i-qty, .i-price, .i-tax', itemsTotals);
		$('.t-discount-input').on('input', itemsTotals);

		itemsTotals();
	}
})(jQuery);
