<?php 
spl_autoload_register(function ($class_name) {
    include $class_name . '.class.php';
});
session_start();
?>
<!DOCTYPE html>
<?php 

?>

<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

        <title>Pagina titel</title>
        
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-alpha.6/css/bootstrap.min.css" integrity="sha384-rwoIResjU2yc3z8GV/NPeZWAv56rSmLldC3R/AZzGRnGxQQKnKkoFVhFQhNUwEyJ" crossorigin="anonymous">
        
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
        
        <link rel="stylesheet" href="style.css">
                <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
        <script type="text/javascript">
            google.charts.load('current', {'packages':['corechart','controls']});
            google.charts.setOnLoadCallback(drawDashboard);
            var dashboard;
           
            function drawDashboard() {
                dashboard = new google.visualization.Dashboard(document.getElementById('dashboard'));

                var jsonData = $.ajax({
                  url: 'query.php?id=0',
                  dataType: "json",
                  async: false
                  }).responseText;
                  
                jsonData = JSON.parse(jsonData);
                  
                // Create our data table out of JSON data loaded from server.
                var data = new google.visualization.DataTable(jsonData.table);

                
                var options = {
                  'title': "",
                  'legend': { position: 'none' },
                  'height': 500
                }
                $.extend(options, jsonData.metadata.options);
                
                
                var chart = new google.visualization.ChartWrapper({
                    'chartType': 'ColumnChart',
                    'containerId': 'chart',
                    'options': options
                });
                
                var control = new google.visualization.ControlWrapper({
                    'controlType': 'DateRangeFilter', /*ChartRangeFilter*/
                    'containerId': 'control',
                    'options': {'width': '100%', 'filterColumnIndex': 0, 'ui': { format:{pattern: "dd-MM-yyyy"}}}
                });
                
                google.visualization.events.addListener(control,'statechange', function() { 
                    //console.log(control.getState().lowValue.toISOString());
                    //console.log(control.getState().highValue.toISOString());
                    $('input[name=datum_start]').val(control.getState().lowValue.toISOString().substring(0,10))
                    $('input[name=datum_einde]').val(control.getState().highValue.toISOString().substring(0,10))
                });
                
                
                dashboard.bind(control,chart);
                dashboard.draw(data);
                control.setState({'lowValue': new Date()-2592000000, 'highValue': new Date()});
              }
              
              $(window).resize(function(){
                drawDashboard();
              });
        </script>
    </head>
    <body>
        <?php /*include('navbar.php');*/ 
            $datum_start = isset($_SESSION['datum_start']) ? $_SESSION['datum_start'] : date('Y-m-d', strtotime("first day of -0 month"));
            $datum_einde = isset($_SESSION['datum_einde']) ? $_SESSION['datum_einde'] : date("Y-m-d");
        ?>
        
        <div id="dashboard" class="container">
            <div class="row">
                <div class="col-12">
                    <form action="index2.php" method="post">
                    <table class="table">
                        <tbody>
                            <tr>
                                <td>Datum start</td>
                                <td><input type="date" name="datum_start" value="<?php echo $datum_start; ?>"></td>
                            </tr>
                            <tr>
                                <td>Datum eind</td>
                                <td><input type="date" name="datum_einde" value="<?php echo $datum_einde; ?>"></td>
                            </tr>
                        </tbody>
                    </table>
                    <button type="submit" class="btn btn-default">Submit</button>
                    </form>
                </div>
            </div>
            
            <div class="row">
                <div id="control" class="col-12" style="text-align: center;"></div>
            </div>
            <div class="row">
                <div id="chart" class="col-12"></div>
            </div>
        </div>
    </body>
</html>