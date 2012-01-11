    var mapspicker_marker   = null;
    var mapspicker_latLgn   = null;
    var mapspicker_map      = null;
    var mapspicker_geocoder = null;
    
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
                        
                        jQuery("#map_canvas_data").html(results[0].formatted_address);
                        jQuery("#map_canvas_data").append(" <a id='map_canvas_data_use'>use this address</a>");
                        jQuery("#map_canvas_data_use").click(function() {
                            jQuery("#wpc_field_street").val(mapspicker_street).trigger('change');
                            jQuery("#wpc_field_number").val(mapspicker_number).trigger('change');
                            jQuery("#wpc_field_postal_code").val(mapspicker_postal_code).trigger('change');
                            jQuery("#wpc_field_city").val(mapspicker_city).trigger('change');
                            jQuery("#wpc_field_state").val(mapspicker_state).trigger('change');
                            jQuery("#wpc_field_country").val(mapspicker_country).trigger('change');

                            jQuery("#wpc_field_latitude").val(mapspicker_latLgn.lat()).trigger('change');
                            jQuery("#wpc_field_longitude").val(mapspicker_latLgn.lng()).trigger('change');
                        });
                    } else {
                        jQuery("#map_canvas_data").html("No results found");
                    }
                } else {
                    jQuery("#map_canvas_data").html("Geocoder failed due to: " + status);
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
    
    jQuery(function($) {
        if(!document.getElementById("map_canvas"))
            return ;
        
        var lat = jQuery("#wpc_latitude").val();
        var lng = jQuery("#wpc_longitude").val();

        if(isNaN(parseFloat(lat)) || lat == 0)  lat = 52.51456249417806;
        if(isNaN(parseFloat(lng)) || lng == 0)  lng = 13.350002031326355;
        
        var latLng = new google.maps.LatLng(lat, lng);
        
        var myOptions = {
            zoom: 12,
            center: latLng,
            disableDefaultUI: true,
            disableDoubleClickZoom: true,
            navigationControl: true,
            mapTypeControl: true,
            navigationControlOptions: {style: google.maps.NavigationControlStyle.SMALL},
            mapTypeId: google.maps.MapTypeId.TERRAIN
        }
        
        mapspicker_geocoder = new google.maps.Geocoder();
        mapspicker_map      = new google.maps.Map(document.getElementById("map_canvas"), myOptions);
        
        setMarker(mapspicker_map, latLng);
        
        google.maps.event.addListener(mapspicker_map, 'dblclick', function(event) {
            setMarker(this, event.latLng);
        });

        setupReverseGeocoder();


        jQuery('#search-for-location-results').empty().hide();
    });
    

    var geocoder            = null;
    var geocoder_query      = "";
    var geocoder_lastquery  = "";
    var geocoder_fired      = false;

    
    function setupReverseGeocoder() {
        geocoder = new google.maps.Geocoder();
        
        jQuery('#search-for-location').bind('change keydown keyup', function(event) {
            if(event.type == 'keydown' && event.keyCode == 13)
                event.preventDefault();
            
            geocoder_query = jQuery(this).val();
            
            if(!geocoder_fired && (event.type == 'change' || event.type == 'keyup')) {
                geocoder_fired = true;
                window.setTimeout('sendGeocoderQuery()', 500);
            }
        });

        jQuery('#div-place-search-for-location').delegate('a.select_geocoder_result', 'click', function(event) {
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
            
            if(geocoder_query != "" && geocoder_query.length >= 3){
                geocoder.geocode( { 'address': geocoder_query}, function(results, status) {
                    var jQ_results = jQuery('#search-for-location-results');
                    
                    jQ_results.empty().show();
                    
                    if (status == google.maps.GeocoderStatus.OK) {
                        for (var i = 0; i < results.length; i++){
                            jQ_results.append(
                                '<li>'+
                                    '<a href="#" class="select_geocoder_result" title="'+results[i]+'">'+results[i].formatted_address+'</a>'+
                                '</li>'
                            );

                            jQ_results.children().last().data("address", results[i]);
                            
                            if(i%2 == 0)
                                jQ_results.children().last().addClass("odd");
                        }
                    } else {
                        jQuery('#search-for-location-results').append("<li><i>Geocode was not successful for the following reason: " + status + "</i></li>");
                    }
                });
            } else {
                jQuery('#search-for-location-results').empty().hide();
            }
        }
    }