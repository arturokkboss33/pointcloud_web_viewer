  <?php
  include('header.php');
  ?>

  <script type="text/javascript">
  $( document ).ready(function() {
      $("[rel='tooltip']").tooltip();

      $('.thumbnail').hover(
          function(){
              $(this).find('.caption').slideDown(350); //.fadeIn(250)
          },
          function(){
              $(this).find('.caption').slideUp(250); //.fadeOut(205)
          }
      );
  
      $(".spoiler-trigger").click(function() {
	      $(this).parent().next().collapse('toggle');
	  });

      $("#buttonShow").click(function() {
	    $("#intro").toggleClass('hide');
	    $("#introul").toggleClass('hide');
            $(this).toggleClass('active');
      });

  });
  
  </script>

<style> 
.refhighlightbox {
    background-color: #e1dede;
    box-shadow: 5px 5px 2px #888888;
}
</style>

  <!-- Top -->
  <div class="row">

    <div class="col-lg-8">
      <h1 style="font-size:38px;">CADDY Underwater Stereo Images Dataset</h1><br />
      <p class="lead">Stereo images and generated point clouds of divers' hand gestures and poses in different underwater scenarios.</p>

      <p class="justify">
      <br>
	The images available were collected with a Bumblebee XB3 FireWire Stereo Vision System during different research trials carried out within the  <a href="http://caddy-fp7.eu/" target="_blank">EU-FP7 CADDY</a> project (Cognitive Autonomous Diving Buddy). With the creation of this dataset, research in two specific areas was made: diver <b>body pose estimation</b> and <b>hand gesture recognition</b>.
     <p class="refhighlightbox"><b>Diver pose estimation</b> <br> s<br>for Applications with Untrained End-Users" <br> In <i>Robotics and Autonomous Systems</i>, April 2015</p>
   
      <br>

      </p>  
    </div>
    
    <div class="col-lg-2 col-lg-offset-2">
      <div style="width:100%; height: 20px;"></div>
      <a title="Jacobs Robotics" href="http://robotics.jacobs-university.de"><img class="img-responsive" src="img/logo.png" width="250"></a>
    </div>

  </div>

  <!-- Separator -->
  <div class="row" style="text-align:center;">
    <div class="col-lg-12 col-sm-12 col-xs-12">
      <hr>
    </div>
  </div>

  <!-- Showcase -->
  <div class="row">
  <?php
  $files_array = array();
  foreach (new DirectoryIterator(DATAFOLDER) as $dirInfo) {
    if($dirInfo->isDir() && !$dirInfo->isDot()) {
      // Info file
      $pcFile = DATAFOLDER . '/' . $dirInfo->getFilename() . '/' . PCFILE;
      $infoFile = DATAFOLDER . '/' . $dirInfo->getFilename() . '/' . PCINFO;
      if (file_exists($pcFile) && file_exists($infoFile)) {
        // sort key, can also be a timestamp or something alike
        $key = $dirInfo->getFilename();
        $data = $dirInfo->getFilename();
        $files_array[$key] = $data;
	  }
    }
  }
  ksort($files_array);

  foreach($files_array as $key => $file){

      $pcFile = DATAFOLDER . '/' . $file . '/' . PCFILE;
      $infoFile = DATAFOLDER . '/' . $file . '/' . PCINFO;
      $zipFile = DATAFOLDER . '/' . $file . '/' . $file . '.zip';
      $bagFile = DATAFOLDER . '/' . $file . '/' . $file . '.bag.zip';

        // Read the info
        $folderName = $file;
        $fi = fopen($infoFile, 'r');
        $title = fgetcsv($fi);
        $meta = fgetcsv($fi);
        fclose($fi);

        // Sanity check
        if (sizeof($title) == 2) {
          $title = $title[1];

          $desc = '';
          if (sizeof($meta) == 2) {
            $desc = $meta[1];
          }
          $imgFile = DATAFOLDER . '/' . $file . '/' . PCIMG;
          if (!file_exists($imgFile)) {
            $imgFile = 'img/default.png';
          }

          $pcSize = round(filesize($pcFile) / (1000000));
          $zipSize = round(filesize($zipFile) / (1000000));
          $bagSize = round(filesize($bagFile) / (1000000000),2);

          ?>
          <div class="col-lg-3 col-sm-4 col-xs-6">
            <div class="thumbnail">
              <div class="caption">
                <h4><?php echo $title ?></h4>
                <div style="text-align:left; padding:5px;">
                  <h5><?php echo $desc ?></h5>
                  <?php if (file_exists($zipFile)) { ?>Stereo images: <?php echo $zipSize ?> MB<?php } ?><br>
		  <?php if (file_exists($bagFile)) { ?>Bagfile: <?php echo $bagSize ?> GB<?php } ?>
                </div>
                <p><a class="btn btn-sm btn-primary" href="view/<?php echo $file ?>">PC</a>
                <?php if (file_exists($zipFile)) { ?>
                <a class="btn btn-sm btn-success" href="<?php echo $zipFile ?>">St</a>
				<?php } ?>
                <?php if (file_exists($bagFile)) { ?>
		<a class="btn btn-sm btn-success" href="<?php echo $bagFile ?>">Bag</a></p>
				<?php } ?>
              </div>
              <img class="img-responsive" src="<?php echo $imgFile ?>">
            </div>
          </div>
          <?php
        }
  }
  ?>
  </div>

  <?php
  include('footer.php');
  ?>

