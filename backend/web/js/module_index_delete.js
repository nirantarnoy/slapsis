// const Toast = Swal.mixin({
//     toast: true,
//     position: 'top-center',
//     showConfirmButton: false,
//     timer: 3000
// });
//
// $("#btn-show-alert").click(function(){
//
//     var msg = $(".alert-msg").val();
//     var msg_error = $(".alert-msg-error").val();
//     // alert(msg);
//     if(msg!='' && typeof(msg) !== "undefined"){
//         Toast.fire({
//             type: 'success',
//             title: msg
//         })
//     }
//     if(msg_error!='' && typeof(msg_error) !== "undefined"){
//         Toast.fire({
//             type: 'error',
//             title: msg_error
//         })
//     }
//
// })
function showAlert(show_type,msg){
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-center',
        showConfirmButton: false,
        timer: 3000
    });

    Toast.fire({
        type: show_type,
        title: msg
    })
}
function recDelete(e){
    //e.preventDefault();
    var url = e.attr("data-url");
    var id = e.attr("data-var");
    //alert(url);
    swal({
        title: "ต้องการลบรายการนี้ใช่หรือไม่",
        text: "",
        type: "error",
        showCancelButton: true,
        closeOnConfirm: true,
        showLoaderOnConfirm: true
    }, function () {
       // alert($("form#form-delete").attr('action'));

        $("form#form-delete").attr('action',url);
        $("form#form-delete").submit();
        // $.ajax({
        //     'type': 'get',
        //     'dataType': 'html',
        //     'url': url,
        //     'data': {'id': id},
        //     'success': function(data){
        //         if(data ==1){
        //             location.reload();
        //            // alert(data);
        //            // showAlert('success','ทำรายการสำเร็จ');//
        //            // $("#btn-show-alert").trigger('click');
        //         }else{
        //            // alert(data);
        //           //  showAlert('error','พบข้อผิดพลาด');
        //         }
        //         // setTimeout(function(){
       //     location.reload();
        //         // },3000);
        //
        //        // return;
        //     },
        //     'error': function(data){
        //          alert(data);//return;
        //     }
        // });
    });
}

function workqueConfirm(){
    //e.preventDefault();
    // var url = e.attr("data-url");
    // var id = e.attr("data-var");
    //alert(url);
    swal({
        title: "ต้องการยืนยันรายการนี้ใช่หรือไม่",
        text: "",
        type: "success",
        showCancelButton: true,
        closeOnConfirm: true,
        showLoaderOnConfirm: true
    }, function () {
        // alert($("form#form-delete").attr('action'));

      //  $("form#form-confirm").attr('action',url);
        $("form#form-confirm").submit();
        // $.ajax({
        //     'type': 'get',
        //     'dataType': 'html',
        //     'url': url,
        //     'data': {'id': id},
        //     'success': function(data){
        //         if(data ==1){
        //             location.reload();
        //            // alert(data);
        //            // showAlert('success','ทำรายการสำเร็จ');//
        //            // $("#btn-show-alert").trigger('click');
        //         }else{
        //            // alert(data);
        //           //  showAlert('error','พบข้อผิดพลาด');
        //         }
        //         // setTimeout(function(){
        //     location.reload();
        //         // },3000);
        //
        //        // return;
        //     },
        //     'error': function(data){
        //          alert(data);//return;
        //     }
        // });
    });
}

