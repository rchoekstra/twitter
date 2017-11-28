<nav class="navbar navbar-toggleable-sm navbar-light bg-faded center">
  <button class="navbar-toggler navbar-toggler-right" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
  </button>
  <!--<a class="navbar-brand" href="#">-</a>-->

  <div class="collapse navbar-collapse" id="navbarSupportedContent">
    <ul class="navbar-nav mx-auto ">
        
<?php 
    printf('<li class="nav-item">
            <a class="nav-link %1$s" href="%2$s">&lt;</a>
          </li>'
          , $page_num==1 ? "disabled" : "" 
          , $page_num==1 ? "#"        : "?page=".($page_num-1));
    
    foreach($pages as $key => $value)
    {
        printf('<li class="nav-item %1$s">
                <a class="nav-link" href="?page=%2$s">%2$s</a>
              </li>', $key+1==$page_num ? "active" : "" , $key+1);
    }
    
    printf('<li class="nav-item">
            <a class="nav-link %1$s" href="%2$s">&gt;</a>
          </li>'
          , $page_num==count($pages) ? "disabled" : "" 
          , $page_num==count($pages) ? "#"        : "?page=".($page_num+1));
?>
        <li class="nav-item">
            <a class="nav-link" href="index.php"><i class="fa fa-filter" aria-hidden="true"></i>
            </a>
        </li>
    </ul>
  </div>
</nav>