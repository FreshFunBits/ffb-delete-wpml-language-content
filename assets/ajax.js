/**
 *
 * @author   FreshFunBits
 * @since    0.1
 */

jQuery(document).ready(function ($) {           //wrapper
    $("#ffb_submit").click(function () {             //event

        $("#ffb_status").html
        ('<img src="' + ffb_delete_wpml_obj.ajax_loading_img + '" />'); // add the AJAX loading image
        ffb_send_ajax();

    });

    function ffb_send_ajax() {

        $.post(ajaxurl, {         //POST request

            ffb_nonce: $('#ffb_nonce').val(),     //nonce
            action: "ffb_delete_language",            //action
            items_per_batch: $('#items_per_batch').val(),
            deleted_lang: $('#deleted_lang').val()

        }, function (data) {                    //callback
            if (data.message) {
                ffb_prepend_message_content('<span style="color: green">' + data.message + '</span>');
            }

            if (data.err_message) {
                ffb_prepend_message_content('<span style="color: red">' + data.err_message + '</span>');
            }

            if (data.next_batch === true) {
                ffb_send_ajax();
            } else {
                $("#ffb_status").html(''); // remove the AJAX loading image
            }

        });

    }

    function ffb_prepend_message_content(text) {
        $(".ffb_message").prepend('<strong>' + text + '</strong><br />');
    }
});

