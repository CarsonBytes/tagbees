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