<?php

// Sanity check
$valid = true;
$pcDataFolder = DATAFOLDER . '/' . $pcFolder;
$pcFile = $pcDataFolder . '/' . PCFILE;
$infoFile = $pcDataFolder . '/' . PCINFO;

console.log("Pointcloud with " + $infoFile + " points loaded." + $pcDataFolder);

if (!is_dir($pcDataFolder)) {
  $valid = false;
}
if (!file_exists($pcFile) || !file_exists($infoFile)) {
  $valid = false;
}

if (!$valid) {
  header("Location: ../404");
  die();
}

// Count the points of the pointcloud
$lineCountCloud = 0;
$handle = fopen($pcFile, "r");
while(!feof($handle)){
  $line = fgets($handle);
  $lineCountCloud++;
}
fclose($handle);
$lineCountCloud--;


// Pointcloud url
if (ENVIRONMENT === 'production') {

  $pcUrl = PRODURL . $pcFile;

}
else {
  $pcUrl = DEVELURL . $pcFile;
}

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
  <head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <meta charset="utf-8">
    <title>Jacobs Robotics Pointcloud Viewer</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <!--[if lt IE 9]>
      <script src="//html5shim.googlecode.com/svn/trunk/html5.js"></script>
    <![endif]-->
    <style>
      body {margin:0; padding:0; background-color:black}
      canvas {width:100%; height:100%;}
    </style>
  </head>

  <body>
    <script src="//ajax.googleapis.com/ajax/libs/jquery/2.0.2/jquery.min.js"></script>
    <script src="../js/bootstrap.min.js"></script>
    <script src="../js/webgl-detector.js"></script>
    <script src="../js/three.min.js"></script>
    <script src="../js/three.trackballcontrols.js"></script>
    <script src="../js/papaparse.min.js"></script>
    <script>

      // Check if iframe or not to change the button
      function inIframe () {
        if ( window.location !== window.parent.location ) {
          return true;
        } else {
          return false;
        }
      }

      // Build a color
      function buildColor(v){
        var pi = 3.151592;
        var r = Math.cos(v*2*pi + 0) * (127) + 128;
        var g = Math.cos(v*2*pi + 2) * (127) + 128;
        var b = Math.cos(v*2*pi + 4) * (127) + 128;
        var color = 'rgb(' + Math.round(r) + ',' + Math.round(g) + ',' + Math.round(b) + ')';
        return color;
      }

      // When page loads
      $(function() {

        // Draw the progressbar on the middle
        var left = Math.round( (window.innerWidth - 400)/2 );
        $("#progressbar-container").css("left",left + "px");

        // Scene
        var scene = new THREE.Scene();

        // Camera
        var camera = new THREE.PerspectiveCamera(40, window.innerWidth / window.innerHeight, 0.1, 300);
	//var camera  = new THREE.OrthographicCamera( window.innerWidth , window.innerWidth , window.innderHeight, window,innerHeight, 1, 1000 );
        camera.position.z = -2.0;
        //camera.rotateOnAxis(new THREE.Vector3(1, 0, 0), 3.14);
	//camera.rotation.set(100.0, 0.4, 3.14 ,"XYZ");
	//camera.rotation.x = camera.rotation.x + 1.57;
        //camera.updateProjectionMatrix();

        // Detect webgl support
        if (!Detector.webgl) {
          $("#progressbar-container").hide();
          Detector.addGetWebGLMessage();
          return;
        }

        // The renderer
        var renderer = new THREE.WebGLRenderer();
        renderer.setSize(window.innerWidth,window.innerHeight -4);

        // Render the scene
        function render() {

          camera.rotation.z = camera.rotation.z + 3.14;

          renderer.render(scene, camera);
        }

        // Setup controls
        var controls = new THREE.TrackballControls(camera);
        controls.rotateSpeed = 2.0;
        controls.zoomSpeed = 10.2;
        controls.panSpeed = 1.6;
        controls.noZoom = false;
        controls.noPan = false;
        controls.staticMoving = true;
        controls.dynamicDampingFactor = 0.3;
        controls.keys = [65, 17, 18];
        controls.addEventListener('change', render);

        // Render loop
        function animate() {
          requestAnimationFrame(animate);
          controls.update();
        }

        // Init the geometry
        var pointSize = 0.015;
        var geometryCloud = new THREE.Geometry({dynamic:true});
        var geometryCloudLabeled2= new THREE.Geometry({dynamic:true});
        var material = new THREE.PointCloudMaterial({size:pointSize, vertexColors:true});

        var pointcloudLoaded = false;
        var useLabelColors = false;
        var labelColorsPresent = false;
        var pcColors = [];
        var pcColorLabels2 = [];

        var min_x = 0, min_y = 0, min_z = 0, max_x = 0, max_y = 0, max_z = 0, freq = 0;

      
        // Load the pointcloud
        Papa.parse("<?php echo $pcUrl ?>", {
          download: true,
          worker: true,
          step: function(row) {
            var line = row.data[0];
            if (line.length == 0) return;
            if (line.length > 9) return;

            // Point
            var x = parseFloat(line[0]);
            var y = parseFloat(line[1]);
            var z = parseFloat(line[2]);
            if(x>max_x) max_x = x;
            if(x<min_x) min_x = x;
            if(y>max_y) max_y = y;
            if(y<min_y) min_y = y;
            if(z>max_z) max_z = z;
            if(z<min_z) min_z = z;
            geometryCloud.vertices.push(new THREE.Vector3(x, y, z));
            geometryCloudLabeled2.vertices.push(new THREE.Vector3(x, y, z));

            // Color
            pcColors.push(new THREE.Color('rgb(' + line[3] + ',' + line[4] + ',' + line[5] + ')'));
	    labelColorsPresent = true;
	    pcColorLabels2.push(new THREE.Color('rgb(' + line[6] + ',' + line[7] + ',' + line[8] + ')'));


            freq++;
            if (freq > 2000) {
              var per = Math.round((geometryCloud.vertices.length) * 100 / (<?php echo $lineCountCloud ?>));
              $("#progressbar").attr("aria-valuenow", per);
              $("#progressbar").css("width", per + "%");
              $("#progressbar").text(per + "%");
              freq = 0;
            }
          },
          complete: function() {
            console.log("Pointcloud with " + geometryCloud.vertices.length + " points loaded.");

            // Build the scene
	    var pointcloud 
            var pointcloud_labeled2	    
	    	    	
            geometryCloudLabeled2.colors = pcColorLabels2;  
	    pointcloud_labeled2 = new THREE.PointCloud(geometryCloudLabeled2, material);
	    
	    geometryCloud.colors = pcColors;
	    pointcloud = new THREE.PointCloud(geometryCloud, material);
	    
            //scene.fog = new THREE.FogExp2(0x000000, 0.0009);


            scene.add(pointcloud);

            // Remove the progressbar
            $("#progressbar-container").hide();
            if (inIframe()) {
              $("#controls-iframe").show();
            }
            else {
              $("#controls-browser").show();
            }

            // Add the canvas, render and animate
            var container = document.getElementById('container');
            container.appendChild(renderer.domElement);
            pointcloudLoaded = true;
            render();
            animate();
          }
        });

        // Changes the color of the points
        function changeColor(color_mode) {
          // Clear the geometry colors and maintain the vertices
          var vertices = geometryCloud.vertices;
          geometryCloud = new THREE.Geometry();
          geometryCloud.vertices = vertices;

          if (color_mode == 'rgb')
              geometryCloud.colors = pcColors;
          else {
            var axis_colors = [];
            for (var i=0; i<geometryCloud.vertices.length; i++) {
              var x = geometryCloud.vertices[i].x;
              var y = geometryCloud.vertices[i].y;
              var z = geometryCloud.vertices[i].z;
              var t = 0;
              switch(color_mode) {
                case 'x':
                  t = (x-min_x)/(max_x-min_x);
                  break;
                case 'y':
                  t = (y-min_y)/(max_y-min_y);
                  break;
                case 'z':
                  t = (z-min_z)/(max_z-min_z);
                  break;
                default:
                  alert('Color mode option not available');
                  break;
              }
              axis_colors.push(new THREE.Color(buildColor(t)));
            }
            geometryCloud.colors = axis_colors;
          }
        }
        
        // Changes the representation style
        function changeRepresentation() {
            useLabelColors = !useLabelColors;
        }

        // Zoom on wheel
        function onMouseWheel(evt) {
          var d = ((typeof evt.wheelDelta != "undefined")?(-evt.wheelDelta):evt.detail);
          d = 100 * ((d>0)?1:-1);
          console.log(d);
          var cPos = camera.position;
          if (isNaN(cPos.x) || isNaN(cPos.y) || isNaN(cPos.y)) return;

          // Your zomm limitation
          // For X axe you can add anothers limits for Y / Z axes
          if (cPos.z > 50  || cPos.z < -50 ) return;

          mb = d>0 ? 1.1 : 0.9;
          cPos.x  = cPos.x * mb;
          cPos.y  = cPos.y * mb;
          cPos.z  = cPos.z * mb;
        }

        // Handle colors and pointsize
        function onKeyDown(evt) {
          if (pointcloudLoaded) {
            // Increase/decrease point size
            if (evt.keyCode == 189 || evt.keyCode == 109 || evt.keyCode == 40) {
              pointSize -= 0.005;
            }
            if (evt.keyCode == 187 || evt.keyCode == 107 || evt.keyCode == 38) {
              pointSize += 0.005;
            }
            if(pointSize < 0.0001) {
                pointSize = 0.0001;
            }
            if(pointSize > 0.05) {
                pointSize = 0.05;
            }

            if (evt.keyCode == 32) changeRepresentation();
            /*if (evt.keyCode == 49) changeColor('x');
            if (evt.keyCode == 50) changeColor('y');
            if (evt.keyCode == 51) changeColor('z');
            if (evt.keyCode == 52) changeColor('rgb');*/

            // Re-render the scene
            material = new THREE.PointCloudMaterial({ size: pointSize, opacity: 0.8, vertexColors: true });
            var pointcloud = new THREE.PointCloud(geometryCloud, material);
            var pointcloud_labeled = new THREE.PointCloud(geometryCloudLabeled2, material);
            scene = new THREE.Scene();
            scene.fog = new THREE.FogExp2(0x000000, 0.0009);
            if(useLabelColors) {
                scene.add(pointcloud_labeled);
            } else {
                scene.add(pointcloud);
            }
            render();
          }
        }

        // Mouse and keyboard events
        window.addEventListener('DOMMouseScroll', onMouseWheel, false);
        window.addEventListener('mousewheel', onMouseWheel, false);
        document.addEventListener("keydown", onKeyDown, false);

      });
    </script>

    <div id="container" style="width:100%; height:100%; position:relative;">

      <div id="controls-browser" style="position:absolute; top:5px; left:5px; z-index:999999; display:none;">
        <a class="btn btn-sm btn-default" href="../home">back</a>
        <p style="color:#aaa; margin-top:5px; font-size:12px;">
          - space: switch on and off point cloud color information<br />
          - +/- or &#8679 (arrow up) / &#8681 (arrow down): change point size<br/>
          - left/right mouse button: pan and move object <br/>
          - scroll mouse: zoom in and out object
        </p>
      </div>
      <div id="controls-iframe" style="position:absolute; top:5px; left:5px; z-index:999999; display:none;">
        <a style="font-size:11px;" href="http://www.robotics.jacobs-university.de/pointclouds/view/<?php echo $pcFolder ?>" target="_blank">view on robotics.jacobs-university.de</a>
      </div>

      <div id="progressbar-container" class="progress progress-striped" style="position:absolute; z-index:999999; width:400px; top:230px;">
        <div id="progressbar" class="progress-bar progress-bar-info" role="progressbar" aria-valuenow="1" aria-valuemin="0" aria-valuemax="100" style="width:1%">1%</div>
      </div>

    </div>

  </body>
</html>

