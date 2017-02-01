<?php

// Sanity check
$valid = true;
$pcDataFolder = DATAFOLDER . '/' . $pcFolder;
$pcFile = $pcDataFolder . '/' . PCFILE;
$meshVerticesFile = $pcDataFolder . '/' . MESHVERTICESFILE;
$meshFacesFile = $pcDataFolder . '/' . MESHFACESFILE;
$infoFile = $pcDataFolder . '/' . PCINFO;
if (!is_dir($pcDataFolder)) {
  $valid = false;
}
if (!file_exists($pcFile) || !file_exists($meshVerticesFile) || !file_exists($meshFacesFile) || !file_exists($infoFile)) {
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

$lineCountMesh = 0;

// Pointcloud url
if (ENVIRONMENT === 'production') {
  $pcUrl = PRODURL . $pcFile;
  $meshVerticesUrl = PRODURL . $meshVerticesFile;
  $meshFacesUrl = PRODURL . $meshFacesFile;
}
else {
  $pcUrl = DEVELURL . $pcFile;
  $meshVerticesUrl = DEVELURL . $meshVerticesFile;
  $meshFacesUrl = DEVELURL . $meshFacesFile;
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
        var camera = new THREE.PerspectiveCamera(60, window.innerWidth / window.innerHeight, 0.1, 300);
        camera.position.z = -1;

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
          renderer.render(scene, camera);
        }

        // Setup controls
        var controls = new THREE.TrackballControls(camera);
        controls.rotateSpeed = 1.0;
        controls.zoomSpeed = 10.2;
        controls.panSpeed = 0.8;
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
        var pointSize = 0.0001;
        var geometryCloud = new THREE.Geometry({dynamic:true});
        var geometryMesh = new THREE.Geometry({dynamic:true});
        var material = new THREE.PointCloudMaterial({size:pointSize, vertexColors:true});

        var pointcloudLoaded = false;
        var useMesh = false;
        var pcColors = [];
        var meshColors = [];
        var min_x = 0, min_y = 0, min_z = 0, max_x = 0, max_y = 0, max_z = 0, freq = 0;

        // Load the mesh vertices
        Papa.parse("<?php echo $meshVerticesUrl ?>", {
          download: true,
          worker: true,
          step: function(row) {
            var line = row.data[0];
            if (line.length != 3) return;

            // Vertices
            var x = parseFloat(line[0]);
            var y = parseFloat(line[1]);
            var z = parseFloat(line[2]);
            if(x>max_x) max_x = x;
            if(x<min_x) min_x = x;
            if(y>max_y) max_y = y;
            if(y<min_y) min_y = y;
            if(z>max_z) max_z = z;
            if(z<min_z) min_z = z;
            geometryMesh.vertices.push(new THREE.Vector3(x, y, z));
            
            // Color
            meshColors.push(new THREE.Color('rgb(255,255,255)'));
            geometryMesh.colors = meshColors;

          }
        });
        
        // Load the mesh faces
        Papa.parse("<?php echo $meshFacesUrl ?>", {
          download: true,
          worker: true,
          step: function(row) {
            var line = row.data[0];
            if (line.length != 3) return;

            // Faces
            geometryMesh.faces.push(new THREE.Face3(line[0], line[1], line[2]));

          }
        });
        
        // Load the pointcloud
        Papa.parse("<?php echo $pcUrl ?>", {
          download: true,
          worker: true,
          step: function(row) {
            var line = row.data[0];
            if (line.length != 6) return;

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

            // Color
            pcColors.push(new THREE.Color('rgb(' + line[3] + ',' + line[4] + ',' + line[5] + ')'));

            freq++;
            if (freq > 2000) {
              var per = Math.round((geometryCloud.vertices.length + geometryMesh.vertices.length) * 100 / (<?php echo $lineCountCloud ?> + <?php echo $lineCountMesh ?>));
              $("#progressbar").attr("aria-valuenow", per);
              $("#progressbar").css("width", per + "%");
              $("#progressbar").text(per + "%");
              freq = 0;
            }
          },
          complete: function() {
            console.log("Pointcloud with " + geometryCloud.vertices.length + " points loaded.");
            console.log("Mesh with " + geometryMesh.vertices.length + " vertices loaded.");

            // Build the scene
            geometryCloud.colors = pcColors;
            var pointcloud = new THREE.PointCloud(geometryCloud, material);
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
            useMesh = !useMesh;
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
            if (evt.keyCode == 189 || evt.keyCode == 109) {
              pointSize -= 0.001;
            }
            if (evt.keyCode == 187 || evt.keyCode == 107) {
              pointSize += 0.001;
            }
            if(pointSize < 0.0001) {
                pointSize = 0.0001;
            }
            if(pointSize > 0.01) {
                pointSize = 0.01;
            }

            if (evt.keyCode == 32) changeRepresentation();
            /*if (evt.keyCode == 49) changeColor('x');
            if (evt.keyCode == 50) changeColor('y');
            if (evt.keyCode == 51) changeColor('z');
            if (evt.keyCode == 52) changeColor('rgb');*/

            // Re-render the scene
            material = new THREE.PointCloudMaterial({ size: pointSize, opacity: 0.8, vertexColors: true });
            var meshMaterial = new THREE.MeshBasicMaterial({ color: 0xffffff, vertexcolors: 0xffffff, wireframe: true });
            var pointcloud = new THREE.PointCloud(geometryCloud, material);
            var mesh = new THREE.Mesh(geometryMesh, meshMaterial);
            scene = new THREE.Scene();
            scene.fog = new THREE.FogExp2(0x000000, 0.0009);
            if(useMesh) {
                scene.add(mesh);
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
          - space: change between point cloud/mesh representation<br />
          - +/-: change point size
        </p>
      </div>
      <div id="controls-iframe" style="position:absolute; top:5px; left:5px; z-index:999999; display:none;">
        <a style="font-size:11px;" href="http://www.robotics.jacobs-university.de/datasets/2016-watertight-meshes-v01/view/<?php echo $pcFolder ?>" target="_blank">view on robotics.jacobs-university.de</a>
      </div>

      <div id="progressbar-container" class="progress progress-striped" style="position:absolute; z-index:999999; width:400px; top:230px;">
        <div id="progressbar" class="progress-bar progress-bar-info" role="progressbar" aria-valuenow="1" aria-valuemin="0" aria-valuemax="100" style="width:1%">1%</div>
      </div>

    </div>

  </body>
</html>

