@extends($stats_layout)

@section('page-contents')
	<div id="pageViewsLine" class="chart no-padding" style="height:200px;"></div>
@stop

@section('page-secondary-contents')
	<table id="table_div" class="display" cellspacing="0" width="100%"></table>
@stop

@section('inline-javascript')
	jQuery(function()
    {
		
		$("input.dateRange").daterangepicker({
			"alwaysShowCalendars": true,
			opens: "left",
			startDate: "{{explode('~', $date_range)[0]}}",
			endDate: "{{explode('~', $date_range)[1]}}",
			ranges: {
				"今天": [moment(), moment()],
				"過去 7 天": [moment().subtract(6, "days"), moment()],
				"本月": [moment().startOf("month"), moment().endOf("month")],
				"上個月": [moment().subtract(1, "month").startOf("month"), moment().subtract(1, "month").endOf("month")]
			},
			locale: {
				format: "YYYY-MM-DD",
				separator: " ~ ",
				applyLabel: "確定",
				cancelLabel: "清除",
				fromLabel: "開始日期",
				toLabel: "結束日期",
				customRangeLabel: "自訂日期區間",
				daysOfWeek: ["日", "一", "二", "三", "四", "五", "六"],
				monthNames: ["1月", "2月", "3月", "4月", "5月", "6月",
				"7月", "8月", "9月", "10月", "11月", "12月"
				],
				firstDay: 1
			}
		});
		$("input.dateRange").on("cancel.daterangepicker", function(ev, picker) {
			$(this).val("");
		});
		$("input.dateRange").on("apply.daterangepicker", function(ev, picker) {
			location.href= "{{route('tracker.stats.index')}}?date_range="+ $(this).val();
		});

		console.log(jQuery('#pageViews'));

		var pageViewsLine = Morris.Line({
            element: 'pageViewsLine',
            parseTime:false,
			grid: true,
			data: [{'date': 0, 'total': 0}],
			xkey: 'date',
			ykeys: ['total'],
			labels: ['Page Views']
		});

		jQuery.ajax({
			type: "GET",
			url: "{{ route('tracker.stats.api.pageviews') }}",
			data: { }
		})
		.done(function( data ) {
		    console.log(data);
			pageViewsLine.setData(formatDates(data));
		});

		var convertToPlottableData = function(data)
		{
			plottable = [];

			jsondata = JSON.parse(data);

            for(key in jsondata)
            {
                plottable[key] = {
					label: jsondata[key].label,
					data: jsondata[key].value
				}
            }

			return plottable;
        };

		var formatDates = function(data)
        {
			data = JSON.parse(data);

            for(key in data)
            {
                if (data[key].date !== 'undefined')
                {
					//data[key].date = moment(data[key].date, "YYYY-MM-DD").format('dddd[,] MMM Do');
					data[key].date = data[key].date;
				}
            }

			return data;
		};
	});

	@include(
        'pragmarx/tracker::_datatables',
        array(
            'datatables_ajax_route' => route('tracker.stats.api.pageviewsbyhost'),
            'datatables_columns' =>
            '
                { "data" : "host",     "title" : "'.trans('tracker::tracker.host').'", "orderable": true, "searchable": false },
                { "data" : "total",    "title" : "'.trans('tracker::tracker.pageview').'", "orderable": false, "searchable": false },
            '
        )
    )

@stop

@section('required-scripts-top')
	<!-- Page-Level Plugin Scripts - Main -->
	<script src="{{ $stats_template_path }}/bower_components/raphael/raphael.min.js"></script>
	<script src="{{ $stats_template_path }}/bower_components/morrisjs/morris.min.js"></script>

	<!-- Page-Level Plugin Scripts - Flot -->
	<!--[if lte IE 8]><script src="{{ $stats_template_path }}/js/excanvas.min.js"></script><![endif]-->
	<script src="{{ $stats_template_path }}/bower_components/flot/jquery.flot.js"></script>
	<script src="{{ $stats_template_path }}/bower_components/flot/jquery.flot.resize.js"></script>
	<script src="{{ $stats_template_path }}/bower_components/flot/jquery.flot.pie.js"></script>
    <script src="{{ $stats_template_path }}/bower_components/flot.tooltip/js/jquery.flot.tooltip.min.js"></script>
@stop
