<?php 
spl_autoload_register(function ($class_name) {
    include $class_name . '.class.php';
});
session_start(); 
?>
<!DOCTYPE html>
<?php 
$pages = array();
$pages[] = new Page(1,2);
$pages[] = new Page(3,4);
$pages[] = new Page(5);

$page_num = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;
$page = $pages[$page_num-1];

$col  = 12 / $page->getNumGraphs(); 

if(!$_SESSION['filter']) { $_SESSION['filter'] = new Filter(); }
if(isset($_REQUEST['datum_start'])) {
    $_SESSION['datum_start'] = $_REQUEST['datum_start'];
    $_SESSION['filter']->addFilter('created_at', sprintf('"%s 00:00:00"', $_REQUEST['datum_start']), '>=',0);
}
    
if (isset($_REQUEST['datum_einde'])) {
    $_SESSION['datum_einde'] = $_REQUEST['datum_einde'];
    $_SESSION['filter']->addFilter('created_at', sprintf('"%s 23:59:59"', $_REQUEST['datum_einde']), '<=',1);
}
//$_SESSION['filter']->addFilter('created_at', "'2017-07-01 00:00:00'", '>=');


?>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        
        <!-- <?php echo $_SESSION['filter']->getFilter(); ?> -->

        <title><?php echo $page->getTitle(); ?></title>
        
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-alpha.6/css/bootstrap.min.css" integrity="sha384-rwoIResjU2yc3z8GV/NPeZWAv56rSmLldC3R/AZzGRnGxQQKnKkoFVhFQhNUwEyJ" crossorigin="anonymous">
        <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet" integrity="sha384-wvfXpqpZZVQGK6TAh5PVlGOfQNHSoD2xbE+QkPxCAFlNEevoEH3Sl0sibVcOQVnN" crossorigin="anonymous">
        
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
        
        <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
        <script type="text/javascript">
            google.charts.load('current', {'packages':['corechart','orgchart']});
            google.charts.setOnLoadCallback(drawCharts);
            
            function drawCharts() {
                <?php
                for($i=0; $i < $page->getNumGraphs(); $i++) {
                    $GET = $_GET;
                    unset($GET['page']);
                    if(count($GET)) {
                        $get_passtrough = '&'.http_build_query($GET);
                    } else {
                        $get_passtrough = '&blaat=1';
                    }
                    
                    printf('drawChart("%1$s", "query.php?id=%2$s");', 1+$i, $page->getGraphId($i).$get_passtrough);
                }
                ?>
            }
            
            function drawChart(num, url) {
                id = 'col'+num;
                var jsonData = $.ajax({
                  url: url,
                  dataType: "json",
                  async: false
                  }).responseText;
                  
                jsonData = JSON.parse(jsonData);
                $('#column-'+num+'-header').html(jsonData.metadata.title);
                if(jsonData.metadata.subtitle!=null) {
                        $('#column-'+num+'-header').append("<span style=\"font-weight: normal\">" + jsonData.metadata.subtitle + "</span>");
                }
                  
                // Create our data table out of JSON data loaded from server.
                var data = new google.visualization.DataTable(jsonData.table);

                var options = {
                  'title': "",
                  'legend': { position: 'none' },
                  'height': 500
                }
                $.extend(options, jsonData.metadata.options);
                
                
                ChartType = jsonData.metadata.charttype
                var chart = new google.visualization[ChartType](document.getElementById(id));

                chart.draw(data, options);
                google.visualization.events.addListener(chart, 'select', function(e) {
                    var row_num = chart.getSelection()[0].row;
                    var col_id  = data.getColumnId(0);
                    if(jsonData.metadata.event!=null) {
                        eval(jsonData.metadata.event);
                    }
                    /*alert(col_id+': '+data.getFormattedValue(row_num,0));*/
                });
              }
              
              $(window).resize(function(){
                drawCharts();
              });
              
              function showTweet(tweet_id) {
                  var jsonData = $.ajax({
                      url: 'query.php?tweet_id='+tweet_id,
                      dataType: "json",
                      async: false
                      }).responseText;
                  
                  jsonData = JSON.parse(jsonData);
                  
                  $("#Modal1Title").html(jsonData.table.rows[0].c[1].v + ' (' + jsonData.table.rows[0].c[2].v + ')');
                  $("#Modal1Body").html(jsonData.table.rows[0].c[3].v);
                  $("#Modal1").modal();
              }
              
              function showUserTweets(user_screen_name) {
                  var jsonData = $.ajax({
                      url: 'query.php?user_screen_name='+user_screen_name,
                      dataType: "json",
                      async: false
                  }).responseText;
                  jsonData = JSON.parse(jsonData);
                  var tbl = document.createElement('table');
                  tbl.setAttribute('border', '1');
                  var tbody = document.createElement('tbody');
                  for(i=0; i < jsonData.table.rows.length; ++i) {
                      var tr = document.createElement('tr');
                      tweet_id = jsonData.table.rows[i].c[0].v;
                      user_screen_name = jsonData.table.rows[i].c[1].v;
                      created_at = jsonData.table.rows[i].c[2].v;
                      tweet_text = jsonData.table.rows[i].c[3].v;
                      
                      /*var td = document.createElement('td')
                      td.appendChild(document.createTextNode(created_at));
                      tr.appendChild(td);*/
                      
                      var td = document.createElement('td')
                      td.appendChild(document.createTextNode(tweet_text));
                      tr.appendChild(td);
                      tbody.appendChild(tr);
                  }
                  tbl.appendChild(tbody);
                  $("#Modal1Title").html("Tweets of " + user_screen_name);
                  $("#Modal1Body").html(tbl);
                  $("#Modal1").modal();
                  
                  /*alert(jsonData.table.rows[0].c[0].v);*/
                  /*alert(user_screen_name);*/
              }
        </script>
        
        <link rel="stylesheet" href="style.css">
    </head>
    <body>
        <?php include('navbar.php'); ?>

        <div class="container-fluid" style="width: 100%; margin-top: 1em;">
            <div class="row">
                <div class="col-12 page-header"><?php echo $page->getTitle(); ?></div>
            </div>
            
            <div class="row" style="min-height: 600px;">
                <?php
                for($i=1; $i <= $page->getNumGraphs(); $i++) {
                    printf('<div class="col-%1$s">
                    <div class="column-header" id="column-%2$s-header"><!--Column %2$s header--></div>
                    <div class="column-content"><div id="col%2$s"></div></div>
                </div>', $col, $i);
                }
                ?>
            </div>
        </div>

        <footer class="footer fixed-bottom">
            <div class="container-fluid" style="width: 100%; margin-top: 1em;">
                 <div class="row" >
                    <div class="col-11"></div>
                    <div id="page-corner" class="col-1"><?php echo $page_num;?></div>
                </div>
            </div>
        </footer>

        <?php include('modal.php'); ?>        
       
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
        <!-- Latest compiled and minified JavaScript -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/tether/1.4.0/js/tether.min.js" integrity="sha384-DztdAPBWPRXSA/3eYEEUWrWCy7G5KFbe8fFjk5JAIxUYHKkDx6Qin1DkWx51bBrb" crossorigin="anonymous"></script>
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-alpha.6/js/bootstrap.min.js" integrity="sha384-vBWWzlZJ8ea9aCX4pEW3rVHjgjt7zpkNpZk+02D9phzyeVkE+jo0ieGizqPLForn" crossorigin="anonymous"></script>
        

    </body>
</html>