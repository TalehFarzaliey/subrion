$(function()
{
	var $search = $('#input-search-query'),
		$filtersForm = $('#js-item-filters-form');

	if ($search.length > 0)
	{
		var pattern = new RegExp('('+ $search.val() +')', 'mgi');

		$('.search-results :not(:has(div,span,p,td,table,a,img)):not(legend):visible:not(br)')
			.filter('div,p,td,span,a')
			.each(function()
			{
				var text = $(this).text();
				if (pattern.exec(text))
				{
					text = text.replace(pattern, '<span class="highlight">$1</span>');
					$(this).html(text);
				}
			});
	}

	$('.js-search-sorting-header a').on('click', function(e)
	{
		e.preventDefault();

		var data = $(this).data();

		if (undefined !== data.field || undefined !== data.order)
		{
			if (undefined !== data.field) intelli.search.setParam('sortingField', data.field);
			if (undefined !== data.order) intelli.search.setParam('sortingOrder', data.order);

			intelli.search.run();
		}
	});

	$('#js-search-results-pagination').on('click', '.pagination a', function(e)
	{
		e.preventDefault();
		intelli.search.run($(this).text());
	});

	if ($filtersForm.length > 0)
	{
		$('select.js-interactive', $filtersForm).select2();
		$('select, input', $filtersForm).not('.no-js').on('change', function()
		{
			intelli.search.run();
		});

		var $container = $('#js-search-results-container'),
			$defaultOptions = $('#js-default-search-options');

		if ($defaultOptions.length)
		{
			intelli.search.setParam('sortingField', $defaultOptions.data('field'));
			intelli.search.setParam('sortingOrder', $defaultOptions.data('order'));
		}

		intelli.search.initFilters();

		intelli.search.bindEvents(
			function(){$container.css('opacity', .3);},
			function(){$container.css('opacity', 1);}
		);
	}
});