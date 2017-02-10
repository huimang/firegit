!function () {
    $('#oldpwd').on('invalid', function () {
        if (this.validity.valueMissing) {
            this.setCustomValidity('旧密码不能为空');
        } else {
            this.setCustomValidity('');
        }
    });
    $('#newpwd').on('invalid', function () {
        if (this.validity.patternMismatch) {
            this.setCustomValidity('密码为6到12位的字母');
        } else if (this.validity.valueMissing) {
            this.setCustomValidity('密码不能为空');
        } else {
            this.setCustomValidity('');
        }
    });
    $('#repwd').on('blur', isSame);

    $('button[type=submit]').on('click', isSame);

    function isSame() {
        var elem = $('#repwd')[0];
        if (elem.value != $('#newpwd').val()) {
            elem.setCustomValidity('两次输入的密码不一样');
        } else {
            elem.setCustomValidity('');
        }
    }
}();