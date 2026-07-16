function getbettingdata(){
  let periodid=$("#curr-period").text();
  $.ajax({
    type:"get",
    data:{round_no:periodid},
    url:"/admin/wingo/bet/details",
    success:function(res){ $("#betdetail").html(res.html); }
  });
}

function wingoLiveBet(){
  let periodid=$("#curr-period").text();
  $.ajax({
    type:"get",
    data:{round_no:periodid},
    url:"/admin/wingo/live/bet/details",
    success:function(res){ 
      $("#live_bet").html(res.html); 
      $('#tobet').text(res.bet_total);
    }
  });
}

function sub(number = 'not_set'){
  let periodid=$("#curr-period").text();

  $.ajax({
    type:"POST",
    data:{round_no:periodid, number:number},
    url:"/admin/wingo/prediction",
    success:function(res){ 
        let displayNumber = (res.number === null || res.number === undefined) ? 'Not Set' : res.number;
    
        $('#prediction').empty();
    
        if(displayNumber === 'Not Set'){
            $('#prediction').text(displayNumber);
        } else {
            let span = $('<span>').text(displayNumber).attr('class','wingo-prediction-number');
            let btn = $('<button>')
                        .text('Remove')
                        .attr('class','wingo-prediction-remove')
                        .css({
                            'background-color': '#f44336',
                            'color': '#fff',
                            'border': 'none',
                            'padding': '2px 6px',
                            'font-size': '12px',
                            'border-radius': '4px',
                            'cursor': 'pointer',
                            'margin-left': '5px',
                            'vertical-align': 'middle'
                        });
    
            $('#prediction').append(span, btn);
        }
    }
  });
}

$(document).on('click', '.wingo-prediction-remove', function(){
  sub(null);
})

$(document).on('click', '.circle', function(){
  const number = $(this).data('number');
  sub(number);
})