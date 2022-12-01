const filterDate = document.getElementById('filterDate');
const spanDate = document.getElementById('spanDate');
const b_search = document.getElementById('b_search');	 	 

filterDate.addEventListener('click', function handleClick() {
    if(filterDate.checked) {
        spanDate.style.display = 'inline-block';
        $('#status').val('check');
        $('#status2').val('');

    }else {
        spanDate.style.display = 'none';
        $('#status').val('uncheck');
        $('#status2').val('in the last 7 days');

    }
});

function checkDate(){
    var fromDate = $('#dateFrom').val();
    var toDate =   $('#dateTo').val();
    let date_2 = new Date(fromDate);
    let date_1 = new Date(toDate);

    const days = (date_1, date_2) =>{
        let difference = date_1.getTime() - date_2.getTime();
        let TotalDays = Math.ceil(difference / (1000 * 3600 * 24));
        return TotalDays;
    }

    if(days(date_1, date_2) > 20){
        alert('Please choose date between 20 days only');
        $( "#b_search" ).prop( "disabled", true );
    }else if(toDate < fromDate){
        alert('Please choose proper date range');
        $( "#b_search" ).prop( "disabled", true );
    } else{
        $( "#b_search" ).prop( "disabled", false );
      
    }

}

$('#search_number').on('keyup',function(){
    const searchnum = $('#search_number').val().length;
    if(searchnum > 4){
        $('#status2').val('');
    }else{
        if($('#status').val() == 'check'){
            $('#status2').val('');    
        }else{
            $('#status2').val('in the last 7 days');
        }
        
    }
});
