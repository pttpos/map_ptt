<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bing Maps with OpenStreetMap</title>
    <script type='text/javascript' src='https://www.bing.com/api/maps/mapcontrol?key=AhQxc3Nm4Sfv53x7JRXUoj76QZnlm7VWkT5qAigmHQo8gjeYFthvGgEqVcjO5c7C' async defer></script>
    <style>
        #map {
            height: 100vh;
            width: 100%;
        }
    </style>
</head>
<body>
    <div id="map"></div>

    <script>
        function loadMapScenario() {
            var map = new Microsoft.Maps.Map(document.getElementById('map'), {
                credentials: 'AhQxc3Nm4Sfv53x7JRXUoj76QZnlm7VWkT5qAigmHQo8gjeYFthvGgEqVcjO5c7C'
            });

            // Prompt the user for location permission
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    var loc = new Microsoft.Maps.Location(position.coords.latitude, position.coords.longitude);
                    map.setView({ center: loc, zoom: 10 });
                }, function(error) {
                    console.error('Error getting the user location:', error);
                });
            } else {
                console.error('Geolocation is not supported by this browser.');
            }

            // Create a tile layer using OpenStreetMap tiles
            var openStreetMapRoad = new Microsoft.Maps.TileLayer({
                mercator: new Microsoft.Maps.TileSource({
                    uriConstructor: 'https://tile.openstreetmap.org/{quadkey}.png',
                    bounds: Microsoft.Maps.LocationRect.fromEdges(85.05112878, -180, -85.05112878, 180),
                    minZoom: 0,
                    maxZoom: 19
                })
            });

            // Add the OpenStreetMap tile layer to the map
            map.layers.insert(openStreetMapRoad);
        }
        window.onload = loadMapScenario;
    </script>
</body>
</html>
