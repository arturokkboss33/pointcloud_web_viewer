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
      <h1 style="font-size:38px;">EU-FP7 CADDY<br>Underwater Stereo Images Dataset</h1><br />
      <p class="lead">Stereo images and generated point clouds of divers' poses hand gestures in different underwater scenarios.</p>

      <p class="justify">
      <br>
      The images available were collected with a Bumblebee XB3 FireWire Stereo Vision System during different research trials carried out within the  <a href="http://caddy-fp7.eu/" target="_blank">EU-FP7 CADDY</a> project (Cognitive Autonomous Diving Buddy). With the creation of this dataset, research in two specific areas was made: <b>diver body pose estimation</b> and <b>hand gesture recognition</b>.
      <br>
      </p>

      <h2 style="font-size:24px">Diver body pose estimation</h2>
      <p class="justify">
      The AUV (Autonomous Underwater Vehicle) needs to face the diver from the front in order to communicate with him/her through an attached tablet. It is also the optimal position to monitor the diver's behavior and overall well-being e.g. breathing pattern, equipment position, etc.
      </p> 
      <p class="justify">
      To keep the AUV in front of the diver at all times, the diver wears a system of inertial sensors in the suit that transmits his pose acoustically (DiverNet) <a href="http://ieeexplore.ieee.org/document/7133640/" target="_blank">[1]</a>. However, acoustic communication's bandwith and tranmission rate (~5s) prevents the system from having this information in real time. For this reason, diver pose estimation methods based on stereo images were developed [2] and this database was created. 
      </p>
      <p class="justify">
      To collect the data, divers were asked to perform three tasks in front of the AUV: turn 360° horizontally (chest pointing downwards) and vertically, clockwise and anticlockwise, and swim freely. For the latter, the AUV was operated manually to follow the diver. Ground thruth is provided by the inertial sensor located in the diver's chest, which logs the data to an underwater tablet directly hooked to the DiverNet or to an on-land computer through an optic fiber cable. Three data collection experiments were done in an open-sea environment in Biograd na Moru, Croatia; and in an indoor pool at Brodarski Institute, Zagreb, Croatia. 
      </p>  
 
      <p class="justify">
      We describe the files provided for each dataset as follows:
 
      <ul class="text" id="introul">

      	<li>Sample pointcloud of the diver (view only)</li>
      	<li>Zip file with stereo images (rectified)</li>
	<li>ROS bagfile of the recorded experiment wich publishes the following topics:</li>

	<ul class=text>
		<li><i>/stereo_camera/rect/{left,right}/image_raw</i> - Rectified color images</li>
		<li><i>/diver_heading</i> - Raw measurement of the diver orientation in radians</li>
		<li><i>/stereo_camera/rect/filtered_divernet_heading</i> - Filtered diver orientation after median filter and offset correction</li>
	</ul>

	<li>YAML file that shows the bagfile timestamp and diver pose associated with each stereo pair</li>

      </ul> 
      </p>

      <p class="justify">i
      It is important to mention that the bagfile shows the recording of the complete experiment whereas the provided stereo images are the ones particularly used for the developed algorithms. They only include images where the diver appears on the field of view and from which sufficiently dense point clouds could be generated. For a more detailed explanation of our methodology please refer to the <i>Publications</i> section [2].
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
                <p><a class="btn btn-sm btn-primary" href="view/<?php echo $file ?>">PC view</a>
                <?php if (file_exists($zipFile)) { ?>
                <a class="btn btn-sm btn-success" href="<?php echo $zipFile ?>">Stereo</a>
				<?php } ?>
                <?php if (file_exists($bagFile)) { ?>
		<a class="btn btn-sm btn-success" href="<?php echo $bagFile ?>">Bagfile</a></p>
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

