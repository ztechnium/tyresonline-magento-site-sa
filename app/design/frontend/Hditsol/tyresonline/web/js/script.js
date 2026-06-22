define(["jquery"],function($){
    "use strict";
    $(document).ready(function(e){
        /* Start contact page google map code */
        if (document.getElementById('store_map') !=null) {
            var map;
            var myMarker;
            var myLatlng;
            myLatlng = new google.maps.LatLng(21.5298169, 39.1843447);		
            var myOptions = {
            zoom: 17,
            zoomControl: true,
            center: myLatlng,
            mapTypeId: google.maps.MapTypeId.ROADMAP
            };
            map = new google.maps.Map(document.getElementById("store_map"), myOptions);
            myMarker = new google.maps.Marker({
                map: map,
                position: myLatlng,
                draggable: false,
                animation:google.maps.Animation.BOUNCE,
                icon: 'https://www.tyresonline.sa/media/wysiwyg/map-marker-tyresonline.png',
            });
            myMarker.setMap(map);
            google.maps.event.trigger(map, "resize");
            map.setCenter(myLatlng);
        }
        /* End contact page google map code */
    })
});