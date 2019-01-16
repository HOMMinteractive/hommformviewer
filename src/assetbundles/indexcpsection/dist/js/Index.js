/**
	* hommformviewer plugin for Craft CMS
	*
	* Index Field JS
	*
	* @author    Domenik Hofer
	* @copyright Copyright (c) 2019 Domenik Hofer
	* @link      http://www.homm.ch
	* @package   Hommformviewer
	* @since     1.0.0
*/

$(document).ready(function(){
	
	$('.hommformviewer_nav a').on('click', function(){
	
		$('.hommformviewer_loading').fadeIn();
		$('.hommformviewer_table').hide();
		$('.hommformviewer_nav a').removeClass('sel');
		$(this).addClass('sel');
		
		var tableName = $(this).data('table');
		
		$.ajax({
		url: '/actions/hommformviewer',
		data:{table:$(this).data('table')},
		success: function(result){
			drawTable(JSON.parse(result),tableName )
		},
		error: function(){
			
		}
		}) 
		
	})
	

	
})


function drawTable(data, tableName){
	
	var table = $(".hommformviewer_table");
	$(table).html('');
		
	
	var tableHead = '<tr>';
	
	$.each(data[0], function(index, el){
		tableHead += '<th>'+index+'</th>';
		
		})
	tableHead += '</tr>';	
	
	table.append(tableHead);
	
	
	$.each(data, function(index, el){
	var tableRow = '<tr>';
	
	$.each(el, function(index2, el2){
		tableRow += '<td>'+el2+'</td>';
	});	
		
		
		tableRow += '</tr>';
		table.append(tableRow);
		})
	
	$('.hommformviewer_loading').fadeOut();
	$('.hommformviewer_table').fadeIn();

	$('.hommformviewer_export').attr('href', 'actions/hommformviewer/default/download-file?table='+tableName);
	
	
}
