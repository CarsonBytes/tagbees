$(function(){
        $(".eventWatcherDetails").hide();
		$(".event_remember").hide();
        jQuery('.watcheDetail, .eventEdit').click(function(){
  		     //jQuery("#watcheDetail").addClass("selected").removeClass("nonselect");
  		     console.log('already triggered');
             console.log($(this));
             console.log($(this).prop('data-target'));
  		     return false;
             jQuery('#watcherDetailForm'+$(this).prop('data-target')).slideDown('show');
			 jQuery('#watcherDetailEdit'+$(this).prop('data-target')).slideUp('hide');			 
        });		
		jQuery('.eventSave, .eventCancel').click(function(){
             jQuery('#watcherDetailForm'+$(this).prop('data-target')).slideUp('hide');
			 jQuery('#watcherDetailEdit'+$(this).prop('data-target')).slideDown('show');
			 
        });
		jQuery('.watcherAlert').click(function(){
             jQuery('#mainEventWatcher'+$(this).prop('data-target')).slideUp('hide');
			 jQuery('#eventRemender'+$(this).prop('data-target')).slideDown('show');
			 
        });
		jQuery('.alertSave, .alertCancel').click(function(){
             jQuery('#mainEventWatcher'+$(this).prop('data-target')).slideDown('show');
			 jQuery('#eventRemender'+$(this).prop('data-target')).slideUp('hide');
        });

});