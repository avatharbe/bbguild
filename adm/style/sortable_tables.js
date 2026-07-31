/**
 * bbGuild ACP - client-side sortable tables.
 *
 * Enhances every data table (thead + tbody) on the page so clicking a column
 * header sorts the rows in the browser, with no page reload. It is numeric
 * aware, toggles ascending/descending, shows a small up/down indicator, keeps
 * the zebra striping correct, and never touches the trailing "Action" column,
 * image columns, or the footer count row.
 *
 * Where a header already carries a server-side sort link (races/classes), the
 * click is intercepted so the instant client-side sort runs instead of the
 * full-page reload; the server-side sort still works as a no-JS fallback.
 */
(function () {
	'use strict';

	function textOf(cell) {
		return cell ? (cell.textContent || '').replace(/\s+/g, ' ').trim() : '';
	}

	function looksNumeric(s) {
		return s !== '' && /^-?[0-9][0-9.,]*$/.test(s);
	}

	function toNumber(s) {
		return parseFloat(s.replace(/,/g, ''));
	}

	// Data rows only - the footer count row uses <th>, so exclude it.
	function dataRows(tbody) {
		return Array.prototype.filter.call(tbody.rows, function (r) {
			return r.getElementsByTagName('th').length === 0;
		});
	}

	function footerRows(tbody) {
		return Array.prototype.filter.call(tbody.rows, function (r) {
			return r.getElementsByTagName('th').length > 0;
		});
	}

	function restripe(rows) {
		for (var i = 0; i < rows.length; i++) {
			rows[i].classList.remove('row1', 'row2');
			rows[i].classList.add(i % 2 === 0 ? 'row1' : 'row2');
		}
	}

	function columnHasImages(rows, idx) {
		for (var i = 0; i < rows.length; i++) {
			var cell = rows[i].cells[idx];
			if (cell && cell.getElementsByTagName('img').length) {
				return true;
			}
		}
		return false;
	}

	function sortBy(table, idx, asc) {
		var tbody = table.tBodies[0];
		var rows = dataRows(tbody);
		var allNumeric = rows.every(function (r) {
			return looksNumeric(textOf(r.cells[idx]));
		});
		rows.sort(function (a, b) {
			var x = textOf(a.cells[idx]);
			var y = textOf(b.cells[idx]);
			var res = allNumeric
				? toNumber(x) - toNumber(y)
				: x.localeCompare(y, undefined, { sensitivity: 'base', numeric: true });
			return asc ? res : -res;
		});
		rows.forEach(function (r) { tbody.appendChild(r); });
		// Keep the footer count row(s) at the bottom.
		footerRows(tbody).forEach(function (r) { tbody.appendChild(r); });
		restripe(rows);
	}

	function enhance(table) {
		var thead = table.tHead;
		var tbody = table.tBodies[0];
		if (!thead || !tbody || !thead.rows.length) {
			return;
		}
		var headRow = thead.rows[thead.rows.length - 1];
		var ths = headRow.cells;
		var rows = dataRows(tbody);
		if (rows.length < 2) {
			return;
		}
		var lastIdx = ths.length - 1;

		for (var i = 0; i < ths.length; i++) {
			(function (th, idx) {
				if (th.classList.contains('bb-nosort')) { return; }
				if (idx === lastIdx) { return; }             // trailing Action column
				if (columnHasImages(rows, idx)) { return; }  // image columns

				th.style.cursor = 'pointer';
				th.setAttribute('data-bb-sortable', '1');

				th.addEventListener('click', function (e) {
					if (e.target && e.target.closest && e.target.closest('a')) {
						e.preventDefault(); // neutralise any server-side sort link
					}
					var asc = th.getAttribute('data-bb-asc') !== 'asc';

					for (var k = 0; k < ths.length; k++) {
						ths[k].removeAttribute('data-bb-asc');
						var old = ths[k].querySelector('.bb-sort-ind');
						if (old) { old.parentNode.removeChild(old); }
					}

					th.setAttribute('data-bb-asc', asc ? 'asc' : 'desc');
					var ind = document.createElement('span');
					ind.className = 'bb-sort-ind';
					ind.style.marginLeft = '4px';
					ind.textContent = asc ? '▲' : '▼';
					th.appendChild(ind);

					sortBy(table, idx, asc);
				});
			})(ths[i], i);
		}
	}

	function init() {
		var tables = document.getElementsByTagName('table');
		for (var i = 0; i < tables.length; i++) {
			if (tables[i].tHead && tables[i].tBodies.length) {
				enhance(tables[i]);
			}
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
