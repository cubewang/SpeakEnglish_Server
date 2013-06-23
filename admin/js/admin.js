/**
 * Created with JetBrains PhpStorm.
 * User: YuWei
 * Date: 12-9-13
 * Time: 下午12:18
 * To change this template use File | Settings | File Templates.
 */
function previewImage(file) {
    var MAXWIDTH = 100;
    var MAXHEIGHT = 100;
    var div = document.getElementById('preview');
    if (file.files && file.files[0]) {
        div.innerHTML = '<img id=imghead>';
        var img = document.getElementById('imghead');
        img.onload = function () {
            var rect = clacImgZoomParam(MAXWIDTH, MAXHEIGHT, img.offsetWidth, img.offsetHeight);
            img.width = rect.width;
            img.height = rect.height;
            img.style.marginLeft = rect.left + 'px';
            img.style.marginTop = rect.top + 'px';
        }
        var reader = new FileReader();
        reader.onload = function (evt) {
            img.src = evt.target.result;
        }
        reader.readAsDataURL(file.files[0]);
    }
    else {
        reSetImgView(div);
    }
}

function previewImage2(file) {
    var MAXWIDTH = 100;
    var MAXHEIGHT = 100;
    var div = document.getElementById('preview2');
    if (file.files && file.files[0]) {
        div.innerHTML = '<img id=imghead2>';
        var img = document.getElementById('imghead2');
        img.onload = function () {
            var rect = clacImgZoomParam(MAXWIDTH, MAXHEIGHT, img.offsetWidth, img.offsetHeight);
            img.width = rect.width;
            img.height = rect.height;
            img.style.marginLeft = rect.left + 'px';
            img.style.marginTop = rect.top + 'px';
        }
        var reader = new FileReader();
        reader.onload = function (evt) {
            img.src = evt.target.result;
        }
        reader.readAsDataURL(file.files[0]);
    }
    else {
        reSetImgView(div);
    }
}

/**
 * 文件预览图设置
 * @param maxWidth
 * @param maxHeight
 * @param width
 * @param height
 * @return {Object}
 */
function clacImgZoomParam(maxWidth, maxHeight, width, height) {
    var param = {top:0, left:0, width:width, height:height};
    if (width > maxWidth || height > maxHeight) {
        rateWidth = width / maxWidth;
        rateHeight = height / maxHeight;

        if (rateWidth > rateHeight) {
            param.width = maxWidth;
            param.height = Math.round(height / rateWidth);
        } else {
            param.width = Math.round(width / rateHeight);
            param.height = maxHeight;
        }
    }

    param.left = Math.round((maxWidth - param.width) / 2);
    param.top = Math.round((maxHeight - param.height) / 2);
    return param;
}

/**
 * 重置文件视图
 * @param div
 */
function reSetImgView(div) {
    var sFilter = 'filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(sizingMethod=scale,src="';
//        file.select();
//        var src = document.selection.createRange().text;
    div.innerHTML = '<img id=imghead>';
    var img = document.getElementById('imghead');
//        img.filters.item('DXImageTransform.Microsoft.AlphaImageLoader').src = src;
    var rect = clacImgZoomParam(MAXWIDTH, MAXHEIGHT, img.offsetWidth, img.offsetHeight);
    status = ('rect:' + rect.top + ',' + rect.left + ',' + rect.width + ',' + rect.height);
    div.innerHTML = "<div id=divhead style='width:" + rect.width + "px;height:" + rect.height + "px;margin-top:" + rect.top + "px;margin-left:" + rect.left + "px;" + sFilter + src + "\"'></div>";
}

/**
 * ajax暂不使用
 */
var xmlhttp;
function loadXMLDoc(pBody, cfunc) {
    if (window.XMLHttpRequest) {
        xmlhttp = new XMLHttpRequest();
    } else {
        xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
    }
    xmlhttp.onreadystatechange = cfunc
    xmlhttp.open("POST", "contentRequest.php", true)
    xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded")
    xmlhttp.send(pBody)
}

/**
 * 表单提交
 */
var cont;
var overlapping;
function doSub() {
    var username = $("username");
    var password = $("password");
    var content = $("content");
    cont = $("btnSub");
    overlapping = $("btnSubOverlapping");
    if (isEmpty(username.value) || isEmpty(password.value)) {
        alert("用户名或密码不能为空");
    }
    else if (isEmpty(content.value)) {
        alert("内容不能为空");
    } else {
        cont.hidden = true;
        overlapping.hidden = false;
        cont.title = "正在提交...";
        document.forminfo.submit();
    }
}

/**
 * 获得引用
 * @param id
 * @return {Element}
 */
function $(id) {
    return document.getElementById(id);
}

/**
 * 变量空判断
 * true为空，false为有值
 * @param str
 * @return boolean
 */
function isEmpty(str) {
    return empty = str.replace(/(^\s*)|(\s*$)/g, "") == "" ? true : false;
}

/**
 * 回调提示表单提交结果
 * @param str
 */
function callback(str) {
    overlapping.hidden = true;
    cont.hidden = false;
    cont.title = "点击提交";
    alert(str);
}