//https://developers.google.com/chart/interactive/docs/basic_load_libs
//google.load("visualization", "1", {packages:["corechart", "gauge", "orgchart", "geochart"]});
google.load('visualization', '1.0', {'packages':['corechart']});

(function($) {
    Drupal.behaviors.googleChart = {
        attach: function(context, settings) {
            google.setOnLoadCallback(drawChart);
            function drawChart() {
                for (var chartId in settings.chart) {
                    var data = new google.visualization.DataTable();
                        data.addColumn('string', 'Label');
                        for (var col in settings.chart[chartId].columns) {
                            data.addColumn('number', settings.chart[chartId].columns[col]);
                        }
                        for (var i in settings.chart[chartId].header) {
                            var row = new Array();
                            for (var j in settings.chart[chartId].rows) {
                                row[j] = parseFloat(settings.chart[chartId].rows[j][i]);
                            }
                            row.unshift(settings.chart[chartId].header[i]);
                            data.addRows([row])
                        };

                    var options = settings.chart[chartId].options;
                    var chart = new Object;
                    var element = document.getElementById(settings.chart[chartId].containerId);
                    if (element) {
                        chart[settings.chart[chartId]] = new google.visualization[settings.chart[chartId].chartType](element);
                        chart[settings.chart[chartId]].draw(data, options);
                    }
                }
            }
        }
    };
})(jQuery);