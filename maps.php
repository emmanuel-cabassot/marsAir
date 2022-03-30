<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8" />
    <title>Display HTML clusters with custom properties</title>
    <meta
      name="viewport"
      content="initial-scale=1,maximum-scale=1,user-scalable=no"
    />
    <link
      href="https://api.mapbox.com/mapbox-gl-js/v2.7.0/mapbox-gl.css"
      rel="stylesheet"
      
    />
    <link rel="stylesheet" href="./assets/css/styles.css">
    <script src="https://api.mapbox.com/mapbox-gl-js/v2.7.0/mapbox-gl.js"></script>
    <style>
      body {
        margin: 0;
        padding: 0;
      }
      #map {
        position: absolute;
        top: 50px;
        bottom: 50px;
        width: 80%;
      }
    </style>
  </head>
  <body>
    <div id="map"></div>

    <script>
      mapboxgl.accessToken =
      'pk.eyJ1IjoiZmxvcmVudDA4IiwiYSI6ImNsMWI1a3VxYzAwbGUzbHMxanJuMnBqb28ifQ.5Le4XJZOwGKvOiZuFgG4vQ';
      const map = new mapboxgl.Map({
        container: "map",
        zoom: 12,
        center: [5.350537, 43.33],
        style: "mapbox://styles/mapbox/streets-v10",
      });

      map.addControl(new mapboxgl.NavigationControl());

      // filters for classifying earthquakes into 3 categories based on pollution
      const mag1 = ["<", ["get", "mag"], 12];
      const mag2 = [
        "all",
        [">=", ["get", "mag"], 13],
        ["<", ["get", "mag"], 35],
      ];
      const mag3 = [
        "all",
        [">=", ["get", "mag"], 35],
        ["<", ["get", "mag"], 55],
      ];
      const mag4 = [
        "all",
        [">=", ["get", "mag"], 55],
        ["<", ["get", "mag"], 150],
      ];
      const mag5 = [">=", ["get", "mag"], 150];

      // colors to use for the categories
      const colors = ["green", "yellow", "orange", "red", "black", "blue"];

      map.on("load", () => {
        // add a clustered GeoJSON source for a sample set of earthquakes
        map.addSource("earthquakes", {
          type: "geojson",
          data: "./API/fakeData.json",
          cluster: true,
          clusterRadius: 13,
          clusterProperties: {
            // keep separate counts for each magnitude category in a cluster
            mag1: ["+", ["case", mag1, 1, 0]],
            mag2: ["+", ["case", mag2, 1, 0]],
            mag3: ["+", ["case", mag3, 1, 0]],
            mag4: ["+", ["case", mag4, 1, 0]],
            mag5: ["+", ["case", mag5, 1, 0]],
          },
        });
        // circle and symbol layers for rendering individual earthquakes (unclustered points)
        map.addLayer({
          id: "earthquake_circle",
          type: "circle",
          source: "earthquakes",
          filter: ["!=", "cluster", true],
          paint: {
            "circle-color": [
              "case",
              mag1,
              colors[0],
              mag2,
              colors[1],
              mag3,
              colors[2],
              mag4,
              colors[3],
              mag5,
              colors[4],
              colors[5],
            ],
            "circle-opacity": 1,
            "circle-radius": {
              base: 1.75,
              stops: [
                [12, 18],
                [22, 120],
              ],
            },
          },
        });
        map.addLayer({
          id: "earthquake_label",
          type: "symbol",
          source: "earthquakes",
          filter: ["!=", "cluster", true],
          layout: {
            "text-field": [
              "number-format",
              ["get", "mag"],
              { "min-fraction-digits": 0, "max-fraction-digits": 0 },
            ],
            "text-font": ["Open Sans Semibold"],
            "text-size": {
              base: 1.75,
              stops: [
                [12, 15],
                [22, 120],
              ],
            },
          },
          paint: {
            "text-color": [
              "case",
              ["<", ["get", "mag"], 149],
              "black",
              "white",
            ],
          },
        });

        // objects for caching and keeping track of HTML marker objects (for performance)
        const markers = {};
        let markersOnScreen = {};

        function updateMarkers() {
          const newMarkers = {};
          const features = map.querySourceFeatures("earthquakes");

          // for every cluster on the screen, create an HTML marker for it (if we didn't yet),
          // and add it to the map if it's not there already
          for (const feature of features) {
            const coords = feature.geometry.coordinates;
            const props = feature.properties;
            if (!props.cluster) continue;
            const id = props.cluster_id;

            let marker = markers[id];
            if (!marker) {
              const el = createDonutChart(props);
              marker = markers[id] = new mapboxgl.Marker({
                element: el,
              }).setLngLat(coords);
            }
            newMarkers[id] = marker;

            if (!markersOnScreen[id]) marker.addTo(map);
          }
          // for every marker we've added previously, remove those that are no longer visible
          for (const id in markersOnScreen) {
            if (!newMarkers[id]) markersOnScreen[id].remove();
          }
          markersOnScreen = newMarkers;
        }

        // after the GeoJSON data is loaded, update markers on the screen on every frame
        map.on("render", () => {
          if (!map.isSourceLoaded("earthquakes")) return;
          updateMarkers();
        });
      });

      // code for creating an SVG donut chart from feature properties
      function createDonutChart(props) {
        const offsets = [];
        const counts = [props.mag1, props.mag2, props.mag3, props.mag4, props.mag5];
        let total = 0;
        for (const count of counts) {
          offsets.push(total);
          total += count;
        }
        const fontSize =
          total >= 1000 ? 22 : total >= 100 ? 20 : total >= 10 ? 18 : 16;
        const r =
          total >= 1000 ? 50 : total >= 100 ? 32 : total >= 10 ? 24 : 18;
        const r0 = Math.round(r * 0.6);
        const w = r * 2;

        let html = `<div>
<svg width="${w}" height="${w}" viewbox="0 0 ${w} ${w}" text-anchor="middle" style="font: ${fontSize}px sans-serif; display: block">`;

        for (let i = 0; i < counts.length; i++) {
          html += donutSegment(
            offsets[i] / total,
            (offsets[i] + counts[i]) / total,
            r,
            r0,
            colors[i]
          );
        }
        html += `<circle cx="${r}" cy="${r}" r="${r0}" fill="white" />
<text dominant-baseline="central" transform="translate(${r}, ${r})">
${total.toLocaleString()}
</text>
</svg>
</div>`;

        const el = document.createElement("div");
        el.innerHTML = html;
        return el.firstChild;
      }

      function donutSegment(start, end, r, r0, color) {
        if (end - start === 1) end -= 0.00001;
        const a0 = 2 * Math.PI * (start - 0.25);
        const a1 = 2 * Math.PI * (end - 0.25);
        const x0 = Math.cos(a0),
          y0 = Math.sin(a0);
        const x1 = Math.cos(a1),
          y1 = Math.sin(a1);
        const largeArc = end - start > 0.5 ? 1 : 0;

        // draw an SVG path
        return `<path d="M ${r + r0 * x0} ${r + r0 * y0} L ${r + r * x0} ${
          r + r * y0
        } A ${r} ${r} 0 ${largeArc} 1 ${r + r * x1} ${r + r * y1} L ${
          r + r0 * x1
        } ${r + r0 * y1} A ${r0} ${r0} 0 ${largeArc} 0 ${r + r0 * x0} ${
          r + r0 * y0
        }" fill="${color}" />`;
      }

      // When a click event occurs on a feature in the places layer, open a popup at the
      // location of the feature, with description HTML from its properties.
      map.on("click", "earthquake_label", (e) => {
        
        // Copy coordinates array.
        const alert1 = "<p><span>BONNE 0-12</span> : La qualité de l’air est satisfaisante et la pollution a peu ou pas d’incidence sur la santé.</p>"
        const alert2 = "<p><span>MOYENNE 12 – 35</span> : La qualité de l’air est acceptable, les personnes sensibles peuvent ressentir des effets.</p>"
        const alert3 = "<p><span>DEGRADEE 35 – 55</span> : La qualité de l’air est dégradée, les personnes sensibles peuvent ressentir des effets ainsi que les personnes âgées et les enfants.</p>"
        const alert4 ="<p><span>MAUVAISE 55 – 150</span> : Toute personne peut ressentir des effets et les personnes sensibles sont à risque.</p>"
        const alert5 = "<p><span>EXTREMEMENT MAUVAIS  > 150</span> : Toute la population est concernée et ressent des effets néfastes pour la santé.</p>"
        const coordinates = e.features[0].geometry.coordinates.slice();
        let description = e.features[0].properties.localisation;
        const pollution = e.features[0].properties.mag;
        let alerte = null
        if (pollution < 13) {
            alerte = alert1
            description = '<div class="bg-popup-green">"<strong>' +description+alerte+ '</strong></div>'
        }
        if (pollution > 12  && pollution < 35) {
            alerte = alert2
            total = '<div class="bg-popup-yellow"><strong>' +description+alerte+ '</strong></div>'
        }
        if (pollution > 34  && pollution < 55) {
            alerte = alert3
            total = '<div class="bg-popup-orange"><strong>' +description+alerte+ '</strong></div>'
        }
        if (pollution > 54  && pollution < 150) {
            alerte = alert4
            total = '<div class="bg-popup-red"><strong>' +description+alerte+ '</strong></div>'
        }
        if (pollution > 149  ) {
            alerte = alert5
            total = '<div class="bg-popup-black"><strong>' +description+alerte+ '</strong></div>'
        }
        /* description = '<strong>' +description+ '</strong>'
        const total = description + alerte */

        // Ensure that if the map is zoomed out such that multiple
        // copies of the feature are visible, the popup appears
        // over the copy being pointed to.
        while (Math.abs(e.lngLat.lng - coordinates[0]) > 180) {
          coordinates[0] += e.lngLat.lng > coordinates[0] ? 360 : -360;
        }

        new mapboxgl.Popup()
          .setLngLat(coordinates)
          .setHTML(total)
          .addTo(map);
      });

      // Change the cursor to a pointer when the mouse is over the places layer.
      map.on("mouseenter", "earthquake_label", () => {
        map.getCanvas().style.cursor = "pointer";
      });

      // Change it back to a pointer when it leaves.
      map.on("mouseleave", "earthquake_label", () => {
        map.getCanvas().style.cursor = "";
      });
    </script>
  </body>
</html>

