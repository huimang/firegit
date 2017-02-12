('hljs' in window) && hljs.initHighlightingOnLoad();

(function () {
    var modalElem = $('<div class="modal">' +
        '<div class="modal-content"><h4></h4><p></p></div>' +
        '<div class="modal-footer"><a class="modal-action modal-close btn-flat">关闭</a>' +
        '</div>' +
        '</div>');
    var onComplete;
    modalElem.appendTo(document.body);
    modalElem.modal({
        dismissible: true,
        opacity: .5,
        complete: function () {
            onComplete && onComplete();
        }
    });
    $('form[data-hook="ajax"]').submit(function (e) {
        var form = $(this);
        var url = form.attr('action');
        $.ajax({
            type: this.method,
            url: url,
            data: form.serialize(),
            dataType: 'json',
            success: function (ret) {
                onSuccess(form, ret);
            }
        });
        return false;
    });

    function onSuccess(elem, ret) {
        if (ret.status == 'ok') {
            var url = elem.data('rurl') || document.URL;
            if (url.indexOf('{') > -1) {
                url = url.replace(/\{([^\}]+)}/g, function (search, name) {
                    if (name in ret.data) {
                        return ret.data[name];
                    } else {
                        return search;
                    }
                });
            }
            onComplete = function () {
                location.href = url;
            };
            modalElem.find('.modal-content h4').html('<i class="fa fa-check"></i>' + (elem.data('success') || '操作成功'));
            modalElem.find('.modal-content p').html('');
            modalElem.modal('open');
            return;
        }

        onComplete = null;
        modalElem.find('.modal-content h4').html('<i class="fa fa-warning"></i>操作失败');
        modalElem.find('.modal-content p').html(
            (ret.desc ? '<h5>' + ret.desc + '</h5>' : '') +
            '状态：<code>' + ret.status +
            '<br/>错误码：<code>' + ret.msg + '</code>'
        );
        modalElem.modal('open');
    }

    $('a').on('click', function () {
        if (/\/\_[a-z][a-zA-Z\_\-0-9]+($|\/$|\/?\?)/.test(this.href)) {
            var link = $(this);
            var cfm = link.data('confirm');
            if (cfm) {
                if (!window.confirm(cfm)) {
                    return false;
                }
            }
            $.ajax({
                url: this.href,
                type: 'POST',
                dataType: 'json',
                success: function (ret) {
                    onSuccess(link, ret);
                }
            });
            return false;
        }
    });
})();
$('select').each(function () {
    var self = $(this);
    var url = self.data('url');
    if (url) {
        self.on('change', function () {
            if (this.value) {
                location.href = url.replace('{value}', this.value);
            }
        });
    }
    self.material_select();
});