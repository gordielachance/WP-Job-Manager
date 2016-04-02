jQuery(document).ready(function($) {

        //Clone Job partners box
        var job_partners_box = $('#job_listing_partnerdiv');
        var job_partners_all = job_partners_box.find('#job_listing_partner-all');
        
        
        var job_client_box = $('#job_client');
        var job_client_box_inside = job_client_box.find('.inside');
        var job_client_id = job_client_box_inside.find('input').val();

        var job_client_new_box = job_partners_all.clone();
        var job_client_new_box_checkboxes = job_client_new_box.find('input[type="checkbox"]');
        
        job_client_new_box_checkboxes.each(function( index ) {
            var checkbox = $(this);
            var id_attr = checkbox.attr('id');
            var id_split = id_attr.split("in-job_listing_partner-");
            var partner_id = id_split[1];
            
            checkbox.attr( "name", 'job_listing_client' );
            checkbox.attr( "id", id_attr.replace("in-job_listing_partner-", "in-job_listing_client-") );

            if (partner_id == job_client_id){
                checkbox.prop( "checked", true );
            }else{
                checkbox.prop( "checked", false );
            }
            
            checkbox.attr('type','radio');
        });
        
        
        job_client_box_inside.empty();
        job_client_box_inside.append(job_client_new_box);

});
