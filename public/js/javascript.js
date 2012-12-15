jQuery(function($){
	
$('.accordion_content1').hide(); 
$('.accordion_button1:first').addClass('active').next().show();
 
//On Click
$('.accordion_button1').click(function(){
	if( $(this).next().is(':hidden') ) { //If immediate next container is closed...
		$('.accordion_button1').removeClass('active').next().slideUp(); //Remove all .acc_trigger classes and slide up the immediate next container
		$(this).toggleClass('active').next().slideDown(); //Add .acc_trigger class to clicked trigger and slide down the immediate next container
	}
	return false; //Prevent the browser jump to the link anchor
});

$('#next1').click( function(){
	$('#openDiv1').slideUp();
	$('#openDiv2').slideDown();
	$('.first').removeClass('active');
	$('.second').addClass("active");
});
$('#prev1').click( function(){
	$('#openDiv2').slideUp();
	$('#openDiv1').slideDown();
	$('.second').removeClass('active');
	$('.first').addClass("active");
});
$('#next2').click( function(){
	$('#openDiv2').slideUp();
	$('#openDiv3').slideDown();
	$('.second').removeClass('active');
	$('.third').addClass("active");

});
$('#prev2').click( function(){
	$('#openDiv3').slideUp();
	$('#openDiv2').slideDown();
	$('.third').removeClass('active');
	$('.second').addClass("active");
});
$('#next3').click( function(){
	$('#openDiv3').slideUp();
	$('#openDiv4').slideDown();
	$('.third').removeClass('active');
	$('.fourth').addClass("active");

});
$('#prev3').click( function(){
	$('#openDiv4').slideUp();
	$('#openDiv3').slideDown();
	$('.fourth').removeClass('active');
	$('.third').addClass("active");
});

});


jQuery(function($) {
	 
	//ACCORDION BUTTON ACTION (ON CLICK DO THE FOLLOWING)
	$('.accordion_button2').click(function() {

		//REMOVE THE ON CLASS FROM ALL BUTTONS
		$('.accordion_button2').removeClass('menuactive');
		  
		//NO MATTER WHAT WE CLOSE ALL OPEN SLIDES
	 	$('.accordion_content2').slideUp('normal');
		//IF THE NEXT SLIDE WASN'T OPEN THEN OPEN IT
		if($(this).next().is(':hidden') == true) {
			
			
			//ADD THE ON CLASS TO THE BUTTON
			$(this).addClass('menuactive');
			  
			//OPEN THE SLIDE
			$(this).next().slideDown('normal');
		 } 
		  
	 });
	  
	
	/*** REMOVE IF MOUSEOVER IS NOT REQUIRED ***/
	
	//ADDS THE .OVER CLASS FROM THE STYLESHEET ON MOUSEOVER 
	$('.accordion_button2').mouseover(function() {
		$(this).addClass('menuover');
		
	//ON MOUSEOUT REMOVE THE OVER CLASS
	}).mouseout(function() {
		$(this).removeClass('menuover');										
	});
	
	/*** END REMOVE IF MOUSEOVER IS NOT REQUIRED ***/
	
	
	/********************************************************************************************************************
	CLOSES ALL S ON PAGE LOAD
	********************************************************************************************************************/	
	$('.accordion_content2').hide();

});