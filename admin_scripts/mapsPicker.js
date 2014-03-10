

function setupMap (parent, filed_id_interfix) {
    var mapspicker_marker       = null;
    var mapspicker_latLgn       = null;
    var mapspicker_map          = null;
    var mapspicker_geocoder     = null;
    var mapspicker_postal_code  = "";
    var mapspicker_street       = "";
    var mapspicker_number       = "";
    var mapspicker_city         = "";
    var mapspicker_state        = "";
    var mapspicker_country      = "";

    function contains(arr, obj){
        if(arr) for(var i = 0; i < arr.length; i++) {
            if(arr[i] === obj)  return true;
        }

        return false;
    }

    function updateMarker(marker, latLng){
        if(mapspicker_geocoder){
            mapspicker_geocoder.geocode({'latLng': latLng}, function(results, status) {
                if (status == google.maps.GeocoderStatus.OK) {
                    if (results[0]) {
                        var address_components = results[0].address_components;
                        for (var i = 0; i < address_components.length; i++){
                            var address_component = address_components[i];

                            if(contains(address_component.types, "postal_code"))                    mapspicker_postal_code  = address_component.long_name;
                            if(contains(address_component.types, "street_number"))                  mapspicker_number       = address_component.long_name;
                            if(contains(address_component.types, "route"))                          mapspicker_street       = address_component.long_name;
                            if(contains(address_component.types, "locality"))                       mapspicker_city         = address_component.long_name;
                            if(contains(address_component.types, "administrative_area_level_1"))    mapspicker_state        = address_component.long_name;
                            if(contains(address_component.types, "country"))                        mapspicker_country      = address_component.long_name;
                        }

                        parent.find(".map_canvas_data").html(results[0].formatted_address);

                        jQuery(" <a class='map_canvas_data_use'> use this address</a> ").appendTo(parent.find(".map_canvas_data")).click(function() {
                            parent.find("#wpc_"+filed_id_interfix+"field_street").val(mapspicker_street).trigger('change');
                            parent.find("#wpc_"+filed_id_interfix+"field_number").val(mapspicker_number).trigger('change');
                            parent.find("#wpc_"+filed_id_interfix+"field_postal_code").val(mapspicker_postal_code).trigger('change');
                            parent.find("#wpc_"+filed_id_interfix+"field_city").val(mapspicker_city).trigger('change');
                            parent.find("#wpc_"+filed_id_interfix+"field_state").val(mapspicker_state).trigger('change');
                            parent.find("#wpc_"+filed_id_interfix+"field_country").val(mapspicker_country).trigger('change');

                            parent.find("#wpc_"+filed_id_interfix+"field_latitude").val(mapspicker_latLgn.lat()).trigger('change');
                            parent.find("#wpc_"+filed_id_interfix+"field_longitude").val(mapspicker_latLgn.lng()).trigger('change');
                        });
                    } else {
                        parent.find(".map_canvas_data").html("No results found");
                    }
                } else {
                    parent.find(".map_canvas_data").html("Geocoder failed due to: " + status);
                }
            });
        }
    }

    function setMarker(map, latLng){
        if(mapspicker_marker){
            google.maps.event.addListener(mapspicker_marker);
            mapspicker_marker.setMap(null);
        }

        mapspicker_latLgn = latLng;

        mapspicker_marker = new google.maps.Marker({
            position:latLng,
            map:map,
            animation: google.maps.Animation.DROP
        });
        mapspicker_marker.setDraggable(true);

        google.maps.event.addListener(mapspicker_marker, 'dragend', function(event) {
            updateMarker(this, event.latLng);
        });

        updateMarker(mapspicker_marker, latLng);
    }

    if ( filed_id_interfix === undefined )
        filed_id_interfix = "";
    else filed_id_interfix += "_";

    parent.append(
'        <div class="wpc_form_row">'+

'            <div class="div-place-search_for_location">'+
'                <input class="wpc_input_text search_for_location"  type="text" value="" placeholder="search for location" />'+

'                <ul class="search_for_location_results"></ul>'+
'            </div>'+

'            <div class="map_canvas"></div>'+
'            <div class="map_canvas_data"></div>'+
'        </div>');

    var lat = jQuery("#wpc_"+filed_id_interfix+"field_latitude").val();
    var lng = jQuery("#wpc_"+filed_id_interfix+"field_longitude").val();

    if(isNaN(parseFloat(lat)) || lat === 0)  lat = 52.51456249417806;
    if(isNaN(parseFloat(lng)) || lng === 0)  lng = 13.350002031326355;

    var latLng = new google.maps.LatLng(lat, lng);

    var myOptions = {
        zoom: 12,
        center: latLng,
        disableDefaultUI: false,
        streetViewControl: false,
        disableDoubleClickZoom: true,
        navigationControl: true,
        scrollwheel: false,
        mapTypeControl: true,
        navigationControlOptions: {style: google.maps.NavigationControlStyle.SMALL},
        mapTypeId: google.maps.MapTypeId.TERRAIN
    };

    mapspicker_geocoder = new google.maps.Geocoder();
    mapspicker_map      = new google.maps.Map(parent.find(".map_canvas")[0], myOptions);

    setMarker(mapspicker_map, latLng);

    google.maps.event.addListener(mapspicker_map, 'dblclick', function(event) {
        setMarker(this, event.latLng);
    });


    parent.find('.search_for_location_results').empty();

    var geocoder            = null;
    var geocoder_query      = "";
    var geocoder_lastquery  = "";
    var geocoder_fired      = false;

    setupReverseGeocoder();

    function setupReverseGeocoder() {
        geocoder = new google.maps.Geocoder();

        parent.find('.search_for_location').bind('change keydown keyup', function(event) {
            if(event.type == 'keydown' && event.keyCode == 13)
                event.preventDefault();

            geocoder_query = jQuery(this).val();

            if(!geocoder_fired && (event.type == 'change' || event.type == 'keyup')) {
                geocoder_fired = true;
                window.setTimeout(sendGeocoderQuery, 500);
            }
        });

        parent.find('.div-place-search_for_location').delegate('a.select_geocoder_result', 'click', function(event) {
            event.preventDefault();

            var sourceElement   = jQuery(this);
            var address         = sourceElement.parent().data("address");

            mapspicker_map.setCenter(address.geometry.location);
            mapspicker_map.setZoom(16);

            setMarker(mapspicker_map, address.geometry.location);
        });
    }

    function sendGeocoderQuery() {
        geocoder_fired = false;

        if(geocoder_lastquery != geocoder_query){
            geocoder_lastquery = geocoder_query;

            if(geocoder_query !== "" && geocoder_query.length >= 3){
                geocoder.geocode( { 'address': geocoder_query, 'language':'de'}, function(results, status) {
                    var jQ_results = parent.find('.search_for_location_results');

                    jQ_results.empty();

                    if (status == google.maps.GeocoderStatus.OK) {
                        for (var i = 0; i < results.length; i++){
                            jQuery('<li>'+
                                    '<a href="#" class="select_geocoder_result" title="'+results[i]+'">'+results[i].formatted_address+'</a>'+
                                '</li>').appendTo(jQ_results).data("address", results[i]);

                            if(i%2 === 0)
                                jQ_results.find().last().addClass("odd");
                        }
                    } else {
                        parent.find('.search_for_location_results').append("<li><i>Geocode was not successful for the following reason: " + status + "</i></li>");
                    }
                });
            } else {
                parent.find('.search_for_location_results').empty();
            }
        }
    }
}

