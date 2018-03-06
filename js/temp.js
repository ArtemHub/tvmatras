$(function () {
    $('[name=submitImportPrice]').on('click', function (e) {
        e.preventDefault();
        sendFile();
    })
    
    $('[data-action=close]').on('click', function () {
        location.reload();
    });

    $('[data-action=exportPrice]').on('click', function () {
        var token = $('[name=uploadFileToken]').val();
        var $obj = $(this);
        $obj.attr('disabled', 'disabled');
        $obj.attr('disabled', 'disabled').parent().addClass('loading');

        query({
            ajax: true,
            controller: 'AdminTvMatrasIndex',
            action: 'exportPrice',
            token: token
        }, function (response) {
            $('[data-action=downloadPrice]').attr('href', response.url);
            $('[data-action=downloadPrice]').removeAttr('disabled');
            $obj.attr('disabled', 'disabled').parent().removeClass('loading');
        });
    });

    $('[data-action=downloadPrice]').on('click', function () {
        $(this).attr('disabled', 'disabled');
        $('[data-action=exportPrice]').removeAttr('disabled');
        setTimeout(function() {
            $('[data-action=downloadPrice]').attr('href', '#');
        },200);
    });
});

function query(data, callback, noprocess) {
    $.ajax({
        url: 'ajax-tab.php',
        data: data,
        processData: (noprocess)? !noprocess : true,
        contentType: (noprocess) ? !noprocess : 'application/x-www-form-urlencoded; charset=UTF-8',
        type: 'POST',
        dataType: 'json',
        success: callback,
        error: function() {
            showErrorMessage('Error: Request failed');
        }
    });
}

function sendFile() {
    var $input = $('[name=uploadFilePrice]');
    var token = $('[name=uploadFileToken]').val();
    var fd = new FormData;

    if(!$input.prop('files')[0]) {
        return false;
    }

    fd.append('ajax', true);
    fd.append('controller', 'AdminTvMatrasIndex');
    fd.append('action', 'UploadFilePrice');
    fd.append('token', token);
    fd.append('price', $input.prop('files')[0]);

    query(fd, function (response) {
        if(!response.success) {
            showErrorMessage(response.error);
            return false;
        }

        if(!response.data || !response.data.steps || !response.data.total || !response.data.stepLimit || !response.data.file) {
            showErrorMessage('No, no this should not happen');
            return false;
        }

        disableForm();
        $input.val('');

        setTimeout(function (data) {
            importStep(data)
        }, 200, response.data);
    }, true);
}

function importStep(data) {
    console.log(data)

    var token = $('[name=uploadFileToken]').val();
    var percent = Math.round(100 / data.steps * data.step);
    setProgressBarPercent(percent);

    if(data.steps === data.step) {
        finishImport(data.file);
        return false;
    }

    removeErrorMessage();

    data.ajax = true;
    data.controller = 'AdminTvMatrasIndex';
    data.action = 'importPrice';
    data.step++;
    data.token = token;

    query(data, function (response) {
        if(!response.success) {
            showErrorMessage(response.error);
            return false;
        }

        if(!response.data || !response.data.steps || !response.data.total || !response.data.stepLimit || !response.data.file) {
            showErrorMessage('No, no this should not happen');
            return false;
        }

        updateLog(response.log);
        importStep(response.data)
    });
}

function updateLog(data) {
    var $total = $('[data-report=total]');
    var $fail = $('[data-report=fail]');
    var $pass = $('[data-report=pass]');
    var $skuList = $('[data-report=skuList]');

    $total.html(parseInt($total.html()) + parseInt(data.total));
    $fail.html(parseInt($fail.html()) + parseInt(data.fail));
    $pass.html(parseInt($pass.html()) + parseInt(data.pass));

    if(data.sku.length != 0) {
        if($skuList.find('.list-empty-msg').length != 0) {
            $skuList.html('');
        }

        data.sku.forEach(function (item) {
            $skuList.append('<div>' + item + '</div>')
        });
    }
}

function finishImport(file) {
    var data = {
        token: $('[name=uploadFileToken]').val(),
        ajax: true,
        controller: 'AdminTvMatrasIndex',
        action: 'clean',
        file: file
    };

    query(data, function (response) {
        if(!response.success) {
            showErrorMessage(response.error);
            return false;
        }
        $obj = $('[data-action=downloadLog]');
        $obj.attr('href', response.url);
        $obj.removeClass('hidden');
        $obj.next().removeClass('hidden');
    })
}

function setProgressBarPercent(percent) {
    var $container = $('#upload_file_progress-bar');
    var $obj = $container.find('.progress-bar');

    $obj.css('width', percent + '%');
    if(percent >= 100) {
        $obj.addClass('progress-bar-success');
    }
    if($container.hasClass('hidden')) {
        $container.removeClass('hidden');
    }
}

function disableForm() {
   $('#configuration_form').addClass('disable');
   $('#configuration_form').slideUp(250);
   setTimeout(function() {
       $('[data-report]').fadeIn(200);
   },100);
}

function enableForm() {
    $('#configuration_form').removeClass('disable');
}

function showErrorMessage(message) {
    var $obj = $('<article class="alert alert-danger" role="alert"><div>' + message + '</div></article>');
    $('#notifications').html($obj);
}

function removeErrorMessage() {
    $('#notifications').html('');
}